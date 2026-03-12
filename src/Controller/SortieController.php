<?php

namespace App\Controller;

use App\Entity\Lieu;
use App\Entity\Sortie;
use App\Enum\EtatSortie;
use App\Exception\EtatError;
use App\Exception\LieuNotFound;
use App\Exception\ParticipantNotFound;
use App\Exception\SortieAlreadyClosed;
use App\Exception\SortieIllegalDisplay;
use App\Exception\SortieIllegalUpdate;
use App\Exception\SortieNotFound;
use App\Form\CancelSortieType;
use App\Form\SortieType;
use App\Repository\CampusRepository;
use App\Repository\EtatRepository;
use App\Repository\SortieRepository;
use App\SortieService\EtatManager;
use App\SortieService\FormAndShow;
use App\SortieService\LieuManager;
use App\Service\FromUserToParticipant;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping as ORM;
use PHPUnit\Framework\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/sortie')]
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
    public function show($id, SortieRepository $sortieRepository, EtatManager $etatService, FormAndShow $sortieService): Response
    {
        //va chercher la sotie dans la bdd en fonction de l'id
        $sortie = $sortieRepository->find($id);

        try{
            $sortieService->exceptionIfCannotRead($sortie);
        } catch (SortieIllegalDisplay $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('sortie_liste');
        }

        return $this->render('sortie/show.html.twig', [
            'sortie' => $sortie,
            'etatColor' => $etatService->etatColorDisplay($sortie),
        ]);
    }

    #[Route('/creer', name: 'sortie_create', methods: ['GET', 'POST'])]
    public function create(
        Request     $request,
        FormAndShow $sortieService,
    ): Response
    {
        try {
            $update = false;

            $newSortie = $sortieService->initialSortie();
            $infoCampus = $newSortie->getCampus();

            $form = $this->createForm(SortieType::class, $newSortie, [
                'CampusToUseAsFilter' => $infoCampus,
                'update' => $update,
                'dataUrlVille' => $this->generateUrl('app_ville_cp')
            ]);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $sortieService->createSortie($infoCampus, $form, $newSortie, $request);

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
        int         $id,
        Request     $request,
        FormAndShow $formSubmission,
        LieuManager $lieuManager,
    ): Response
    {
        try {
            $update = true;

            $sortie = $formSubmission->getRightSortie($id);
            $formSubmission->ExceptionIfCannotUpdateSortie($sortie);

            $infoCampus = $sortie->getCampus();

            $cpVille = [];
            foreach ($sortie->getLieux() as $lieu) {
                $cpVille[] = $lieu->getVille()->getCodePostal();
            }

            $form = $this->createForm(SortieType::class, $sortie, [
                'CampusToUseAsFilter' => $infoCampus,
                'update' => $update,
                'dataUrlVille' => $this->generateUrl('app_ville_cp'),
                'cpVilleOrigine' => $cpVille[0],
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

    #[Route("/{id}/publish",name: 'sortie_publish', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function publishSortie(int $id, FormAndShow $sortieService, SortieRepository $sortieRepository): Response
    {
        $sortie = $sortieRepository->find($id);
        $sortieService->publishSortie($sortie);

        $this->addFlash('success','La sortie à été publié');
        return $this->redirectToRoute('sortie_liste');
    }

    #[Route("/{id}/register",name: 'sortie_register', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function registerSortie(int $id, FormAndShow $sortieService, SortieRepository $sortieRepository): Response
    {
        $sortie = $sortieRepository->find($id);
        $sortieService->registerSortie($sortie);

        $this->addFlash('success','Vous êtes inscrit à la sortie');
        return $this->redirectToRoute('sortie_liste');
    }

    #[Route("/{id}/unregister",name: 'sortie_unregister', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function unRegisterSortie(int $id, FormAndShow $sortieService, SortieRepository $sortieRepository): Response
    {
        $sortie = $sortieRepository->find($id);
        $sortieService->unRegisterSortie($sortie);

        $this->addFlash('success','Vous êtes retiré de la sortie');
        return $this->redirectToRoute('sortie_liste');
    }

    #[Route('/{id}/cancel', name: 'sortie_cancel', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function cancelSortie(
        int              $id,
        Request          $request,
        FormAndShow      $sortieService,
        SortieRepository $sortieRepository,
        EtatManager      $etatService
    ): Response {
        $sortie = $sortieRepository->find($id);

        try {
            if ($sortie->getEtat()->getLibelle() === 'Clôturée') {
                throw new SortieAlreadyClosed();
            }

            $form = $this->createForm(CancelSortieType::class, $sortie);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $sortieService->cancelSortie($sortie, $sortie->getMotif());

                $this->addFlash('success', 'Vous avez annulé la sortie');
                return $this->redirectToRoute('sortie_liste');
            }
        } catch (SortieAlreadyClosed $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('sortie_liste');
        }

        return $this->render('sortie/cancel.html.twig', [
            'form' => $form->createView(),
            'sortie' => $sortie,
            'etatColor' => $etatService->etatColorDisplay($sortie),
        ]);
    }
}
