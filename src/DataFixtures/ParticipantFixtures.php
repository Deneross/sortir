<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use App\Entity\Participant;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ParticipantFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = \Faker\Factory::create('fr_FR');

        $campusDispo = [
            $this->getReference('SAINT-HERBLAIN', Campus::class),
            $this->getReference('CHARTRES DE BRETAGNE', Campus::class),
            $this->getReference('LA ROCHE SUR YON', Campus::class)
        ];

        //L'admin par défaut disponible pour tester
        $admin = new Participant();
        $admin->setPseudo('admin');
        $admin->setPassword(password_hash('Admin@123', PASSWORD_DEFAULT));
        $admin->setNom('Admi');
        $admin->setPrenom('Nistrateur');
        $admin->setTelephone('0102030405');
        $admin->setMail('admin@admin.com');
        $admin->setCampus($campusDispo[0]);
        $admin->setRoles(['ROLE_ADMIN']);

        $manager->persist($admin);

        //Un utilisateur par défaut disponible pour tester
        $user = new Participant();
        $user->setPseudo('participant');
        $user->setPassword(password_hash('Participant@123', PASSWORD_DEFAULT));
        $user->setNom('Parti');
        $user->setPrenom('Cipant');
        $user->setTelephone('0102030405');
        $user->setMail('participant@participant.com');
        $user->setCampus($campusDispo[1]);

        $manager->persist($user);

        //Un utilisateur inactif
        $inactif = new Participant();
        $inactif->setPseudo('inactif');
        $inactif->setPassword(password_hash('Inactif@123', PASSWORD_DEFAULT));
        $inactif->setNom('Ina');
        $inactif->setPrenom('Ctif');
        $inactif->setTelephone('0102030405');
        $inactif->setMail('inactif@inactif.com');
        $inactif->setCampus($campusDispo[2]);
        $inactif->setActif(false);

        $manager->persist($inactif);

        for ($i = 0; $i < 10; $i++) {
            $participant = new Participant();
            $participant->setPseudo($faker->userName());
            $participant->setPassword(password_hash(($faker->password(8).'@'.rand(0,9)), PASSWORD_DEFAULT));
            $participant->setNom($faker->lastName());
            $participant->setPrenom($faker->firstName());
            $participant->setTelephone($faker->phoneNumber());
            $participant->setMail($faker->email());
            $participant->setCampus($campusDispo[rand(0, count($campusDispo) - 1)]);

            $manager->persist($participant);
            $this->addReference('participant_' . $i, $participant);
            $this->addReference('organisateur_' . $i, $participant);


        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [CampusFixtures::class];
    }
}
