<?php

namespace App\Util\AdminPage;

use App\Repository\CampusRepository;
use Symfony\Component\HttpFoundation\Request;

class MadeForCampus extends Filters
{
    private const string SESSION_FILTER_NAME_CAMPUS_NAME = 'filter_campus_with_name';

    public function __construct(
        private readonly CampusRepository $repo,
    )
    {
        parent::__construct();
        $this->nomListSession .= '_campus';
    }

    /********* Fonctions au chargement de la page ********/
    public function initCampusList(Request $request): array
    {
        return $this->initListPage($request, $this->campusList());
    }

    public function initCampusName(Request $request): string
    {
        return $this->initInputFilter($request, self::SESSION_FILTER_NAME_CAMPUS_NAME);
    }


    /********* Fonction à l'utilisation des filtres ********/
    public function filterPage(Request $request, string $keyToFilter, string $keyToReinit, string $keyName): string
    {
        $lstCampusFiltered = [];
        $nameForFilter = null;
        $messageToReturn = "Une erreur s'est produite à l'utilisation des filtres";

        if ($request->request->has($keyToFilter)) {
            $nameForFilter = $request->request->get($keyName);

            $campusFiltered = $this->repo->findCampusWithFilters($nameForFilter);
            foreach ($campusFiltered as $campus) {
                $lstCampusFiltered[$campus->getId()] = $campus;
            }

            $messageToReturn = "Votre liste est à présent filtrée";
        }

        if ($request->request->has($keyToReinit)) {
            $nameForFilter = $this->defaultInput;

            $lstCampusFiltered = $this->campusList();

            $messageToReturn = 'Voici tous les campus disponibles';
        }

        $this->updatingFilters($request, $lstCampusFiltered, $nameForFilter);
        return $messageToReturn;
    }

    /********* Fonctions utiles ********/
    private function updatingFilters(Request $request, array $liste, string $name): void
    {
        $session = $request->getSession();

        $session->remove($this->getNomListSession());
        $session->remove(self::SESSION_FILTER_NAME_CAMPUS_NAME);

        $session->set($this->getNomListSession(), $liste);
        $session->set(self::SESSION_FILTER_NAME_CAMPUS_NAME, $name);
    }

    private function campusList(): array
    {
        $liste = $this->repo->findAll();
        $lstCampus = [];

        foreach ($liste as $campus) {
            $lstCampus[$campus->getId()] = $campus;
        }

        return $lstCampus;
    }

    public function getNomListSession(): string
    {
        return $this->nomListSession;
    }


}
