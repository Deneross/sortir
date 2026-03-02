<?php

namespace App\Controller;

use App\Access\CheckActifParticipant;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ActifController extends AbstractController
{
    #[Route('/login/actif', name: 'app_actif')]
    public function checkingIfActif(CheckActifParticipant $check): Response
    {
        $isDenied = $check->isParticipantUnactif();
        
        if ($isDenied) {
            $this->addFlash('danger', 'Vos droits ont été révoqués, vous n\'êtes plus actif sur la plateforme');
            $this->createAccessDeniedException();
        }

        return $this->redirectToRoute('sortie_liste');
    }
}
