<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use App\Entity\Sortie;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Validator\Constraints\Length;

class SortieFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = \Faker\Factory::create('fr_FR');

        $activites = [
            'Randonnée',
            'Bowling',
            'Cinéma',
            'Restaurant',
            'Escape Game',
            'Soirée Jeux',
            'Karting',
            'Laser Game',
        ];


        for ($i = 1; $i <= 30; $i++) {
            $sortie = new Sortie();

            $activite = $faker->randomElement($activites);

            $sortie->setNom($activite . ' à ' . $faker->city());

            $dateDebut = $faker->dateTimeBetween("-1 month", "+2 month");
            $sortie->setDateHeureDebut(\DateTimeImmutable::createFromMutable($dateDebut));

            $sortie->setDuree($faker->numberBetween(30, 120));

            $dateLimite = $faker->dateTimeBetween('-2 month', $dateDebut->modify('-1 day'));
            $sortie->setDateLimiteInscription(\DateTimeImmutable::createFromMutable($dateLimite));
            $sortie->setNbInscriptionMax($faker->numberBetween(2, 200));
            $sortie->setInfosSortie($faker->text(200));
            $sortie->setPublished($faker->boolean(70));

            $manager->persist($sortie);
        }
        $manager->flush();
    }
}
