<?php

namespace App\Controller;

use App\Access\CheckActifParticipant;
use App\Entity\Participant;
use App\Exception\ParticipantNotFound;
use App\Form\ImportUsersType;
use App\Form\Participant_createType;
use App\Form\ParticipantType;
use App\ParticipantService\ParticipantService;
use App\Repository\CampusRepository;
use App\Repository\ParticipantRepository;
use App\SortieService\FormAndShow;
use App\Util\FromUserToParticipant;
use App\Util\ImgManager;
use App\Util\UserImport;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use phpDocumentor\Reflection\Types\Array_;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
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

        if ($flash->peek("success") === [] && $flash->peek("warning") === []) {
            $this->addFlash('success', 'Bienvenue administrateur ' . $this->getUser()->getPseudo() . ' !');
        }

        return $this->render('admin/index.html.twig');
    }

    #[Route('/list_participants', name: '_participants_show', methods: ['GET'])]
    public function showUsers(
        ParticipantRepository $participantRepository,
        Request               $request
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
        ParticipantService     $participantService,
        EntityManagerInterface $em,
        Request                $request,

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

    #[Route('/{id}/disabled_participant', name: '_participant_disabled', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function disabledUser(
        CheckActifParticipant  $checkActifParticipant,
        ParticipantRepository  $participantRepository,
        EntityManagerInterface $em,
        int                    $id,
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

    /**
     * @throws Exception
     */
    #[Route('/import', name: '_participants_import', methods: ['GET', 'POST'])]
    public function importUsers(
        CampusRepository $campusRepo,
        Request          $request,
        UserImport       $import
    ): Response
    {
        $lancerImport = $this->createForm(ImportUsersType::class, null);
        $lancerImport->handleRequest($request);

        if ($lancerImport->isSubmitted() && $lancerImport->isValid()) {
            $resultatImport = $import->readAndGiveDataOfUserImported($lancerImport);

            $usersToImport = $resultatImport['users'];
            $request->getSession()->set('import_users', $usersToImport);

            foreach ($resultatImport['errors'] as $error) {
                $this->addFlash('danger', $error);
            }

            return $this->redirectToRoute('app_admin_participants_import_validation');
        }

        return $this->render('admin/import/create.html.twig', [
            'campusAvailable' => $campusRepo->getOnlyCampusNames(),
            'form' => $lancerImport
        ]);
    }

    #[Route('/import/users', name: '_participants_import_validation', methods: ['GET', 'POST'])]
    public function validateImport(
        Request                $request,
        EntityManagerInterface $em,
        CampusRepository       $campusRepo,
    ): Response
    {
        $usersToImport = $request->getSession()->get('import_users');
        if (!$usersToImport) {
            $this->addFlash('danger', 'Aucun fichier d\'import disponible');
            return $this->redirectToRoute('app_admin_participants_import');
        }

        if ($request->getMethod() == 'POST') {

            if ($request->request->has('confirm')) {
                foreach ($usersToImport as $row) {
                    $user = $row['userData'];
                    /**
                     * Petit souci de relation avec le campus qui est perdu avec la mise en session
                     * Je lui réatribue le campus ici.
                     */
                    $campus = $campusRepo->findOneBy(['name' => $user->getCampus()->getName()]);
                    $user->setCampus($campus);

                    $em->persist($user);
                }
                $em->flush();
                $this->addFlash('success', 'L\'import vient d\'être réalisé');
            } else {
                $this->addFlash('warning', 'L\'import a été annulé');
            }
            $request->getSession()->remove('import_users');
            return $this->redirectToRoute('app_adminapp_adminanduser_index');
        }

        return $this->render('admin/import/validate.html.twig', [
            'rows' => $usersToImport,
        ]);
    }


}
