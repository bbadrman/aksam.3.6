<?php

namespace App\Tests\Service;

use App\Dto\ContratFormDTO;
use App\Entity\Client;
use App\Entity\ContratStatut;
use App\Entity\Product;
use App\Service\ContratService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ContratServiceTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private ContratService $contratService;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->contratService = $container->get(ContratService::class);
    }

    public function testCreateVehiculeContratPersistsVehiculeDetails(): void
    {
        $client = (new Client())->setNom('Petit')->setPhone('0600000000');
        $product = (new Product())->setCode(Product::CODE_VEHICULE)->setNom('Véhicule');
        $this->em->persist($client);
        $this->em->persist($product);
        $this->em->flush();

        $dto = new ContratFormDTO();
        $dto->client = $client;
        $dto->product = $product;
        $dto->cotisation = '450.00';
        $dto->immatriculation = 'GH-456-IJ';
        $dto->conducteur = 'Marie Petit';

        $contrat = $this->contratService->create($dto);

        $this->assertNotNull($contrat->getId());
        $this->assertSame(ContratStatut::BROUILLON, $contrat->getStatut());
        $this->assertNotNull($contrat->getVehiculeDetails());
        $this->assertSame('GH-456-IJ', $contrat->getVehiculeDetails()->getImmatriculation());
    }

    public function testCreateNonVehiculeContratHasNoVehiculeDetails(): void
    {
        $client = (new Client())->setNom('Sans Vehicule')->setPhone('0600000001');
        $product = (new Product())->setCode(Product::CODE_PREVOYANCE)->setNom('Prévoyance');
        $this->em->persist($client);
        $this->em->persist($product);
        $this->em->flush();

        $dto = new ContratFormDTO();
        $dto->client = $client;
        $dto->product = $product;

        $contrat = $this->contratService->create($dto);

        $this->assertNull($contrat->getVehiculeDetails());
    }

    public function testDuplicatePersistsANewContratWithoutSharingReferences(): void
    {
        $client = (new Client())->setNom('Duplique')->setPhone('0600000002');
        $product = (new Product())->setCode(Product::CODE_VEHICULE)->setNom('Véhicule 2');
        $this->em->persist($client);
        $this->em->persist($product);
        $this->em->flush();

        $dto = new ContratFormDTO();
        $dto->client = $client;
        $dto->product = $product;
        $dto->immatriculation = 'KL-789-MN';

        $original = $this->contratService->create($dto);
        $copie = $this->contratService->duplicate($original);

        $this->assertNotSame($original->getId(), $copie->getId());
        $this->assertNotNull($copie->getId());
        $this->assertSame('KL-789-MN', $copie->getVehiculeDetails()->getImmatriculation());
        $this->assertNotSame($original->getVehiculeDetails(), $copie->getVehiculeDetails());
    }

    public function testValiderAssignsNumeroPoliceAndChangesStatut(): void
    {
        $client = (new Client())->setNom('AValider')->setPhone('0600000003');
        $product = (new Product())->setCode('valider-test')->setNom('Test');
        $this->em->persist($client);
        $this->em->persist($product);
        $this->em->flush();

        $dto = new ContratFormDTO();
        $dto->client = $client;
        $dto->product = $product;
        $contrat = $this->contratService->create($dto);

        $this->contratService->valider($contrat);

        $this->assertSame(ContratStatut::VALIDE, $contrat->getStatut());
        $this->assertNotNull($contrat->getNumeroPolice());
        $this->assertStringStartsWith('POL-', $contrat->getNumeroPolice());
    }

    public function testCannotValiderAnAlreadyValidatedContrat(): void
    {
        $client = (new Client())->setNom('DejaValide')->setPhone('0600000004');
        $product = (new Product())->setCode('deja-valide-test')->setNom('Test');
        $this->em->persist($client);
        $this->em->persist($product);
        $this->em->flush();

        $dto = new ContratFormDTO();
        $dto->client = $client;
        $dto->product = $product;
        $contrat = $this->contratService->create($dto);
        $this->contratService->valider($contrat);

        $this->expectException(\RuntimeException::class);
        $this->contratService->valider($contrat);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em->close();
    }
}
