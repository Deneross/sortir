<?php

namespace App\Controller;

use App\Entity\Sortie;
use App\Repository\SortieRepository;
use App\SortieService\Etat;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 10;

        $paginator = $sortieRepository->findLastEvents($page, $limit);

        $total = count($paginator);
        $pages = (int) ceil($total / $limit);

        return $this->render('sortie/list.html.twig', [
            //passe les sorties à twig pour affichage
            'sorties' => $paginator,
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
        ]);
    }

    #[Route('/{id}', name: 'sortie_show', requirements: ['id'=>'\d+'], methods: ['GET'])]
    public function show($id, SortieRepository $sortieRepository): Response
    {
        //va chercher la sotie dans la bdd en fonction de l'id
        $sortie = $sortieRepository ->find($id);

        return $this->render('sortie/show.html.twig',[
            //passe la sortie à twig pour affichage
            'sortie' => $sortie,
        ]);
    }

    #[Route('/creer', name: 'sortie_create', methods: ['GET', 'POST'])]
    public function create(): Response
    {
        //todo: traiter le formulaire d'ajout de sortie

        return $this->render('sortie/create.html.twig',[
            //todo:passer le formulaire à twig
        ]);
    }

    #[Route('/{id}/modifier', name: 'sortie_edit', requirements: ['id'=>'\d+'], methods: ['GET', 'POST'])]
    public function edit($id): Response
    {
        //todo:aller chercher la sortie à modifier dans la bdd

        //todo: traiter le formulaire de modification de sortie

        return $this->render('sortie/edit.html.twig',[
            //todo:passer le formulaire à twig
        ]);
    }
}
