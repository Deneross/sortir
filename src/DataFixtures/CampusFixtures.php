<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CampusFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $exCampus = ['SAINT-HERBLAIN', 'CHARTRES DE BRETAGNE', 'LA ROCHE SUR YON'];

        foreach ($exCampus as $campus){
            $newCampus = new Campus();
            $newCampus->setName($campus);

            $manager->persist($newCampus);
            $this->addReference("$campus", $newCampus);
        }

        $manager->flush();
    }
}
