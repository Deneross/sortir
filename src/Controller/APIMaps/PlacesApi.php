<?php

namespace App\Controller\APIMaps;


use App\Repository\VilleRepository;
use App\Service\GooglePlacesService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class PlacesApi extends AbstractController
{
    #[Route('sortie/api/places', methods: ['POST'])]
    public function search(
        Request             $request,
        GooglePlacesService $api,
    ): JsonResponse
    {
        $data = $request->toArray();

        $recherche = $data["recherche"];
        $ville = $data["ville"];

        if (!$recherche) {
            return $this->json([
                'error' => 'Il vous manque une indication de lieu à rechercher'
            ], 400);
        }
        if (!$ville) {
            return $this->json([
                'error' => 'Erreur dans l\'attribution d\'une ville pour la recherche'
            ], 400);
        }

        try {
            $resultat = $api->searchPlaces($recherche, $ville);
        } catch (\Exception $e) {
            throw new \Exception(
                'Une erreur est survenue avec l\'appel de l\'Api de recehrche ' . $e->getMessage(),
                $e->getCode());
        }
        return $this->json($resultat);
    }

}
