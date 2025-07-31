<?php

namespace App\Controller;

use App\Repository\ProdottoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function index(ProdottoRepository $prodottoRepository): Response
    {

        $prodotti = $prodottoRepository->findAll();

        
        return $this->render('home/index.html.twig', [
            'prodotti' => $prodotti,
        ]);
    }

    #[Route('/tutti-prodotti', name: 'app_tutti_prodotti')]
    public function tuttiProdotti(ProdottoRepository $prodottoRepository): Response
    {
        $prodotti = $prodottoRepository->findAll();

        return $this->render('home/tutti_prodotti.html.twig', [
            'prodotti' => $prodotti,
        ]);
    }
}
