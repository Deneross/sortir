<?php

namespace App\Controller;

use App\Exception\ParticipantNotFound;
use App\Form\ParticipantType;
use App\Repository\ParticipantRepository;
use App\Util\FormParticipant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/participant', name: 'app_participant')]
final class ParticipantController extends AbstractController
{
    #[Route('', name: '_read', methods: ['GET'])]
    public function index(
        FormParticipant $service
    ): Response
    {
        try {
            $participantCo = $service->getParticipant();
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
        FormParticipant        $service,
        UserPasswordHasherInterface $toHash,
        EntityManagerInterface $em,
        Request                $request
    ): Response
    {
        try {
            $participant = $service->getParticipant();
            $campus = $participant->getCampus();
            $form = $this->createForm(ParticipantType::class, $participant);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                //Je perdais le campus à la soumission à cause du disbled, je le réinjecte ici.
                $participant->setCampus($campus);

                $pwdChanged = $form->get('newPassword')->getData();
                if (!empty($pwdChanged)) {
                    $participant->setPassword($toHash->hashPassword($participant, $pwdChanged));

                }

                $em->flush();

                $this->addFlash('success','Ton profil vient d\'être mis à jour !');
                return $this->redirectToRoute('app_participant_read');
            }
        } catch (ParticipantNotFound $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('sortie_liste');
        }

        return $this->render('participant/base.html.twig', [
            'form' => 'participant/form_update.html.twig',
            'formParticipant' => $form,
            'participant' => $participant,
            'img' => $service->getProfilPicture($participant),
        ]);
    }
}
