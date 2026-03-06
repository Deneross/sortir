<?php

namespace App\Controller;

use App\Entity\Ville;
use App\Form\VilleType;
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
        Request $request,
        VilleRepository $villeRepository,
    ): JsonResponse
    {
        $villeId = $request->query->get('ville');
        $ville = $villeRepository->findOneBy(['id' => $villeId]);

        return $this->json([
            'codePostal' => $ville?->getCodePostal(),
        ]);
    }

    #[Route("/create", name: '_create')]
    #[IsGranted("ROLE_ADMIN")]
    public function create(Request $request, EntityManagerInterface $em): Response {
        $newVille = new Ville();
        $form = $this->createForm(VilleType::class, $newVille);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($newVille);
            $em->flush();

            $this->addFlash('success','Votre ville a été ajoutée à la liste');
            return $this->redirectToRoute('app_adminapp_adminanduser_index');
        }

        return $this->render('ville/create.html.twig', [
            'form' => $form
        ]);
    }
}
