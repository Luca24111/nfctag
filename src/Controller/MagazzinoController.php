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

final class MagazzinoController extends AbstractController
{
    #[Route('/magazzino', name: 'app_magazzino')]
    public function inMagazzino(ProdottoRepository $prodottoRepository): Response
    {
        $prodotti = $prodottoRepository->findBy(['avaiable' => true]);

        return $this->render('magazzino/inside.html.twig', [
            'prodotti' => $prodotti,
        ]);
    }

    // src/Controller/MagazzinoController.php

    #[Route('/magazzino/nuovo', name: 'app_prodotto_nuovo')]
    public function nuovo(Request $request, EntityManagerInterface $em, SessionInterface $session): Response
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
            if ($immagineFile) {
                $originalFilename = pathinfo($immagineFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII', $originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$immagineFile->guessExtension();
                
                try {
                    $immagineFile->move(
                        $this->getParameter('uploads_directory'),
                        $newFilename
                    );
                    $prodotto->setImage('uploads/images/' . $newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Errore durante il salvataggio dell\'immagine: ' . $e->getMessage());
                    return $this->redirectToRoute('app_prodotto_nuovo');
                }
            }

            $now = new \DateTime();
            $prodotto
                ->setCreatedAt($now)
                ->setUpdateDate($now);

            $em->persist($prodotto);
            $em->flush();

            // Salva i dati del prodotto nella session per la pagina di successo
            $session->set('prodotto_creato', [
                'nome' => $prodotto->getName(),
                'descrizione' => $prodotto->getDescription(),
                'posizione' => $prodotto->getScaffale(),
                'immagine' => $prodotto->getImage(),
            ]);

            return $this->redirectToRoute('app_prodotto_successo');
        }

        return $this->render('magazzino/crea.html.twig', [
            'form' => $form->createView(),
        ]);
    }



    #[Route('/magazzino/successo', name: 'app_prodotto_successo')]
    public function successo(SessionInterface $session): Response
    {
        $prodottoData = $session->get('prodotto_creato', []);
        
        // Rimuovi i dati dalla session dopo averli recuperati
        $session->remove('prodotto_creato');
        
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
    public function outMagazzino(ProdottoRepository $prodottoRepository): Response
    {
        $prodotti = $prodottoRepository->findBy(['avaiable' => true]);

        return $this->render('magazzino/out.html.twig', [
            'prodotti' => $prodotti,
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
}
