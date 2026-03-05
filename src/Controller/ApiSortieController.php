<?php

namespace App\Controller;

use App\Entity\Participant;
use App\SortieService\SortieSearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class ApiSortieController extends AbstractController
{
    #[Route('/sorties', name: 'api_sorties', methods: ['GET'])]
    public function list(Request $request, SortieSearchService $searchService): JsonResponse
    {
        /** @var Participant|null $user */
        $user = $this->getUser();

        $page  = max(1, $request->query->getInt('page', 1));
        $limit = max(1, $request->query->getInt('limit', 10));

        $filters = [
            'campus' => $request->query->get('campus'),
            'search' => $request->query->get('search'),
            'dateMin' => $request->query->get('dateMin'),
            'dateMax' => $request->query->get('dateMax'),
            'orga' => $request->query->getBoolean('orga'),
            'inscrit' => $request->query->getBoolean('inscrit'),
            'nonInscrit' => $request->query->getBoolean('nonInscrit'),
            'terminees' => $request->query->getBoolean('terminees'),
        ];

        $paginator = $searchService->searchPaginated($filters, $user, $page, $limit);

        $total = count($paginator);
        $pages = (int) ceil($total / $limit);

        $data = [];

        foreach ($paginator as $s) {
            $nbInscrits = $s->getInscrits()->count();
            $maxPlaces = $s->getNbInscriptionMax();
            $isFull = $nbInscrits >= $maxPlaces;

            $isUserInscrit = false;
            $isOrga = false;

            if ($user) {
                $isUserInscrit = $s->getInscrits()->contains($user);
                $isOrga = ($s->getOrganisateur()?->getId() === $user->getId());
            }

            $published = method_exists($s, 'isPublished')
                ? (bool) $s->isPublished()
                : (bool) $s->getPublished();

            $showUrl = $this->generateUrl('sortie_show', ['id' => $s->getId()]);
            $editUrl = $this->generateUrl('sortie_edit', ['id' => $s->getId()]);
            $orgaUrl = $this->generateUrl('app_participant_show', ['id' => $s->getOrganisateur()->getId()]);

            $actions = [];

            // Afficher (toujours)
            $actions[] = [
                'key' => 'show',
                'label' => 'Afficher',
                'class' => 'btn btn-group-sm btn-primary',
                'title' => 'Détail de la sortie',
                'href' => $showUrl,
                'method' => 'GET',
            ];

            // Actions organisateur
            if ($isOrga) {
                if ($published) {
                    $actions[] = [
                        'key' => 'cancel',
                        'label' => 'Annuler',
                        'class' => 'btn btn-group-sm btn-danger',
                        'title' => 'Annuler la sortie',
                        'href' => $showUrl, // TODO route annulation
                        'method' => 'GET',
                    ];
                } else {
                    $actions[] = [
                        'key' => 'edit',
                        'label' => 'Modifier',
                        'class' => 'btn btn-group-sm btn-success',
                        'title' => 'Modifier la sortie',
                        'href' => $editUrl,
                        'method' => 'GET',
                    ];

                    $actions[] = [
                        'key' => 'publish',
                        'label' => 'Publier',
                        'class' => 'btn btn-group-sm btn-success',
                        'title' => 'Publier la sortie',
                        'href' => $showUrl, // TODO route publication
                        'method' => 'GET',
                    ];

                    $actions[] = [
                        'key' => 'delete',
                        'label' => 'Supprimer',
                        'class' => 'btn btn-group-sm btn-danger',
                        'title' => 'Supprimer la sortie',
                        'href' => $showUrl, // TODO route suppression
                        'method' => 'GET',
                    ];
                }
            }

            // Inscription / désinscription (etatId == 2)
            $etatId = $s->getEtat()?->getId();
            if ((int) $etatId === 2 && $user) {
                if ($isUserInscrit) {
                    $actions[] = [
                        'key' => 'unsubscribe',
                        'label' => 'Se désister',
                        'class' => 'btn btn-group-sm btn-primary',
                        'title' => "Se désister de la sortie",
                        'href' => $showUrl, // TODO route désinscription
                        'method' => 'GET',
                    ];
                } else {
                    $actions[] = [
                        'key' => 'subscribe',
                        'label' => "S'inscrire",
                        'class' => 'btn btn-group-sm btn-primary',
                        'title' => "S'inscrire à la sortie",
                        'href' => $showUrl, // TODO route inscription
                        'method' => 'GET',
                    ];
                }
            }

            $data[] = [
                'id' => $s->getId(),
                'campusId' => $s->getCampus()->getId(),
                'campusName' => $s->getCampus()->getName(),
                'nom' => $s->getNom(),
                'dateHeureDebut' => $s->getDateHeureDebut()->format('Y-m-d H:i'),
                'dateLimiteInscription' => $s->getDateLimiteInscription()->format('Y-m-d'),

                'nbInscrits' => $nbInscrits,
                'nbInscriptionMax' => $maxPlaces,

                'etat' => $s->getEtat()?->getLibelle(),
                'etatId' => $etatId,

                'organisateurId' => $s->getOrganisateur()->getId(),
                'organisateurPseudo' => $s->getOrganisateur()->getPseudo(),
                'organisateurUrl' => $orgaUrl,

                'isUserInscrit' => $isUserInscrit,
                'isFull' => $isFull,
                'isOrga' => $isOrga,
                'published' => $published,

                'actions' => $actions,
            ];
        }

        return $this->json([
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'pages' => $pages,
                'total' => $total,
            ],
        ]);
    }
}
