<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Repository\ParticipantRepository;
use App\SortieService\FormAndShow;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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

    #[Route('/list_participants', name: 'participants_show', methods: ['GET'])]
    public function showUsers(ParticipantRepository $participantRepository): Response
    {
        $participants = $participantRepository->findAll();

        return $this->render('admin/list_participants.html.twig', [
            'participants' => $participants,
            'titleAndH1' => 'Créer un participant',
        ]);
    }

    #[Route('/{id}/update', name: 'admin_update', methods: ['GET', 'POST'])]
    public function adminUserUpdate():Response
    {

    }


}
