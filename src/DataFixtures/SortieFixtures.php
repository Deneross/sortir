<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use App\Entity\Etat;
use App\Entity\Lieu;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Entity\Ville;
use App\SortieService\EtatManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Validator\Constraints\Length;

class SortieFixtures extends Fixture implements DependentFixtureInterface
{

    public function __construct(
        private EtatManager $etatService,
    )
    {
    }

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

            $sortie->setNom($activite . ' à ' . $i);

            $dateDebut = $faker->dateTimeBetween("-1 month", "+2 month");
            $sortie->setDateHeureDebut(\DateTimeImmutable::createFromMutable($dateDebut));

            $sortie->setDuree($faker->numberBetween(30, 120));

            $dateLimite = $faker->dateTimeBetween('-2 month', $dateDebut->modify('-1 day'));
            $sortie->setDateLimiteInscription(\DateTimeImmutable::createFromMutable($dateLimite));

            $inscriptionMax = $faker->numberBetween(2, 200);
            $sortie->setNbInscriptionMax($inscriptionMax);
            $sortie->setInfosSortie($faker->text(200));

            $nbInscrits = $faker->numberBetween(0, $inscriptionMax);

            for ($j = 1; $j <= $nbInscrits; $j++) {
                $participant = $this->getReference('participant_' .
//                    $faker->numberBetween(1, 2)
                2
                    , Participant::class);
                $sortie->addInscrit($participant);
            }

            $sortie->setOrganisateur($this->getReference('organisateur_' . $faker->numberBetween(1, 2), Participant::class));

            $campusNames = [
                'SAINT-HERBLAIN',
                'CHARTRES DE BRETAGNE',
                'LA ROCHE SUR YON'
            ];

            $campus = $faker->randomElement($campusNames);
            $sortie->setCampus($this->getReference($campus, Campus::class));

            /**
             * Choix aléatoire entre les options annulé, archivé ou publié.
             * Calcul ensuite réel de l'état sinon j'ai des exceptions illogiques qui se produisent
             */
            $sortie->setPublished($faker->boolean(70));
            $sortie->setArchived($faker->boolean(10));
            $sortie->setCancel($faker->boolean(10));
            $this->etatService->settingEtat($sortie);

            $this->addReference('sortie' . $i, $sortie);

            $manager->persist($sortie);
        }
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [ParticipantFixtures::class, CampusFixtures::class, EtatFixtures::class];
    }
}
