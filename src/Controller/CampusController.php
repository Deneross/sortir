<?php

namespace App\Controller;

use App\Entity\Campus;
use App\Form\CampusType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/campus', name: 'app_campus')]
#[IsGranted('ROLE_ADMIN')]
final class CampusController extends AbstractController
{
    #[Route('/create', name: '_create')]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        $newCampus = new Campus();
        $form = $this->createForm(CampusType::class, $newCampus);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $em->persist($newCampus);
            $em->flush();

            $this->addFlash('success','Votre campus est créé. N\'oubliez pas d\'y ajouter vos participants pour qu\'ils puissent créer des sorties.');
            return $this->redirectToRoute('app_adminapp_adminanduser_index');
        }

        return $this->render('campus/create.html.twig', [
            'form' => $form
        ]);
    }
}
