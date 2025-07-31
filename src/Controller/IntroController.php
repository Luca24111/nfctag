<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class IntroController extends AbstractController
{
    #[Route('/', name: 'app_intro')]
    public function intro(): Response
    {
        return $this->render('intro/index.html.twig');
    }
}



