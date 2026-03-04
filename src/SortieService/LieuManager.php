<?php

namespace App\SortieService;

use App\Entity\Lieu;
use App\Entity\Participant;
use App\Entity\Sortie;
use Symfony\Component\Form\FormInterface;

class LieuManager
{
    public function createLieuFromSortie(FormInterface $form):Lieu{
        $newLieu = new Lieu();
        $newLieu->setName($form->get('lieuNom')->getData());
        $newLieu->setRue($form->get('lieuRue')->getData());
        $newLieu->setCodePostal($form->get('lieuCodePostal')->getData());
        $newLieu->setCoordonneesGps($form->get('lieuCoordonnees')->getData());
        return $newLieu;
    }

}
