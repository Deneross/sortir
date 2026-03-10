<?php

namespace App\Controller;

use App\Entity\Ville;
use App\Form\VilleFilterType;
use App\Form\VilleType;
use App\Repository\CampusRepository;
use App\Repository\LieuRepository;
use App\Repository\VilleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
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
        //Initialisation de mes listes
        $villes = $villeRepo->findAllVillesDesc();
        $campus = $campusRepo->findAll();

        /*************************** Partie Create **************************/
        //Initialisation des éléments du create
        $newVille = new Ville();
        $formCreate = $this->createForm(VilleType::class, $newVille);
        $formCreate->handleRequest($request);

        //Gestion de la création d'une ville
        if ($formCreate->isSubmitted() && $formCreate->isValid()) {
            $em->persist($newVille);
            $em->flush();

            return $this->redirectDeTurbo('Votre ville a été ajoutée à la liste', $request, $villeRepo, $campus);
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

            return $this->redirectDeTurbo('Votre ville a bien été mise à jour', $request, $villeRepo, $campus);
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
                return $this->redirectDeTurbo('Votre ville vient d\'être suprimée définitivement', $request, $villeRepo, $campus);
            } else {
                throw new \Exception('La ville est utilisé pour une sortie. Elle ne peut être supprimée', 403);
            }
        }

        /*************************** Partie Filtre **************************/
        if ($request->request->has('ville_filter')) {
            $filterCampus = $request->request->get('ville_filter_campus');
            $filterName = $request->request->get('ville_filter_name');
            $filterCodePostal = $request->request->get('ville_filter_codePostal');
            $villes = $villeRepo->findAllVillesDesc();
        }

        /*************************** Standard de la page **************************/
        return $this->render('ville/index.html.twig', [
            'villes' => $villes,
            'formCreate' => $formCreate->createView(),
            'campus' => $campus,
        ]);
    }

    private function redirectDeTurbo(string $successMsg, Request $request, VilleRepository $villeRepo, array $campus): Response
    {
        $this->addFlash('success', $successMsg);
        if ($request->headers->has('Turbo-Frame')) {
            return $this->render('ville/index.html.twig', [
                'villes' => $villeRepo->findAllVillesDesc(),
                'formCreate' => $this->createForm(VilleType::class, new Ville())->createView(),
                'campus' => $campus,
            ]);
        }
        return $this->redirectToRoute('app_ville_admin');
    }
}
