<?php

namespace App\Util\AdminPage;

use App\Repository\VilleRepository;
use Symfony\Component\HttpFoundation\Request;

class MadeForVille extends Filters
{
    private const string SESSION_FILTER_NAME_CAMPUS = 'filter_ville_with_campus';
    private const string SESSION_FILTER_NAME_VILLE_NAME = 'filter_ville_with_name';
    private const string SESSION_FILTER_NAME_VILLE_CP = 'filter_ville_with_code_postal';

    public function __construct(
        private readonly VilleRepository $repo,
        private readonly string          $defaultCampus = "-1",
    )
    {
        parent::__construct();
        $this->nomListSession .= '_ville';
    }

    /********* Fonctions au chargement de la page ********/
    public function initVilleList(Request $request): array
    {
        return $this->initListPage($request, $this->villeList());
    }

    public function initCampus(Request $request): string
    {
        $campus = $request->getSession()->get(self::SESSION_FILTER_NAME_CAMPUS);
        if (!$campus) {
            $request->getSession()->set(self::SESSION_FILTER_NAME_CAMPUS, $this->defaultCampus);
            $campus = $this->defaultCampus;
        }
        return $campus;
    }

    public function initVilleName(Request $request): string
    {
        return $this->initInputFilter($request, self::SESSION_FILTER_NAME_VILLE_NAME);
    }

    public function initVilleCodePostal(Request $request): string
    {
        return $this->initInputFilter($request, self::SESSION_FILTER_NAME_VILLE_CP);
    }

    /********* Fonction à l'utilisation des filtres ********/
    public function filterPage(Request $request, string $keyToFilter, string $keyToReinit, string $keyCampus, string $keyName, string $keyCp): string
    {
        $lstVillesFiltered = [];
        $campusForFilter = null;
        $nameForFilter = null;
        $codePostalForFilter = null;
        $messageToReturn = "Une erreur s'est produite à l'utilisation des filtres";

        if ($request->request->has($keyToFilter)) {
            $campusForFilter = $request->request->get($keyCampus);
            $nameForFilter = $request->request->get($keyName);
            $codePostalForFilter = $request->request->get($keyCp);

            $villesFiltered = $this->repo->findVilleWithFilters($campusForFilter, $nameForFilter, $codePostalForFilter);
            foreach ($villesFiltered as $ville) {
                $lstVillesFiltered[$ville->getId()] = $ville;
            }

            $messageToReturn = "Votre liste est à présent filtrée";
        }

        if ($request->request->has($keyToReinit)) {
            $campusForFilter = $this->defaultCampus;
            $nameForFilter = $this->defaultInput;
            $codePostalForFilter = $this->defaultInput;

            $lstVillesFiltered = $this->villeList();

            $messageToReturn = 'Voici toutes les villes disponibles';
        }

        $this->updatingFilters($request, $lstVillesFiltered, $campusForFilter, $nameForFilter, $codePostalForFilter);
        return $messageToReturn;
    }

    /********* Fonctions utiles ********/
    private function updatingFilters(Request $request, array $liste, string $campus, string $name, string $codePostal): void
    {
        $session = $request->getSession();

        $session->remove($this->getNomListSession());
        $session->remove(self::SESSION_FILTER_NAME_CAMPUS);
        $session->remove(self::SESSION_FILTER_NAME_VILLE_NAME);
        $session->remove(self::SESSION_FILTER_NAME_VILLE_CP);

        $session->set($this->getNomListSession(), $liste);
        $session->set(self::SESSION_FILTER_NAME_CAMPUS, $campus);
        $session->set(self::SESSION_FILTER_NAME_VILLE_NAME, $name);
        $session->set(self::SESSION_FILTER_NAME_VILLE_CP, $codePostal);
    }

    private function villeList(): array
    {
        $liste = $this->repo->findAllVillesDesc();
        $villes = [];

        foreach ($liste as $ville) {
            $villes[$ville->getId()] = $ville;
        }

        return $villes;
    }

    public function getNomListSession(): string
    {
        return $this->nomListSession;
    }



}
