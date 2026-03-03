<?php

namespace App\DataFixtures;

use App\Entity\Etat;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class EtatFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $data = [
            1 => 'En création',
            2 => 'Ouverte',
            3 => 'Clôturée',
            4 => 'En cours',
            5 => 'Terminée',
            6 => 'Annulée',
            7=> 'Historisée',
            8=> 'INVALIDE',
        ];

        foreach ($data as $id => $libelle) {
            $etat = new Etat()->setId($id)->setLibelle($libelle);
            $manager->persist($etat);
            $this->addReference('etat_'.$id, $etat);
        }

        $manager->flush();
    }
}
