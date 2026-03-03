<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/participant', name: 'app_participant')]
final class ParticipantController extends AbstractController
{
    #[Route('', name: '_display')]
    public function index(Security $security): Response
    {
        $participantCo = $security->getUser();

        //todo: envoyer le bon src de la photo du participant
        $participantPhotoProfil = $participantCo->getNomFichierPhoto()?:'/images/default.png';

        return $this->render('participant/show.html.twig', [
            'participant' =>  $participantCo,
            'img' => $participantPhotoProfil
        ]);
    }
}
