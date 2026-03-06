<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin', name: 'app_admin')]
#[IsGranted("ROLE_ADMIN")]
final class AdminAndUserController extends AbstractController
{

    #[Route]
    public function index(SessionInterface $session): Response
    {
        $flash = $session->getFlashBag();

        if ($flash->peek("success") === []) {
            $this->addFlash('success', 'Bienvenue administrateur '.$this->getUser()->getPseudo().' !');
        }

        return $this->render('admin/index.html.twig');
    }


}
