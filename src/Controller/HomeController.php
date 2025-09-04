<?php

namespace App\Controller;

use App\Repository\ProdottoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function index(Request $request, ProdottoRepository $prodottoRepository): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 20;
        
        $prodotti = $prodottoRepository->findPaginated($page, $limit);
        $total = $prodottoRepository->countWithFilters();
        $stats = $prodottoRepository->getStats();
        
        $totalPages = ceil($total / $limit);
        
        return $this->render('home/index.html.twig', [
            'prodotti' => $prodotti,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'stats' => $stats
        ]);
    }

    #[Route('/tutti-prodotti', name: 'app_tutti_prodotti')]
    public function tuttiProdotti(Request $request, ProdottoRepository $prodottoRepository): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 20;
        $search = $request->query->get('search', '');
        $status = $request->query->get('status', '');
        $scaffale = $request->query->get('scaffale', '');
        
        $filters = [];
        if (!empty($search)) {
            $filters['search'] = $search;
        }
        if (!empty($status)) {
            $filters['out'] = ($status === 'out');
        }
        if (!empty($scaffale)) {
            $filters['scaffale'] = $scaffale;
        }
        
        $prodotti = $prodottoRepository->findPaginated($page, $limit, $filters);
        $total = $prodottoRepository->countWithFilters($filters);
        $stats = $prodottoRepository->getStats();
        $scaffali = $prodottoRepository->getScaffali();
        
        $totalPages = ceil($total / $limit);
        
        return $this->render('home/tutti_prodotti.html.twig', [
            'prodotti' => $prodotti,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'search' => $search,
            'status' => $status,
            'scaffale' => $scaffale,
            'total' => $total,
            'stats' => $stats,
            'scaffali' => $scaffali
        ]);
    }
}
