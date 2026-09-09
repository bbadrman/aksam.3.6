<?php

namespace App\Tests\Service;

use App\Dto\ProspectFormDTO;
use App\Entity\Product;
use App\Entity\Team;
use App\Entity\User;
use App\Service\ProspectService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ProspectServiceManualCreationTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private ProspectService $prospectService;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->prospectService = $container->get(ProspectService::class);
    }

    public function testCreateManuallyRoutesVehiculeFieldsAndSetsAffectAt(): void
    {
        $product = (new Product())->setCode(Product::CODE_VEHICULE)->setNom('Véhicule');
        $team = (new Team())->setNom('Équipe manuelle test');
        $commercial = new User();
        $commercial->setUsername('commercial-manuel-test');
        $commercial->setNom('Test');
        $commercial->setPrenom('Commercial');
        $commercial->setPassword('hash-non-utilise');

        $this->em->persist($product);
        $this->em->persist($team);
        $this->em->persist($commercial);
        $this->em->flush();

        $dto = new ProspectFormDTO();
        $dto->nom = 'Manuel';
        $dto->prenom = 'Creation';
        $dto->phone = '0600000000';
        $dto->product = $product;
        $dto->team = $team;
        $dto->commercial = $commercial;
        $dto->immatriculation = 'MM-111-NN';
        $dto->marque = 'Peugeot';

        $prospect = $this->prospectService->createManually($dto);

        $this->assertNotNull($prospect->getId());
        $this->assertSame($team, $prospect->getTeam());
        $this->assertSame($commercial, $prospect->getCommercial());
        $this->assertNotNull($prospect->getAffectAt());
        $this->assertNotNull($prospect->getVehiculeDetails());
        $this->assertSame('MM-111-NN', $prospect->getVehiculeDetails()->getImmatriculation());
        $this->assertNull($prospect->getPrevoyanceDetails());
    }

    public function testCreateManuallyWithoutTeamOrCommercialLeavesAffectAtNull(): void
    {
        $product = (new Product())->setCode(Product::CODE_PREVOYANCE)->setNom('Prévoyance');
        $this->em->persist($product);
        $this->em->flush();

        $dto = new ProspectFormDTO();
        $dto->nom = 'SansAffectation';
        $dto->phone = '0600000001';
        $dto->product = $product;
        $dto->situationFamiliale = 'Marié';

        $prospect = $this->prospectService->createManually($dto);

        $this->assertNull($prospect->getAffectAt());
        $this->assertNotNull($prospect->getPrevoyanceDetails());
        $this->assertSame('Marié', $prospect->getPrevoyanceDetails()->getSituationFamiliale());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em->close();
    }
}
