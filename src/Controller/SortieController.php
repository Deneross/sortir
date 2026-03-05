<?php

namespace App\Controller;

use App\Entity\Lieu;
use App\Entity\Sortie;
use App\Enum\EtatSortie;
use App\Exception\EtatError;
use App\Exception\LieuNotFound;
use App\Exception\ParticipantNotFound;
use App\Exception\SortieIllegalUpdate;
use App\Exception\SortieNotFound;
use App\Form\SortieType;
use App\Repository\CampusRepository;
use App\Repository\EtatRepository;
use App\Repository\SortieRepository;
use App\SortieService\EtatManager;
use App\SortieService\FormSubmission;
use App\SortieService\LieuManager;
use App\Util\FromUserToParticipant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/Sortie')]
final class SortieController extends AbstractController
{
    #[Route('/', name: 'sortie_liste', methods: ['GET'])]
    public function list(Request $request, SortieRepository $sortieRepository): Response
    {
        //va chercher les sorties dans la bdd
        $page = max(1, (int)$request->query->get('page', 1));
        $limit = 10;

        $paginator = $sortieRepository->findLastEvents($page, $limit);

        $total = count($paginator);
        $pages = (int)ceil($total / $limit);

        return $this->render('sortie/list.html.twig', [
            //passe les sorties à twig pour affichage
            'sorties' => $paginator,
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
        ]);
    }

    #[Route('/{id}', name: 'sortie_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show($id, SortieRepository $sortieRepository, EtatManager $etatService): Response
    {
        //va chercher la sotie dans la bdd en fonction de l'id
        $sortie = $sortieRepository->find($id);

        return $this->render('sortie/show.html.twig', [
            'sortie' => $sortie,
            'etatColor' => $etatService->etatColorDisplay($sortie),
        ]);
    }

    #[Route('/creer', name: 'sortie_create', methods: ['GET', 'POST'])]
    public function create(
        Request        $request,
        FormSubmission $sortieService,
    ): Response
    {
        try {
            $update = false;

            $newSortie = $sortieService->initialSortie();
            $infoCampus = $newSortie->getCampus();

            $form = $this->createForm(SortieType::class, $newSortie, [
                'CampusToUseAsFilter' => $infoCampus,
                'update' => $update,
            ]);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $sortieService->createSortie($infoCampus, $form, $newSortie);

                $this->addFlash('success', 'La sortie est prête ! Découvrez en tous les détails ici');
                return $this->redirectToRoute('sortie_show', ['id' => $newSortie->getId()]);
            }
        } catch (ParticipantNotFound|EtatError $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('sortie_liste');
        }

        return $this->render('/sortie/form.html.twig', [
            'form' => $form,
            'titleAndH1' => 'Créer une sortie',
            'allowRemove' => $update,
        ]);
    }

    #[Route('/{id}/modifier', name: 'sortie_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        int            $id,
        Request        $request,
        FormSubmission $formSubmission,
        LieuManager    $lieuManager,
    ): Response
    {
        try {
            $update = true;

            $sortie = $formSubmission->getRightSortie($id);
            $formSubmission->ExceptionIfCannotUpdateSortie($sortie);

            $infoCampus = $sortie->getCampus();

            $form = $this->createForm(SortieType::class, $sortie, [
                'CampusToUseAsFilter' => $infoCampus,
                'update' => $update,
            ]);

            $lieuManager->setLieuInput($form, $sortie);

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {

                if ($form->get('supprimer')->isClicked()) {
                    $formSubmission->removeSortie($sortie);

                    $this->addFlash('warning', 'La sortie vient d\'être supprimée.');
                    return $this->redirectToRoute('sortie_liste');
                }

                $formSubmission->updateSortie($infoCampus, $sortie, $form);

                $this->addFlash('success', 'La sortie a bien été mise à jour !');
                return $this->redirectToRoute('sortie_show', ['id' => $id]);

            }

        } catch (SortieNotFound|LieuNotFound|SortieIllegalUpdate $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('sortie_liste');
        }

        return $this->render('/sortie/form.html.twig', [
            'form' => $form,
            'titleAndH1' => 'Mise à jour d\'une sortie',
            'allowRemove' => $update,
        ]);
    }
}
