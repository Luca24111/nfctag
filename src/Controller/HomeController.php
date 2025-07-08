<?php

namespace App\Controller;

use App\Repository\ProdottoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ProdottoRepository $prodottoRepository): Response
    {

        $prodotti = $prodottoRepository->findAll();

        
        return $this->render('home/index.html.twig', [
            'prodotti' => $prodotti,
        ]);
    }
}
