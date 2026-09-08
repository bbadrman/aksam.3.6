<?php

namespace App\Tests\Service;

use App\Entity\Client;
use App\Entity\Contrat;
use App\Entity\Product;
use App\Entity\SavStatus;
use App\Entity\User;
use App\Service\SavService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SavServiceTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private SavService $savService;
    private Contrat $contrat;
    private User $auteur;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->savService = $container->get(SavService::class);

        $client = (new Client())->setNom('SavTest')->setPhone('0600000000');
        $product = (new Product())->setCode('sav-test-'.uniqid())->setNom('Test SAV');
        $this->contrat = (new Contrat())->setClient($client)->setProduct($product);

        $this->auteur = new User();
        $this->auteur->setUsername('auteur-sav-'.uniqid());
        $this->auteur->setNom('Test');
        $this->auteur->setPrenom('Auteur');
        $this->auteur->setPassword('hash-non-utilise');

        $this->em->persist($client);
        $this->em->persist($product);
        $this->em->persist($this->contrat);
        $this->em->persist($this->auteur);
        $this->em->flush();
    }

    public function testFullLifecycleOuvertureRelanceCloture(): void
    {
        $sav = $this->savService->ouvrir($this->contrat, 'Résiliation', 'Le client souhaite résilier', '150.00');
        $this->assertSame(SavStatus::OUVERT, $sav->getStatut());

        $this->savService->ajouterRelance($sav, 'Premier contact', new \DateTimeImmutable('+2 days'), $this->auteur);
        $this->assertSame(SavStatus::TRAITE, $sav->getStatut());
        $this->assertCount(1, $sav->getRelanceHistory());

        $this->savService->cloturer($sav);
        $this->assertSame(SavStatus::CLOTURE, $sav->getStatut());
        $this->assertNotNull($sav->getClosedAt());
    }

    public function testCannotRelancerAClosedSav(): void
    {
        $sav = $this->savService->ouvrir($this->contrat, 'Test', null, null);
        $this->savService->cloturer($sav);

        $this->expectException(\RuntimeException::class);
        $this->savService->ajouterRelance($sav, 'Trop tard', null, $this->auteur);
    }

    public function testCannotClotureAnAlreadyClosedSav(): void
    {
        $sav = $this->savService->ouvrir($this->contrat, 'Test', null, null);
        $this->savService->cloturer($sav);

        $this->expectException(\RuntimeException::class);
        $this->savService->cloturer($sav);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em->close();
    }
}
