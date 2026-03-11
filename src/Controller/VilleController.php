<?php

namespace App\Controller;

use App\Entity\Ville;
use App\Form\VilleType;
use App\Repository\CampusRepository;
use App\Repository\LieuRepository;
use App\Repository\VilleRepository;
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
        VilleRepository        $villeRepo,
        CampusRepository       $campusRepo,
        LieuRepository         $lieuRepo,
        Request                $request,
        EntityManagerInterface $em
    ): Response
    {
        //Initialisation de ma page
        $campus = $campusRepo->findAll();

        $villes = $request->getSession()->get('lstVille');
        if (!$villes) {
            $request->getSession()->set('lstVille', $this->listeInitialeVilles($villeRepo));
        }

        $campusFiltered = $request->getSession()->get('campus');
        if (!$campusFiltered) {
            $this->initCampusSession($request, null);
        }

        $nameFiltered = $request->getSession()->get('name');
        if (!$nameFiltered) {
            $this->initNameSession($request, null);
        }

        $cpFiltered = $request->getSession()->get('codePostal');
        if (!$cpFiltered) {
            $this->initCodePostalSession($request, null);
        }

        dump($campusFiltered, $nameFiltered, $cpFiltered);

        /*************************** Partie Create **************************/
        //Initialisation des éléments du create
        $newVille = new Ville();
        $formCreate = $this->createForm(VilleType::class, $newVille);
        $formCreate->handleRequest($request);

        //Gestion de la création d'une ville
        if ($formCreate->isSubmitted() && $formCreate->isValid()) {
            $em->persist($newVille);
            $em->flush();

            $villes[$newVille->getId()] = $newVille;
            $request->getSession()->set('lstVille', $villes);

            $this->addFlash('success', 'La ville a été ajoutée à la liste');
            return $this->redirectToRoute('app_ville_admin');
        }

        /*************************** Partie Update **************************/
        if ($request->request->has('ville_edit_id')) {
            //Info de la ville que l'on a modifié
            $id = $request->request->get('ville_edit_id');
            $ville = $villeRepo->find($id);

            if (!$ville) {
                throw $this->createNotFoundException('La ville n\'existe pas');
            }

            //Nouvelle donnée de la ville
            $ville->setCampus($campusRepo->find($request->request->get('ville_campus')));
            $ville->setName($request->request->get('ville_name'));
            $ville->setCodePostal($request->request->get('ville_codePostal'));

            $em->persist($ville);
            $em->flush();

            $villes[$id] = $ville;
            $request->getSession()->set('lstVille', $villes);

            $this->addFlash('success', 'La ville a été lise à jour');
            return $this->redirectToRoute('app_ville_admin');
        }

        /*************************** Partie Delete **************************/
        if ($request->request->has('ville_delete_id')) {
            //Quelle ville est concernée par la suppression
            $id = $request->request->get('ville_delete_id');
            $ville = $villeRepo->find($id);

            if (!$ville) {
                throw $this->createNotFoundException('La ville n\'existe pas');
            }

            //Check de BDD pour éviter les sorties orphelines
            if ($lieuRepo->canVilleBeDeleted($id)) {
                $em->remove($ville);
                $em->flush();

                unset($villes[$id]);
                $request->getSession()->set('lstVille', $villes);

                $this->addFlash('warning', 'La ville a été supprimée');
                return $this->redirectToRoute('app_ville_admin');
            } else {
                throw new \Exception('La ville est utilisé pour une sortie. Elle ne peut être supprimée', 403);
            }
        }

        /*************************** Partie Filtre **************************/
        if ($request->request->has('ville_filter') || $request->request->has('ville_reinit')) {
            $campusForFilter = null;
            $nameForFilter = null;
            $codePostalForFilter = null;
            $lstVillesFiltered = [];

            $successMsg = "Liste réinitialisée";

            //Appliquer un filtre
            if ($request->request->has('ville_filter')) {
                $campusForFilter = $request->request->get('ville_filter_campus');
                $nameForFilter = $request->request->get('ville_filter_name');
                $codePostalForFilter = $request->request->get('ville_filter_codePostal');

                $villesFiltered = $villeRepo->findVilleWithFilters($campusForFilter, $nameForFilter, $codePostalForFilter);
                foreach ($villesFiltered as $ville) {
                    $lstVillesFiltered[$ville->getId()] = $ville;
                }

                $successMsg = "Listre filtrée";

            } //Réinitialiser la page
            elseif ($request->request->has('ville_reinit')) {
                $lstVillesFiltered = $this->listeInitialeVilles($villeRepo);
            }

            //Résultat des filtres
            $request->getSession()->remove('lstVille');
            $request->getSession()->set('lstVille', $lstVillesFiltered);

            $request->getSession()->remove('campus');
            $this->initCampusSession($request, $campusForFilter);

            $request->getSession()->remove('name');
            $this->initNameSession($request, $nameForFilter);

            $request->getSession()->remove('codePostal');
            $this->initCodePostalSession($request, $codePostalForFilter);

            $this->addFlash('secondary', $successMsg);
            return $this->redirectToRoute('app_ville_admin');
        }


        /*************************** Standard de la page **************************/
        return $this->render('ville/index.html.twig', [
            'villes' => $villes,
            'formCreate' => $formCreate,
            'campus' => $campus,
            'campusFiltered' => $campusFiltered,
            'nameFiltered' => $nameFiltered,
            'cpFiltered' => $cpFiltered,
        ]);
    }

    private function listeInitialeVilles(VilleRepository $repo): array
    {
        $lstVilles = $repo->findAllVillesDesc();

        foreach ($lstVilles as $ville) {
            $villes[$ville->getId()] = $ville;
        }
        return $villes;
    }

    private function initCampusSession(Request $request, ?string $campus): void
    {
        $request->getSession()->set('campus', $campus ?? "-1");

    }

    private function initNameSession(Request $request, ?string $name): void
    {
        $request->getSession()->set('name', $name ?? "");
    }

    private function initCodePostalSession(Request $request, ?string $codePostal): void
    {
        $request->getSession()->set('codePostal', $codePostal ?? "");
    }
}
