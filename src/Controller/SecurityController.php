<?php
// src/Controller/SecurityController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(Request $request): Response
    {
        // Puoi eventualmente gestire errori e passare l’ultimo username
        return $this->render('security/login.html.twig', [
            'last_username' => $request->getSession()->get('_security.last_username'),
            'error'         => $request->get('error'),
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Questo metodo rimane vuoto: Symfony intercetta la rotta per il logout.
        throw new \Exception('Questo metodo può rimanere vuoto.');
    }
}
