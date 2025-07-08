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
use Symfony\Component\Routing\Annotation\Route;

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

}
