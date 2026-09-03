<?php

namespace App\Tests\Entity;

use App\Entity\Compartenaire;
use App\Entity\Product;
use App\Entity\Team;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ReferentielTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testProductCrud(): void
    {
        $product = (new Product())->setNom('Vehicule')->setDescription('Assurance auto');
        $this->em->persist($product);
        $this->em->flush();

        $this->assertNotNull($product->getId());
        $this->assertSame('Vehicule', (string) $product);
    }

    public function testCompartenaireApiTokenLookup(): void
    {
        $partenaire = (new Compartenaire())
            ->setNom('Partenaire Test')
            ->setApiToken('token-unique-test')
            ->setApiActif(true);
        $this->em->persist($partenaire);
        $this->em->flush();

        $repo = $this->em->getRepository(Compartenaire::class);
        $found = $repo->findOneByApiToken('token-unique-test');

        $this->assertNotNull($found);
        $this->assertSame('Partenaire Test', $found->getNom());
    }

    public function testCompartenaireInactiveTokenIsNotFound(): void
    {
        $partenaire = (new Compartenaire())
            ->setNom('Partenaire Inactif')
            ->setApiToken('token-inactif')
            ->setApiActif(false);
        $this->em->persist($partenaire);
        $this->em->flush();

        $repo = $this->em->getRepository(Compartenaire::class);
        $found = $repo->findOneByApiToken('token-inactif');

        $this->assertNull($found);
    }

    public function testUserCanBelongToMultipleTeams(): void
    {
        $teamNord = (new Team())->setNom('Nord');
        $teamSud = (new Team())->setNom('Sud');

        $user = new User();
        $user->setUsername('chef-multi-equipe');
        $user->setNom('Chef');
        $user->setPrenom('Equipe');
        $user->setPassword('hash-non-utilise-ici');
        $user->addTeam($teamNord);
        $user->addTeam($teamSud);

        $this->em->persist($teamNord);
        $this->em->persist($teamSud);
        $this->em->persist($user);
        $this->em->flush();
        $this->em->clear();

        $reloaded = $this->em->getRepository(User::class)->findOneBy(['username' => 'chef-multi-equipe']);

        $this->assertCount(2, $reloaded->getTeams());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em->close();
    }
}
