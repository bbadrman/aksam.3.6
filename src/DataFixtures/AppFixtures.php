<?php

namespace App\DataFixtures;

use App\Entity\Compartenaire;
use App\Entity\Product;
use App\Entity\Team;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        foreach (['Véhicule', 'Prévoyance', 'Construction'] as $nom) {
            $manager->persist((new Product())->setNom($nom));
        }

        foreach (['Équipe Nord', 'Équipe Sud'] as $nom) {
            $manager->persist((new Team())->setNom($nom));
        }

        $manager->persist(
            (new Compartenaire())
                ->setNom('Partenaire Démo')
                ->setApiToken('demo-token-' . bin2hex(random_bytes(8)))
                ->setApiActif(true)
        );

        $manager->flush();
    }
}
