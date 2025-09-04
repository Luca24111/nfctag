<?php

namespace App\Controller;

use App\Entity\Prodotto;
use App\Form\ProdottoType;
use App\Repository\ProdottoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Psr\Log\LoggerInterface;

final class MagazzinoController extends AbstractController
{
    #[Route('/magazzino', name: 'app_magazzino')]
    public function inMagazzino(Request $request, ProdottoRepository $prodottoRepository): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 20;
        $search = $request->query->get('search', '');
        $scaffale = $request->query->get('scaffale', '');
        
        $filters = ['available' => true];
        if (!empty($search)) {
            $filters['search'] = $search;
        }
        if (!empty($scaffale)) {
            $filters['scaffale'] = $scaffale;
        }
        
        $prodotti = $prodottoRepository->findPaginated($page, $limit, $filters);
        $total = $prodottoRepository->countWithFilters($filters);
        $stats = $prodottoRepository->getStats();
        $scaffali = $prodottoRepository->getScaffali();
        
        $totalPages = ceil($total / $limit);

        return $this->render('magazzino/inside.html.twig', [
            'prodotti' => $prodotti,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'search' => $search,
            'scaffale' => $scaffale,
            'total' => $total,
            'stats' => $stats,
            'scaffali' => $scaffali
        ]);
    }

    // src/Controller/MagazzinoController.php

    #[Route('/magazzino/nuovo', name: 'app_prodotto_nuovo')]
    public function nuovo(Request $request, EntityManagerInterface $em, SessionInterface $session, LoggerInterface $logger): Response
    {
        $prodotto = new Prodotto();
        $prodotto
            ->setAvaiable(true)
            ->setOut(false);

        // se passo ?nfcId=30, lo salvo
        if ($request->query->get('nfcId')) {
            $prodotto->setNfcId((int)$request->query->get('nfcId'));
        }

        $form = $this->createForm(ProdottoType::class, $prodotto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestione upload immagine
            $immagineFile = $form->get('immagine')->getData();
            $fotoBase64 = $request->request->get('foto_base64');
            
            if ($immagineFile || $fotoBase64) {
                // Validazione file
                if ($immagineFile && $immagineFile->getSize() > 10 * 1024 * 1024) { // 10MB max
                    $this->addFlash('error', 'Il file è troppo grande. Massimo 10MB.');
                    return $this->redirectToRoute('app_prodotto_nuovo');
                }
                
                $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
                if ($immagineFile && !in_array($immagineFile->getMimeType(), $allowedMimes)) {
                    $this->addFlash('error', 'Tipo file non supportato. Usa JPEG, PNG o WebP.');
                    return $this->redirectToRoute('app_prodotto_nuovo');
                }
                
                // Prepara i dati immagine da file o da base64
                $imageData = null;
                $originalFilename = 'camera';
                if ($immagineFile) {
                    $imageData = file_get_contents($immagineFile->getPathname());
                    $originalFilename = pathinfo($immagineFile->getClientOriginalName(), PATHINFO_FILENAME);
                } elseif ($fotoBase64) {
                    if (preg_match('/^data:image\/(png|jpe?g|webp);base64,/', $fotoBase64, $m)) {
                        $imageData = base64_decode(substr($fotoBase64, strpos($fotoBase64, ',') + 1));
                        // Mantieni estensione coerente (forziamo jpg in uscita)
                    } else {
                        $this->addFlash('error', 'Formato immagine non valido.');
                        return $this->redirectToRoute('app_prodotto_nuovo');
                    }
                }
                
                if ($imageData) {
                    try {
                        // Compressione e ottimizzazione immagine
                        $manager = new ImageManager(new Driver());
                        $image = $manager->read($imageData);
                        
                        // Ridimensionamento se troppo grande
                        $maxWidth = 1200;
                        $maxHeight = 1200;
                        if ($image->width() > $maxWidth || $image->height() > $maxHeight) {
                            $image->scaleDown($maxWidth, $maxHeight);
                        }
                        
                        // Compressione JPEG con qualità 80%
                        $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII', $originalFilename);
                        $newFilename = $safeFilename . '-' . uniqid() . '.jpg';
                        
                        $uploadsDir = $this->getParameter('uploads_directory');
                        
                        if (!is_dir($uploadsDir)) {
                            mkdir($uploadsDir, 0755, true);
                        }
                        
                        $imagePath = $uploadsDir . '/' . $newFilename;
                        $image->toJpeg(80)->save($imagePath);
                        
                        $prodotto->setImage('uploads/images/' . $newFilename);
                        $this->addFlash('success', 'Immagine ottimizzata e salvata con successo!');
                    } catch (\Exception $e) {
                        $this->addFlash('error', 'Errore durante il salvataggio dell\'immagine');
                        if ($this->getParameter('kernel.environment') === 'dev') {
                            $this->addFlash('debug', $e->getMessage());
                        }
                        return $this->redirectToRoute('app_prodotto_nuovo');
                    }
                }
            }

            $now = new \DateTime();
            $prodotto
                ->setCreatedAt($now)
                ->setUpdateDate($now);

            $em->persist($prodotto);
            $em->flush();

            // Log dell'azione
            $logger->info('Nuovo prodotto creato', [
                'user' => $this->getUser()->getEmail(),
                'product_id' => $prodotto->getId(),
                'product_name' => $prodotto->getName(),
                'has_image' => !empty($prodotto->getImage())
            ]);

            // Salva i dati del prodotto nella session per la pagina di successo
            $session->set('prodotto_creato', [
                'nome' => $prodotto->getName(),
                'descrizione' => $prodotto->getDescription(),
                'posizione' => $prodotto->getScaffale(),
                'immagine' => $prodotto->getImage(),
            ]);
            // Salva anche l'ID per ricaricare eventuali dati mancanti (es. immagine)
            $session->set('prodotto_creato_id', $prodotto->getId());

            return $this->redirectToRoute('app_prodotto_successo');
        } else {
            if ($form->isSubmitted()) {
                $this->addFlash('error', 'Errore nella compilazione del form');
                if ($this->getParameter('kernel.environment') === 'dev') {
                    $errors = $form->getErrors(true);
                    foreach ($errors as $error) {
                        $this->addFlash('debug', 'Form error: ' . $error->getMessage());
                    }
                }
            }
        }

        return $this->render('magazzino/crea.html.twig', [
            'form' => $form->createView(),
        ]);
    }



    #[Route('/magazzino/successo', name: 'app_prodotto_successo')]
    public function successo(SessionInterface $session, ProdottoRepository $prodottoRepository): Response
    {
        $prodottoData = $session->get('prodotto_creato', []);
        $productId = $session->get('prodotto_creato_id');

        // Se l'immagine non è presente in sessione, prova a ricaricarla dal DB usando l'ID
        if ((!isset($prodottoData['immagine']) || empty($prodottoData['immagine'])) && $productId) {
            $prodotto = $prodottoRepository->find($productId);
            if ($prodotto && $prodotto->getImage()) {
                $prodottoData['immagine'] = $prodotto->getImage();
            }
        }

        // Rimuovi i dati dalla session dopo averli recuperati
        $session->remove('prodotto_creato');
        $session->remove('prodotto_creato_id');

        return $this->render('magazzino/prodotto_successo.html.twig', [
            'nome' => $prodottoData['nome'] ?? null,
            'descrizione' => $prodottoData['descrizione'] ?? null,
            'posizione' => $prodottoData['posizione'] ?? null,
            'immagine' => $prodottoData['immagine'] ?? null,
        ]);
    }

    #[Route(
        '/magazzino/{id}',
        name: 'app_prodotto_detail',
        requirements: ['id' => '\d+']
    )]
    public function detail(Prodotto $prodotto): Response
    {
        return $this->render('magazzino/detail.html.twig', [
            'prodotto' => $prodotto,
        ]);
    }

    #[Route('/fuori/magazzino', name: 'app_out_magazzino')]
    public function outMagazzino(Request $request, ProdottoRepository $prodottoRepository): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 20;
        $search = $request->query->get('search', '');
        $scaffale = $request->query->get('scaffale', '');
        
        $filters = ['out' => true];
        if (!empty($search)) {
            $filters['search'] = $search;
        }
        if (!empty($scaffale)) {
            $filters['scaffale'] = $scaffale;
        }
        
        $prodotti = $prodottoRepository->findPaginated($page, $limit, $filters);
        $total = $prodottoRepository->countWithFilters($filters);
        $stats = $prodottoRepository->getStats();
        $scaffali = $prodottoRepository->getScaffali();
        
        $totalPages = ceil($total / $limit);

        return $this->render('magazzino/out.html.twig', [
            'prodotti' => $prodotti,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'search' => $search,
            'scaffale' => $scaffale,
            'total' => $total,
            'stats' => $stats,
            'scaffali' => $scaffali
        ]);
    }

    #[Route('/magazzino/{id}/delete', name: 'app_prodotto_delete', methods: ['POST'])]
    public function delete(Prodotto $prodotto, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete-prodotto'.$prodotto->getId(), $request->request->get('_token'))) {
            $em->remove($prodotto);
            $em->flush();
            $this->addFlash('success', 'Prodotto eliminato con successo.');
        }
        return $this->redirectToRoute('app_magazzino');
    }

    #[Route('/magazzino/{id}/modifica', name: 'app_prodotto_modifica', methods: ['GET','POST'])]
    public function edit(
        Prodotto $prodotto,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $form = $this->createForm(ProdottoType::class, $prodotto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // aggiorna la data di modifica
            $prodotto->setUpdateDate(new \DateTime());
            $em->flush();

            $this->addFlash('success', 'Prodotto aggiornato con successo.');
            return $this->redirectToRoute('app_prodotto_detail', [
                'id' => $prodotto->getId(),
            ]);
        }

        return $this->render('magazzino/edit.html.twig', [
            'prodotto' => $prodotto,
            'form'     => $form->createView(),
        ]);
    }

    #[Route('/genera-prodotti-finti', name: 'app_genera_prodotti_finti')]
    public function generaProdottiFinti(EntityManagerInterface $em): Response
    {
        // Aumenta i limiti per gestire 50k prodotti
        ini_set('memory_limit', '2G');
        ini_set('max_execution_time', 600); // 10 minuti
        set_time_limit(600);
        
        $faker = \Faker\Factory::create('it_IT');
        
        $targetCount = 50000;
        $prodottiGenerati = 0;
        $batchSize = 1000; // Aumentato per migliorare performance
        
        $this->addFlash('info', "🚀 Iniziando generazione di $targetCount prodotti...");
        
        try {
            for ($i = 0; $i < $targetCount; $i++) {
                $prodotto = new Prodotto();
                
                // Genera nome prodotto realistico
                $tipi = ['Laptop', 'Monitor', 'Mouse', 'Tastiera', 'Cuffie', 'Webcam', 'Microfono', 'Stampante', 'Scanner', 'Router', 'Tablet', 'Smartphone', 'Server', 'Workstation', 'All-in-One'];
                $marchi = ['Apple', 'Dell', 'HP', 'Lenovo', 'Samsung', 'LG', 'Asus', 'Acer', 'Logitech', 'Corsair', 'MSI', 'Gigabyte', 'Razer', 'SteelSeries', 'HyperX'];
                $tipo = $faker->randomElement($tipi);
                $marca = $faker->randomElement($marchi);
                $modello = $faker->numberBetween(1000, 9999);
                
                $prodotto->setName($tipo . ' ' . $marca . ' ' . $modello);
                $prodotto->setDescription($faker->sentence(8, 15));
                
                // Genera scaffale realistico (A-Z, 1-100)
                $scaffale = $faker->randomLetter() . $faker->numberBetween(1, 100);
                $prodotto->setScaffale($scaffale);
                
                // 70% in magazzino, 30% fuori
                $isOut = $faker->boolean(30);
                $prodotto->setOut($isOut);
                $prodotto->setAvaiable(!$isOut);
                
                // Genera date realistiche
                $createdAt = $faker->dateTimeBetween('-2 years', 'now');
                $prodotto->setCreatedAt($createdAt);
                
                // Update date più recente del created
                $updateDate = $faker->dateTimeBetween($createdAt, 'now');
                $prodotto->setUpdateDate($updateDate);
                
                // 80% con immagine, 20% senza
                if ($faker->boolean(80)) {
                    $prodotto->setImage('uploads/images/placeholder-' . $faker->numberBetween(1, 10) . '.jpg');
                }
                
                $em->persist($prodotto);
                $prodottiGenerati++;
                
                // Flush ogni 1000 prodotti per ottimizzare performance
                if ($prodottiGenerati % $batchSize === 0) {
                    $em->flush();
                    $em->clear(); // Clear per liberare memoria
                    
                    // Progress update ogni 5000 prodotti
                    if ($prodottiGenerati % 5000 === 0) {
                        $progress = round(($prodottiGenerati / $targetCount) * 100, 1);
                        $memoryUsage = round(memory_get_usage(true) / 1024 / 1024, 1);
                        $this->addFlash('info', "📊 Generati $prodottiGenerati prodotti ($progress%) - Memoria: {$memoryUsage}MB");
                    }
                }
            }
            
            // Final flush
            $em->flush();
            
            $this->addFlash('success', "✅ Generati con successo $prodottiGenerati prodotti finti!");
            
        } catch (\Exception $e) {
            $this->addFlash('error', "❌ Errore durante la generazione: " . $e->getMessage());
            $this->addFlash('info', "Prodotti generati prima dell'errore: $prodottiGenerati");
            $this->addFlash('debug', "Stack trace: " . $e->getTraceAsString());
        }
        
        return $this->redirectToRoute('app_home');
    }

    #[Route('/elimina-prodotti-finti', name: 'app_elimina_prodotti_finti')]
    public function eliminaProdottiFinti(EntityManagerInterface $em): Response
    {
        // Metodo ottimizzato per eliminare grandi quantità di prodotti finti
        ini_set('memory_limit', '1G');
        ini_set('max_execution_time', 300);
        set_time_limit(300);
        
        $connection = $em->getConnection();
        $prodottiEliminati = 0;
        
        // Lista completa di tutti i tipi e marchi usati nella generazione
        $tipiFinti = ['Laptop', 'Monitor', 'Mouse', 'Tastiera', 'Cuffie', 'Webcam', 'Microfono', 'Stampante', 'Scanner', 'Router', 'Tablet', 'Smartphone', 'Server', 'Workstation', 'All-in-One'];
        $marchiFinti = ['Apple', 'Dell', 'HP', 'Lenovo', 'Samsung', 'LG', 'Asus', 'Acer', 'Logitech', 'Corsair', 'MSI', 'Gigabyte', 'Razer', 'SteelSeries', 'HyperX'];
        
        $this->addFlash('info', "🗑️ Iniziando eliminazione prodotti finti...");
        
        try {
            // Metodo 1: Eliminazione con SQL diretto (più veloce)
            $patterns = [];
            foreach ($tipiFinti as $tipo) {
                foreach ($marchiFinti as $marca) {
                    $patterns[] = "'%" . $tipo . ' ' . $marca . "%'";
                }
            }
            
            $sql = "DELETE FROM prodotto WHERE name LIKE " . implode(' OR name LIKE ', $patterns);
            $stmt = $connection->prepare($sql);
            $result = $stmt->execute();
            
            if ($result) {
                // In Doctrine DBAL, dobbiamo usare executeStatement per ottenere il row count
                $prodottiEliminati = $connection->executeStatement($sql);
                $this->addFlash('success', "✅ Eliminati $prodottiEliminati prodotti finti con SQL diretto!");
            } else {
                // Fallback: metodo Doctrine se SQL diretto fallisce
                $this->addFlash('info', "⚠️ SQL diretto fallito, uso metodo Doctrine...");
                
                $batchSize = 500;
                $totalEliminati = 0;
                
                foreach ($tipiFinti as $tipo) {
                    foreach ($marchiFinti as $marca) {
                        $pattern = '%' . $tipo . ' ' . $marca . '%';
                        
                        // Query con LIMIT per evitare memory issues
                        $qb = $em->createQueryBuilder();
                        $qb->select('p')
                           ->from(Prodotto::class, 'p')
                           ->where('p.name LIKE :pattern')
                           ->setParameter('pattern', $pattern)
                           ->setMaxResults($batchSize);
                        
                        $prodotti = $qb->getQuery()->getResult();
                        
                        while (!empty($prodotti)) {
                            foreach ($prodotti as $prodotto) {
                                $em->remove($prodotto);
                                $totalEliminati++;
                            }
                            
                            $em->flush();
                            $em->clear();
                            
                            // Progress update
                            if ($totalEliminati % 1000 === 0) {
                                $this->addFlash('info', "🗑️ Eliminati $totalEliminati prodotti finti...");
                            }
                            
                            // Ricarica il prossimo batch
                            $prodotti = $qb->getQuery()->getResult();
                        }
                    }
                }
                
                $prodottiEliminati = $totalEliminati;
                $this->addFlash('success', "✅ Eliminati $prodottiEliminati prodotti finti con Doctrine!");
            }
            
        } catch (\Exception $e) {
            $this->addFlash('error', "❌ Errore durante l'eliminazione: " . $e->getMessage());
            $this->addFlash('info', "Prodotti eliminati prima dell'errore: $prodottiEliminati");
        }
        
        return $this->redirectToRoute('app_home');
    }

    #[Route('/test-genera-1000', name: 'app_test_genera_1000')]
    public function testGenera1000(EntityManagerInterface $em): Response
    {
        // Test con 1000 prodotti per verificare che funzioni
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 120);
        set_time_limit(120);
        
        $faker = \Faker\Factory::create('it_IT');
        
        $targetCount = 1000;
        $prodottiGenerati = 0;
        $batchSize = 100;
        
        $this->addFlash('info', "🧪 Test: Iniziando generazione di $targetCount prodotti...");
        
        try {
            for ($i = 0; $i < $targetCount; $i++) {
                $prodotto = new Prodotto();
                
                $tipi = ['Laptop', 'Monitor', 'Mouse', 'Tastiera', 'Cuffie'];
                $marchi = ['Apple', 'Dell', 'HP', 'Lenovo', 'Samsung'];
                $tipo = $faker->randomElement($tipi);
                $marca = $faker->randomElement($marchi);
                $modello = $faker->numberBetween(1000, 9999);
                
                $prodotto->setName($tipo . ' ' . $marca . ' ' . $modello);
                $prodotto->setDescription($faker->sentence(5, 10));
                $prodotto->setScaffale($faker->randomLetter() . $faker->numberBetween(1, 50));
                $prodotto->setOut($faker->boolean(30));
                $prodotto->setAvaiable(!$prodotto->isOut());
                $prodotto->setCreatedAt($faker->dateTimeBetween('-1 year', 'now'));
                $prodotto->setUpdateDate($faker->dateTimeBetween($prodotto->getCreatedAt(), 'now'));
                
                if ($faker->boolean(80)) {
                    $prodotto->setImage('uploads/images/placeholder-' . $faker->numberBetween(1, 10) . '.jpg');
                }
                
                $em->persist($prodotto);
                $prodottiGenerati++;
                
                if ($prodottiGenerati % $batchSize === 0) {
                    $em->flush();
                    $em->clear();
                    
                    if ($prodottiGenerati % 200 === 0) {
                        $progress = round(($prodottiGenerati / $targetCount) * 100, 1);
                        $this->addFlash('info', "🧪 Test: Generati $prodottiGenerati prodotti ($progress%)");
                    }
                }
            }
            
            $em->flush();
            $this->addFlash('success', "✅ Test completato: Generati $prodottiGenerati prodotti!");
            
        } catch (\Exception $e) {
            $this->addFlash('error', "❌ Test fallito: " . $e->getMessage());
            $this->addFlash('info', "Prodotti generati: $prodottiGenerati");
        }
        
        return $this->redirectToRoute('app_home');
    }

    #[Route('/conta-prodotti-finti', name: 'app_conta_prodotti_finti')]
    public function contaProdottiFinti(EntityManagerInterface $em): Response
    {
        // Conta quanti prodotti finti ci sono nel database
        $connection = $em->getConnection();
        
        $tipiFinti = ['Laptop', 'Monitor', 'Mouse', 'Tastiera', 'Cuffie', 'Webcam', 'Microfono', 'Stampante', 'Scanner', 'Router', 'Tablet', 'Smartphone', 'Server', 'Workstation', 'All-in-One'];
        $marchiFinti = ['Apple', 'Dell', 'HP', 'Lenovo', 'Samsung', 'LG', 'Asus', 'Acer', 'Logitech', 'Corsair', 'MSI', 'Gigabyte', 'Razer', 'SteelSeries', 'HyperX'];
        
        $patterns = [];
        foreach ($tipiFinti as $tipo) {
            foreach ($marchiFinti as $marca) {
                $patterns[] = "'%" . $tipo . ' ' . $marca . "%'";
            }
        }
        
        $sql = "SELECT COUNT(*) as count FROM prodotto WHERE name LIKE " . implode(' OR name LIKE ', $patterns);
        $stmt = $connection->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        
        $prodottiFinti = $result['count'];
        $totaleProdotti = $em->getRepository(Prodotto::class)->count([]);
        
        $this->addFlash('info', "📊 Database Status:");
        $this->addFlash('info', "   • Prodotti finti: $prodottiFinti");
        $this->addFlash('info', "   • Totale prodotti: $totaleProdotti");
        $this->addFlash('info', "   • Prodotti reali: " . ($totaleProdotti - $prodottiFinti));
        
        return $this->redirectToRoute('app_home');
    }
}
