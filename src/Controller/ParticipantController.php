<?php

namespace App\Controller;

use App\Exception\ParticipantNotFound;
use App\Form\ParticipantType;
use App\ParticipantService\ParticipantService;
use App\Repository\ParticipantRepository;
use App\Util\FromUserToParticipant;
use App\Util\ImgManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/participant', name: 'app_participant')]
final class ParticipantController extends AbstractController
{
    /******* Routes sur un participant connecté, faisant appel au service pour returner le user ********/
    #[Route('', name: '_read', methods: ['GET'])]
    public function index(
        ImgManager            $service,
        FromUserToParticipant $rightParticipant
    ): Response
    {
        try {
            $participantCo = $rightParticipant->getParticipant();
        } catch (ParticipantNotFound $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('app_login');
        }

        return $this->render('participant/base.html.twig', [
            'form' => 'participant/form_readonly.html.twig',
            'participant' => $participantCo,
            'img' => $service->getProfilPicture($participantCo),
        ]);
    }

    #[Route('/update', name: '_update', methods: ['GET', 'POST'])]
    public function update(
        ParticipantService $participantService,
        ImgManager                  $service,
        FromUserToParticipant       $rightParticipant,
        Request                     $request
    ): Response
    {
        try {
            $participant = $rightParticipant->getParticipant();
            $campus = $participant->getCampus();
            $form = $this->createForm(ParticipantType::class, $participant);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {

                $participantService->updateParticipant($form,$participant,$campus);

                $this->addFlash('success', 'Ton profil vient d\'être mis à jour !');
                return $this->redirectToRoute('app_participant_read');
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

    /******* Routes standard pour afficher les infos d'un participant ********/
    #[Route('/{id}', name: '_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function display(
        ImgManager            $service,
        int                   $id,
        ParticipantRepository $repo,
    ): Response
    {
        $participantCo = $repo->find($id);

        if (!$participantCo) {
            throw new ParticipantNotFound(
                'Aucun participant correspondant',
                Response::HTTP_NOT_FOUND
            );
        }

        return $this->render('participant/base.html.twig', [
            'form' => 'participant/form_readonly.html.twig',
            'participant' => $participantCo,
            'img' => $service->getProfilPicture($participantCo),
        ]);
    }
}
