<?php

namespace App\Tests\Entity;

use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ClientTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testDisplayNameUsesRaisonSocialeForProfessionals(): void
    {
        $client = (new Client())->setRaisonSociale('Ma Société SARL')->setNom('Ignoré')->setPhone('0600000000');

        $this->assertSame('Ma Société SARL', $client->getDisplayName());
    }

    public function testDisplayNameFallsBackToNomPrenom(): void
    {
        $client = (new Client())->setNom('Dupont')->setPrenom('Jean')->setPhone('0600000000');

        $this->assertSame('Jean Dupont', $client->getDisplayName());
    }

    public function testClientCanBePersisted(): void
    {
        $client = (new Client())->setNom('Martin')->setPhone('0611111111');
        $this->em->persist($client);
        $this->em->flush();

        $this->assertNotNull($client->getId());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em->close();
    }
}
