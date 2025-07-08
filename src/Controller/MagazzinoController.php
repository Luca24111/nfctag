<?php

namespace App\Controller;

use App\Entity\Prodotto;
use App\Form\ProdottoType;
use App\Repository\ProdottoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

    #[Route('/magazzino/nuovo', name: 'app_prodotto_nuovo')]
    public function nuovo(Request $request, EntityManagerInterface $em): Response
    {
        // creo il prodotto e imposto i valori di default
        $prodotto = new Prodotto();
        $prodotto
            ->setAvaiable(true)   // disponibile di default
            ->setOut(false);      // in magazzino di default

        $form = $this->createForm(ProdottoType::class, $prodotto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $now = new \DateTime();
            $prodotto
                ->setCreatedAt($now)
                ->setUpdateDate($now);

            $em->persist($prodotto);
            $em->flush();

            return $this->redirectToRoute('app_prodotto_successo');
        }

        return $this->render('magazzino/crea.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/magazzino/successo', name: 'app_prodotto_successo')]
    public function successo(): Response
    {
        return $this->render('magazzino/prodotto_successo.html.twig');
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
}
