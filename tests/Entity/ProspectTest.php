<?php

namespace App\Tests\Entity;

use App\Entity\Product;
use App\Entity\Prospect;
use App\Entity\ProspectPrevoyanceDetails;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ProspectTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testProspectCanBePersistedWithExtensionDetails(): void
    {
        $product = (new Product())->setCode(Product::CODE_PREVOYANCE)->setNom('Prévoyance');
        $this->em->persist($product);

        $prospect = new Prospect();
        $prospect->setNom('Martin');
        $prospect->setPhone('0611111111');
        $prospect->setProduct($product);
        $prospect->setPrevoyanceDetails(
            (new ProspectPrevoyanceDetails())
                ->setCapitalSouhaite('50000.00')
                ->setSituationFamiliale('Marié')
        );

        $this->em->persist($prospect);
        $this->em->flush();
        $this->em->clear();

        $reloaded = $this->em->getRepository(Prospect::class)->find($prospect->getId());
        $this->assertSame(Prospect::STATUT_NOUVEAU, $reloaded->getStatut());
        $this->assertNotNull($reloaded->getPrevoyanceDetails());
        $this->assertSame('50000.00', $reloaded->getPrevoyanceDetails()->getCapitalSouhaite());
    }

    public function testFindARelancerExcludesConvertedAndLostProspects(): void
    {
        $product = (new Product())->setCode(Product::CODE_VEHICULE)->setNom('Véhicule');
        $this->em->persist($product);

        $past = new \DateTimeImmutable('-1 day');

        $aRelancer = (new Prospect())->setNom('A relancer')->setPhone('0600000001')->setProduct($product);
        $aRelancer->setRelanceAt($past);

        $converti = (new Prospect())->setNom('Converti')->setPhone('0600000002')->setProduct($product);
        $converti->setRelanceAt($past);
        $converti->setStatut(Prospect::STATUT_CONVERTI);

        $this->em->persist($aRelancer);
        $this->em->persist($converti);
        $this->em->flush();

        $resultats = static::getContainer()->get(\App\Repository\ProspectRepository::class)
            ->findARelancer(new \DateTimeImmutable());

        $noms = array_map(fn (Prospect $p) => $p->getNom(), $resultats);

        $this->assertContains('A relancer', $noms);
        $this->assertNotContains('Converti', $noms);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em->close();
    }
}
