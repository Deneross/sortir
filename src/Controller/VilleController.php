<?php

namespace App\Controller;

use App\Entity\Ville;
use App\Form\VilleType;
use App\Repository\CampusRepository;
use App\Repository\VilleRepository;
use App\Util\AdminPage\Factory;
use App\Util\AdminPage\MadeForVille;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/ville', name: 'app_ville')]
final class VilleController extends AbstractController
{
    #[Route('/cp', name: '_cp')]
    public function getCp(
        Request         $request,
        VilleRepository $villeRepository,
    ): JsonResponse
    {
        $villeId = $request->query->get('ville');
        $ville = $villeRepository->findOneBy(['id' => $villeId]);

        return $this->json([
            'codePostal' => $ville?->getCodePostal(),
        ]);
    }

    #[Route('/admin', name: '_admin', methods: ['GET', 'POST'])]
    #[IsGranted("ROLE_ADMIN")]
    public function index(
        Factory          $service,
        MadeForVille     $filters,
        VilleRepository  $villeRepo,
        CampusRepository $campusRepo,
        Request          $request,
    ): Response
    {
        //Initialisation de ma page
        $campus = $campusRepo->findAll();

        $villes = $filters->initVilleList($request);

        $campusFiltered = $filters->initCampus($request);
        $nameFiltered = $filters->initVilleName($request);
        $cpFiltered = $filters->initVilleCodePostal($request);

        /*************************** Partie Create **************************/
        //Initialisation des éléments du create
        $newVille = new Ville();
        $formCreate = $this->createForm(VilleType::class, $newVille);
        $formCreate->handleRequest($request);

        //Gestion de la création d'une ville
        if ($formCreate->isSubmitted() && $formCreate->isValid()) {
            $service->sendToBDDAndUpdateSessionList($newVille, $villes, $request, $filters);

            $this->addFlash('success', 'La ville a été ajoutée à la liste');
            return $this->redirectToRoute('app_ville_admin');
        }

        /*************************** Partie Update **************************/
        if ($request->request->has('ville_edit_id')) {
            //Ville que l'on veut modifier
            try {
                $ville = $service->foundEntity('ville_edit_id', $villeRepo, $request);
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage() . ' La ville n\'existe pas.');
                return $this->redirectToRoute('app_ville_admin');
            }

            //Nouvelle donnée de la ville
            $ville->setCampus($campusRepo->find($request->request->get('ville_campus')));
            $ville->setName($request->request->get('ville_name'));
            $ville->setCodePostal($request->request->get('ville_codePostal'));

            //Update de ville
            $service->sendToBDDAndUpdateSessionList($ville, $villes, $request, $filters);

            $this->addFlash('success', 'La ville a été mise à jour');
            return $this->redirectToRoute('app_ville_admin');
        }

        /*************************** Partie Delete **************************/
        if ($request->request->has('ville_delete_id')) {
            try {
                $ville = $service->foundEntity('ville_delete_id', $villeRepo, $request);
                try {
                    $service->deletingVille($ville, $villes, $request, $filters);
                    $this->addFlash('warning', 'La ville a été supprimée');
                    return $this->redirectToRoute('app_ville_admin');
                } catch (\Exception $e) {
                    $this->addFlash('error', $e->getMessage() . ' La suppression de la ville a été annulée.');
                }
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage() . ' La ville n\'existe pas.');
                return $this->redirectToRoute('app_ville_admin');
            }
        }

        /*************************** Partie Filtre **************************/
        if ($request->request->has('ville_filter') || $request->request->has('ville_reinit')) {
            $message = $filters->filterPage(
                $request,
                'ville_filter',
                'ville_reinit',
                'ville_filter_campus',
                'ville_filter_name',
                'ville_filter_codePostal'
            );

            $this->addFlash('secondary', $message);
            return $this->redirectToRoute('app_ville_admin');
        }


        /*************************** Standard de la page **************************/
        return $this->render('admin/ville/index.html.twig', [
            'villes' => $villes,
            'formCreate' => $formCreate,
            'campus' => $campus,
            'campusFiltered' => $campusFiltered,
            'nameFiltered' => $nameFiltered,
            'cpFiltered' => $cpFiltered,
        ]);
    }
}
