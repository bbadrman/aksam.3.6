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
        $products = [
            Product::CODE_VEHICULE => 'Véhicule',
            Product::CODE_PREVOYANCE => 'Prévoyance',
            Product::CODE_CONSTRUCTION => 'Construction',
        ];

        $productEntities = [];
        foreach ($products as $code => $nom) {
            $product = (new Product())->setCode($code)->setNom($nom);
            $manager->persist($product);
            $productEntities[$code] = $product;
        }

        foreach (['Équipe Nord', 'Équipe Sud'] as $nom) {
            $manager->persist((new Team())->setNom($nom));
        }

        $manager->persist(
            (new Compartenaire())
                ->setNom('Partenaire Démo Véhicule')
                ->setApiToken('demo-token-' . bin2hex(random_bytes(8)))
                ->setApiActif(true)
                ->setProduct($productEntities[Product::CODE_VEHICULE])
        );

        $manager->flush();
    }
}
