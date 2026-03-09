<?php

namespace App\Controller;

use App\Access\CheckActifParticipant;
use App\Entity\Participant;
use App\Exception\ParticipantNotFound;
use App\Form\Participant_createType;
use App\Form\ParticipantType;
use App\ParticipantService\ParticipantService;
use App\Repository\ParticipantRepository;
use App\SortieService\FormAndShow;
use App\Util\FromUserToParticipant;
use App\Util\ImgManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
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
            $this->addFlash('success', 'Bienvenue administrateur ' . $this->getUser()->getPseudo() . ' !');
        }

        return $this->render('admin/index.html.twig');
    }

    #[Route('/list_participants', name: '_participants_show', methods: ['GET'])]
    public function showUsers(
        ParticipantRepository $participantRepository,
        Request $request
    ): Response
    {
        $search = $request->query->get('search');

        if (!empty($search)) {
            $participants = $participantRepository->findBySearch($search);
        } else {
            $participants = $participantRepository->findAll();
        }

        return $this->render('admin/list_participants.html.twig', [
            'participants' => $participants,
            'titleAndH1' => 'Liste des participants',
            'search' => $search,
        ]);
    }

    #[Route('/{id}/update', name: '_admin_update', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function adminUserUpdate(
        ParticipantService    $participantService,
        ImgManager            $service,
        Request               $request,
        ParticipantRepository $participantRepository,
        int                   $id,
    ): Response
    {
        try {
            $participant = $participantRepository->find($id);
            $campus = $participant->getCampus();
            $form = $this->createForm(ParticipantType::class, $participant);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {

                $participantService->updateParticipant($form, $participant, $campus);

                $this->addFlash('success', 'Le participant vient d\'être mis à jour !');
                return $this->redirectToRoute('app_admin_participants_show', ['id' => $id]);
            }
        } catch (ParticipantNotFound $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('sortie_liste');
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface $e) {
        }

        return $this->render('participant/base.html.twig', [
            'form' => 'participant/form_update.html.twig',
            'formParticipant' => $form,
            'participant' => $participant,
            'img' => $service->getProfilPicture($participant),
        ]);
    }

    #[Route('/create_participant', name: '_participant_create', methods: ['GET', 'POST'])]
    public function createUser(
        ParticipantService    $participantService,
        EntityManagerInterface $em,
        Request               $request,

    ): Response
    {
        $participant = new Participant();
        $form = $this->createForm(Participant_createType::class, $participant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $participantService->updateParticipant($form, $participant);

            $em->persist($participant);
            $em->flush();

            $this->addFlash('success', 'Le participant vient d\'être créé !');
            return $this->redirectToRoute('app_admin_participants_show');
        }


        return $this->render('admin/create_participant.html.twig', [
            'participants' => $participant,
            'formParticipant' => $form->createView(),
            'titleAndH1' => 'Créer un participant',
        ]);
    }

    #[Route('/{id}/disabled_participant', name: '_participant_disabled',requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function disabledUser(
        CheckActifParticipant $checkActifParticipant,
        ParticipantRepository $participantRepository,
        EntityManagerInterface $em,
        int $id,
    ): Response
    {
        $participant = $participantRepository->find($id);

        if (!$participant) {
            throw $this->createNotFoundException('Participant introuvable');
        }

        if (!$checkActifParticipant->isGivenParticipantInactif($participant)) {
            $participant->setActif(false);
            $em->flush();

            $this->addFlash('success', 'Le participant vient d\'être rendu inactif !');
        } else {
            $this->addFlash('danger', 'Le participant est déjà inactif.');
        }

        return $this->redirectToRoute('app_admin_participants_show');
    }


}
