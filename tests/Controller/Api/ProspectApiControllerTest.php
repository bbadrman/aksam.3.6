<?php

namespace App\Tests\Controller\Api;

use App\Entity\Compartenaire;
use App\Entity\Product;
use App\Entity\Prospect;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProspectApiControllerTest extends WebTestCase
{
    private EntityManagerInterface $em;

    private function em(): EntityManagerInterface
    {
        return $this->em ??= static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testCreateProspectRequiresToken(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/prospects', server: ['CONTENT_TYPE' => 'application/json'], content: '{}');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateProspectWithInvalidTokenIsRejected(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/prospects',
            server: ['CONTENT_TYPE' => 'application/json', 'HTTP_X_API_TOKEN' => 'token-inexistant'],
            content: '{}'
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateProspectRoutesToVehiculeDetails(): void
    {
        $client = static::createClient();

        $product = (new Product())->setCode(Product::CODE_VEHICULE)->setNom('Véhicule');
        $partenaire = (new Compartenaire())
            ->setNom('Site Auto Test')
            ->setApiToken('token-vehicule-test')
            ->setApiActif(true)
            ->setProduct($product);

        $this->em()->persist($product);
        $this->em()->persist($partenaire);
        $this->em()->flush();

        $client->request(
            'POST',
            '/api/prospects',
            server: ['CONTENT_TYPE' => 'application/json', 'HTTP_X_API_TOKEN' => 'token-vehicule-test'],
            content: json_encode([
                'nom' => 'Dupont',
                'prenom' => 'Jean',
                'phone' => '0600000000',
                'immatriculation' => 'AB-123-CD',
                'marque' => 'Renault',
            ])
        );

        $this->assertResponseStatusCodeSame(201);
        $responseData = json_decode($client->getResponse()->getContent(), true);

        $prospect = $this->em()->getRepository(Prospect::class)->find($responseData['id']);
        $this->assertNotNull($prospect->getVehiculeDetails());
        $this->assertSame('AB-123-CD', $prospect->getVehiculeDetails()->getImmatriculation());
        $this->assertNull($prospect->getPrevoyanceDetails());
        $this->assertNull($prospect->getConstructionDetails());
    }

    public function testCreateProspectMissingRequiredFieldsReturns400(): void
    {
        $client = static::createClient();

        $product = (new Product())->setCode(Product::CODE_PREVOYANCE)->setNom('Prévoyance');
        $partenaire = (new Compartenaire())
            ->setNom('Site Prevoyance Test')
            ->setApiToken('token-prevoyance-test')
            ->setApiActif(true)
            ->setProduct($product);

        $this->em()->persist($product);
        $this->em()->persist($partenaire);
        $this->em()->flush();

        $client->request(
            'POST',
            '/api/prospects',
            server: ['CONTENT_TYPE' => 'application/json', 'HTTP_X_API_TOKEN' => 'token-prevoyance-test'],
            content: json_encode(['prenom' => 'Sans nom ni phone'])
        );

        $this->assertResponseStatusCodeSame(400);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (isset($this->em)) {
            $this->em->close();
        }
    }
}
