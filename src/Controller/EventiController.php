<?php
// src/Controller/EventiController.php
namespace App\Controller;

use App\Entity\Eventi;
use App\Repository\ProdottoRepository;
use App\Form\EventiType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class EventiController extends AbstractController
{
    #[Route('/eventi', name: 'app_eventi')]
    public function index(EntityManagerInterface $em): Response
    {
        $eventi = $em->getRepository(Eventi::class)
                     ->findBy([], ['data' => 'DESC']);

        return $this->render('eventi/index.html.twig', [
            'eventi' => $eventi,
        ]);
    }

    #[Route('/evento/nuovo', name: 'app_evento_nuovo')]
    public function nuovo(Request $request, EntityManagerInterface $em): Response
    {
        $evento = new Eventi();
        $form = $this->createForm(EventiType::class, $evento);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($evento);
            $em->flush();
            return $this->redirectToRoute('app_evento_successo');
        }

        return $this->render('eventi/crea.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/evento/successo', name: 'app_evento_successo')]
    public function successo(): Response
    {
        return $this->render('eventi/successo.html.twig');
    }

    #[Route('/eventi/{id}', name: 'app_evento_detail')]
    public function detail(
        Eventi $evento,
        ProdottoRepository $prodRepo
    ): Response {
        // tutti i prodotti disponibili (avaiable = true)
        $allProducts = $prodRepo->findBy(['avaiable' => true]);

        return $this->render('eventi/detail.html.twig', [
            'evento'      => $evento,
            'prodotti'    => $evento->getProdotti(),
            'allProducts' => $allProducts,
        ]);
    }

    #[Route(
        '/eventi/{id}/add-prodotto',
        name: 'app_evento_add_prodotto',
        methods: ['POST']
    )]
    public function addProdotto(
        Request $request,
        Eventi $evento,
        ProdottoRepository $prodRepo,
        EntityManagerInterface $em
    ): Response {
        $productId = $request->request->get('productId');
        if ($productId) {
            $prodotto = $prodRepo->find($productId);
            if ($prodotto && $prodotto->isAvaiable()) {
                // **lato owning**: aggiunge l'evento al prodotto
                $prodotto->addEvento($evento);

                // (opzionale) mantiene sincronizzato anche l'inverse side
                $evento->addProdotto($prodotto);

                $em->flush();
                $this->addFlash('success', 'Prodotto associato con successo.');
            } else {
                $this->addFlash('error', 'Prodotto non valido o non disponibile.');
            }
        }
        return $this->redirectToRoute('app_evento_detail', [
            'id' => $evento->getId(),
        ]);
    }

    #[Route('/eventi/{id}/add-nfc', name: 'app_evento_add_nfc', methods: ['POST'])]
    public function addNfc(Request $request, EntityManagerInterface $em, ProdottoRepository $prodRepo, Eventi $evento): Response
    {
        $nfcId = $request->request->get('nfc_Id');
        $prodotto = $prodRepo->findOneBy(['nfcId' => $nfcId]);

        // Controlla se è una richiesta AJAX
        if ($request->isXmlHttpRequest()) {
                    if (!$prodotto) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Tag NFC non riconosciuto o prodotto non registrato'
            ]);
        }

            // Verifica se il prodotto è già associato all'evento
            if ($evento->getProdotti()->contains($prodotto)) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Questo prodotto è già associato all\'evento'
                ]);
            }

            // Assegna l'evento al prodotto e sincronizza entrambi i lati
            $prodotto->addEvento($evento);
            $evento->addProdotto($prodotto);

            $em->persist($prodotto);
            $em->persist($evento);
            $em->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Prodotto associato con successo',
                'product' => [
                    'id' => $prodotto->getId(),
                    'name' => $prodotto->getName(),
                    'isOut' => $prodotto->isOut()
                ],
                'eventoId' => $evento->getId(),
                'csrfToken' => $this->container->get('security.csrf.token_manager')->getToken('remove-prodotto'.$evento->getId().'-'.$prodotto->getId())->getValue()
            ]);
        }

        // Richiesta normale (non AJAX)
        if (!$prodotto) {
            $this->addFlash('error', sprintf('Prodotto non trovato per il tag NFC: %s', $nfcId));
        } else {
            // Assegna l'evento al prodotto e sincronizza entrambi i lati
            $prodotto->addEvento($evento);
            $evento->addProdotto($prodotto);

            $em->persist($prodotto);
            $em->persist($evento);
            $em->flush();

            $this->addFlash('success', 'Prodotto associato con successo.');
        }

        return $this->redirectToRoute('app_evento_detail', ['id' => $evento->getId()]);
    }


    
    #[Route(
        '/eventi/{id}/remove-prodotto/{productId}',
        name: 'app_evento_remove_prodotto',
        methods: ['POST']
    )]
    public function removeProdotto(
        Request $request,
        Eventi $evento,
        ProdottoRepository $prodRepo,
        EntityManagerInterface $em,
        CsrfTokenManagerInterface $csrfManager,
        int $productId
    ): Response {
        // controlla CSRF
        $submittedToken = $request->request->get('_token');
        if (!$csrfManager->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken('remove-prodotto'.$evento->getId().'-'.$productId, $submittedToken))) {
            throw new InvalidCsrfTokenException('Token non valido.');
        }

        if ($prodotto = $prodRepo->find($productId)) {
            // owning side
            $evento->removeProdotto($prodotto);
            // (optional) inverse side
            $prodotto->removeEvento($evento);

            $em->flush();
        } else {
            $this->addFlash('error', 'Prodotto non trovato.');
        }

        return $this->redirectToRoute('app_evento_detail', [
            'id' => $evento->getId(),
        ]);
    }


    #[Route('/eventi/{id}/delete', name: 'app_evento_delete', methods: ['POST'])]
    public function delete(Eventi $evento, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete-evento'.$evento->getId(), $request->request->get('_token'))) {
            $em->remove($evento);
            $em->flush();
            $this->addFlash('success', 'Evento eliminato con successo.');
        }
        return $this->redirectToRoute('app_eventi');
    }

    
    #[Route('/eventi/{id}/modifica', name: 'app_evento_modifica', methods: ['GET','POST'])]
    public function edit(
        Eventi $evento,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $form = $this->createForm(EventiType::class, $evento);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Evento aggiornato con successo.');
            return $this->redirectToRoute('app_evento_detail', [
                'id' => $evento->getId(),
            ]);
        }

        return $this->render('eventi/edit.html.twig', [
            'evento' => $evento,
            'form'   => $form->createView(),
        ]);
    }

}
