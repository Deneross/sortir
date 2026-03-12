<?php

namespace App\Service;

use App\Entity\Ville;
use App\Mapper\PlaceGoogleMapper;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GooglePlacesService
{
    private string $apiKey;

    public function __construct(
        string                               $googlePlacesApiKey,
        private readonly HttpClientInterface $httpClient,
        private readonly PlaceGoogleMapper   $mapper

    )
    {
        $this->apiKey = $googlePlacesApiKey;
    }

    public function searchPlaces(string $recherche, string $ville): array
    {
        $searchText = $recherche . ' à ' . $ville . ', France';
        $response = $this->httpClient->request(
            'POST',
            'https://places.googleapis.com/v1/places:searchText',
            ['headers' => [
                'Content-Type' => 'application/json',
                'X-Goog-Api-Key' => $this->apiKey,
                'X-Goog-FieldMask' => 'places.displayName,places.formattedAddress,places.location'
            ],
                'json' => [
                    'textQuery' => $searchText,
                ]
            ]
        );
        $data = $response->toArray();

        $resultat = [];
        foreach ($data["places"] as $place) {
            $resultat[] = $this->mapper->map($place);
        }

        return $resultat;
    }
}
