<?php

namespace App\Tests\Service;

use App\Entity\Prospect;
use App\Entity\Team;
use App\Entity\User;
use App\Service\ClientService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ClientServiceTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private ClientService $clientService;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->clientService = $container->get(ClientService::class);
    }

    public function testConvertFromProspectCreatesClientWithSameIdentity(): void
    {
        $team = new Team();
        $team->setNom('Équipe Test');

        $commercial = new User();
        $commercial->setUsername('commercial-test');
        $commercial->setNom('Commercial');
        $commercial->setPrenom('Test');
        $commercial->setPassword('hash-non-utilise');

        $prospect = new Prospect();
        $prospect->setNom('Leroy');
        $prospect->setPrenom('Sophie');
        $prospect->setPhone('0622222222');
        $prospect->setEmail('sophie@example.com');
        $prospect->setTeam($team);
        $prospect->setCommercial($commercial);

        // Prospect requiert un Product NOT NULL — on en crée un minimal ici.
        $product = new \App\Entity\Product();
        $product->setCode('test-produit')->setNom('Produit Test');
        $prospect->setProduct($product);

        $this->em->persist($team);
        $this->em->persist($commercial);
        $this->em->persist($product);
        $this->em->persist($prospect);
        $this->em->flush();

        $client = $this->clientService->convertFromProspect($prospect);

        $this->assertSame('Leroy', $client->getNom());
        $this->assertSame('Sophie', $client->getPrenom());
        $this->assertSame($team, $client->getTeam());
        $this->assertSame($commercial, $client->getCommercial());
        $this->assertSame($prospect, $client->getProspect());
        $this->assertSame(Prospect::STATUT_CONVERTI, $prospect->getStatut());
    }

    public function testCannotConvertAlreadyConvertedProspect(): void
    {
        $product = new \App\Entity\Product();
        $product->setCode('test-produit-2')->setNom('Produit Test 2');

        $prospect = new Prospect();
        $prospect->setNom('Déjà Converti');
        $prospect->setPhone('0633333333');
        $prospect->setProduct($product);

        $this->em->persist($product);
        $this->em->persist($prospect);
        $this->em->flush();

        $this->clientService->convertFromProspect($prospect);

        $this->expectException(\RuntimeException::class);
        $this->clientService->convertFromProspect($prospect);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em->close();
    }
}
