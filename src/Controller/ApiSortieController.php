<?php

namespace App\Controller;

use App\Entity\Participant;
use App\SortieService\SortieSearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/api')]
final class ApiSortieController extends AbstractController
{
    public function __construct(private CsrfTokenManagerInterface $csrf) {}

    #[Route('/sorties', name: 'api_sorties', methods: ['GET'])]
    public function list(Request $request, SortieSearchService $searchService): JsonResponse
    {
        /** @var Participant|null $user */
        $user = $this->getUser();

        $page = max(1, $request->query->getInt('page', 1));
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
        $pages = (int)ceil($total / $limit);

        $data = [];

        foreach ($paginator as $s) {
            $now = new \DateTimeImmutable();

            $nbInscrits = $s->getInscrits()->count();
            $maxPlaces = $s->getNbInscriptionMax();
            $isFull = $nbInscrits >= $maxPlaces;

            $isUserInscrit = $user ? $s->getInscrits()->contains($user) : false;
            $isOrga = $user ? ($s->getOrganisateur()?->getId() === $user->getId()) : false;

            $showUrl = $this->generateUrl('sortie_show', ['id' => $s->getId()]);
            $editUrl = $this->generateUrl('sortie_edit', ['id' => $s->getId()]);
            $publishUrl = $this->generateUrl('sortie_publish', ['id' => $s->getId()]);
            $registerUrl = $this->generateUrl('sortie_register', ['id' => $s->getId()]);
            $unRegisterUrl = $this->generateUrl('sortie_unregister', ['id' => $s->getId()]);
            $cancelUrl = $this->generateUrl('sortie_cancel', ['id' => $s->getId()]);
            $orgaUrl = $this->generateUrl('app_participant_show', ['id' => $s->getOrganisateur()->getId()]);

            $etatLibelle = mb_strtolower($s->getEtat()?->getLibelle() ?? '');

            $isCreation = $etatLibelle === 'en création';
            $isOuverte = $etatLibelle === 'ouverte';
            $isAnnulee = $etatLibelle === 'annulée';

            $hasStarted = $s->getDateHeureDebut() <= $now;
            $deadlinePassed = $s->getDateLimiteInscription() < $now;

            $actions = [
                [
                    'key' => 'show',
                    'label' => 'Afficher',
                    'class' => 'btn btn-group-sm btn-primary',
                    'title' => 'Détail de la sortie',
                    'href' => $showUrl,
                    'method' => 'GET',
                ]
            ];

            if (!$isAnnulee) {
                if ($isOrga && $isCreation) {
                    $actions[] = [
                        'key' => 'edit',
                        'label' => 'Modifier',
                        'class' => 'btn btn-group-sm btn-success',
                        'href' => $editUrl,
                        'method' => 'GET',
                    ];

                    $actions[] = [
                        'key' => 'publish',
                        'label' => 'Publier',
                        'class' => 'btn btn-group-sm btn-success',
                        'title' => 'Publier la sortie',
                        'href' => $publishUrl,
                        'method' => 'POST',
                        'csrf' => $this->csrf->getToken('sortie_publish'.$s->getId())->getValue(),
                    ];
                }

                if ($user && $isOuverte && !$deadlinePassed && !$hasStarted && !$isFull && !$isUserInscrit) {
                    $actions[] = [
                        'key' => 'register',
                        'label' => "S'inscrire",
                        'class' => 'btn btn-group-sm btn-primary',
                        'href' => $registerUrl,
                        'method' => 'POST',
                        'csrf' => $this->csrf->getToken('sortie_register'.$s->getId())->getValue(),
                    ];
                }

                if ($user && $isUserInscrit && !$hasStarted) {
                    $actions[] = [
                        'key' => 'unregister',
                        'label' => 'Se désister',
                        'class' => 'btn btn-group-sm btn-primary',
                        'href' => $unRegisterUrl,
                        'method' => 'POST',
                        'csrf' => $this->csrf->getToken('sortie_unregister'.$s->getId())->getValue(),
                    ];
                }

                if ($isOrga && !$isCreation && !$hasStarted) {
                    $actions[] = [
                        'key' => 'cancel',
                        'label' => 'Annuler',
                        'class' => 'btn btn-group-sm btn-danger',
                        'href' => $cancelUrl,
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
                'organisateurId' => $s->getOrganisateur()->getId(),
                'organisateurPseudo' => $s->getOrganisateur()->getPseudo(),
                'organisateurUrl' => $orgaUrl,
                'isUserInscrit' => $isUserInscrit,
                'isFull' => $isFull,
                'isOrga' => $isOrga,
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
