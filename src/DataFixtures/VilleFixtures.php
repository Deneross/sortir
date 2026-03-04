<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use App\Entity\Ville;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class VilleFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly ParameterBagInterface $param,
    )
    {
    }

    public function load(ObjectManager $manager): void
    {
        $campusDispo = [
            '44'=>$this->getReference('SAINT-HERBLAIN', Campus::class),
            '35'=>$this->getReference('CHARTRES DE BRETAGNE', Campus::class),
            '85'=>$this->getReference('LA ROCHE SUR YON', Campus::class)
        ];

        foreach ($campusDispo as $idCampus => $campus) {
            $pathFichierJson = $this->buildPath($idCampus);
            $data = json_decode(file_get_contents($pathFichierJson), true);

            foreach ($data as $info) {
                $ville = new Ville();
                $ville->setCodePostal($info['codesPostaux'][0]);
                $ville->setName($info['nom']);
                $ville->setCampus($campus);

                $manager->persist($ville);
            }
        }
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [CampusFixtures::class];
    }

    private function buildPath(string $idCampus): string{
        return $this->param->get('kernel.project_dir').'\src\DataFixtures\data\response'.$idCampus.'.json';
    }
}
