<?php

namespace App\Tests\Controller;

use App\Entity\Product;
use App\Entity\Prospect;
use App\Entity\ProspectConstructionDetails;
use App\Entity\ProspectPrevoyanceDetails;
use App\Entity\ProspectRelanceHistory;
use App\Entity\ProspectVehiculeDetails;
use App\Entity\RelanceMotif;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Tests fonctionnels bout-en-bout : un prospect de chaque produit
 * (véhicule, prévoyance, construction), avec une relance enregistrée,
 * doit s'afficher correctement sur la fiche /traitement/{id}.
 */
class ProspectShowControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private User $commercial;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);

        $this->commercial = new User();
        $this->commercial->setUsername('commercial-show-test');
        $this->commercial->setNom('Test');
        $this->commercial->setPrenom('Commercial');
        $this->commercial->setRoles(['ROLE_ADMIN']);
        $this->commercial->setPassword(
            $container->get(UserPasswordHasherInterface::class)->hashPassword($this->commercial, 'password123')
        );
        $this->em->persist($this->commercial);
        $this->em->flush();

        $this->client->loginUser($this->commercial);
    }

    public function testShowDisplaysVehiculeProspectWithRelanceHistory(): void
    {
        $prospect = $this->createProspectWithHistory(
            Product::CODE_VEHICULE,
            'Prospect Vehicule',
            (new ProspectVehiculeDetails())
                ->setImmatriculation('AA-111-BB')
                ->setMarque('Citroën')
                ->setModele('C3'),
            fn (Prospect $p) => $p->setVehiculeDetails($this->lastDetails),
        );

        $this->client->request('GET', '/traitement/'.$prospect->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Prospect Vehicule');
        $this->assertSelectorTextContains('body', 'AA-111-BB');
        $this->assertSelectorTextContains('body', 'Citroën');
        $this->assertSelectorTextContains('body', 'Rendez-vous');
        $this->assertSelectorTextContains('body', 'Premier contact, RDV pris');
    }

    public function testShowDisplaysPrevoyanceProspectWithRelanceHistory(): void
    {
        $prospect = $this->createProspectWithHistory(
            Product::CODE_PREVOYANCE,
            'Prospect Prevoyance',
            (new ProspectPrevoyanceDetails())
                ->setCapitalSouhaite('75000.00')
                ->setSituationFamiliale('Célibataire')
                ->setProfession('Artisan'),
            fn (Prospect $p) => $p->setPrevoyanceDetails($this->lastDetails),
        );

        $this->client->request('GET', '/traitement/'.$prospect->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Prospect Prevoyance');
        $this->assertSelectorTextContains('body', '75000.00');
        $this->assertSelectorTextContains('body', 'Artisan');
        $this->assertSelectorTextContains('body', 'Injoignable');
    }

    public function testShowDisplaysConstructionProspectWithRelanceHistory(): void
    {
        $prospect = $this->createProspectWithHistory(
            Product::CODE_CONSTRUCTION,
            'Prospect Construction',
            (new ProspectConstructionDetails())
                ->setTypeBien('Maison')
                ->setSurfaceM2('120.00')
                ->setAdresseBien('12 rue des Tests'),
            fn (Prospect $p) => $p->setConstructionDetails($this->lastDetails),
        );

        $this->client->request('GET', '/traitement/'.$prospect->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Prospect Construction');
        $this->assertSelectorTextContains('body', 'Maison');
        $this->assertSelectorTextContains('body', '12 rue des Tests');
        $this->assertSelectorTextContains('body', 'Toujours injoignable');
    }

    public function testTraiterFromShowPageAppendsHistoryAndRedirectsToShow(): void
    {
        $prospect = $this->createProspectWithHistory(
            Product::CODE_VEHICULE,
            'Prospect A Traiter',
            (new ProspectVehiculeDetails())->setImmatriculation('CC-222-DD'),
            fn (Prospect $p) => $p->setVehiculeDetails($this->lastDetails),
        );

        $this->client->request('POST', '/traitement/'.$prospect->getId().'/traiter', [
            'motif' => (string) RelanceMotif::ATTENTE_DOC->value,
            'comment' => 'En attente de documents',
        ]);

        $this->assertResponseRedirects('/traitement/'.$prospect->getId());
        $this->client->followRedirect();

        $this->assertSelectorTextContains('body', 'Attente DOC');
        $this->assertSelectorTextContains('body', 'En attente de documents');
        // L'historique garde la trace de la relance précédente en plus de la nouvelle.
        $this->assertSelectorTextContains('body', 'Premier contact, RDV pris');
    }

    private ?object $lastDetails = null;

    private function createProspectWithHistory(
        string $productCode,
        string $nom,
        object $details,
        callable $attachDetails,
    ): Prospect {
        $this->lastDetails = $details;

        $product = (new Product())->setCode($productCode.'-show-test')->setNom(ucfirst($productCode));
        $prospect = (new Prospect())->setNom($nom)->setPhone('0600000000')->setProduct($product);
        $attachDetails($prospect);

        $motif = match ($productCode) {
            Product::CODE_VEHICULE => RelanceMotif::RENDEZ_VOUS,
            Product::CODE_PREVOYANCE => RelanceMotif::INJOIGNABLE,
            default => RelanceMotif::TOUJOURS_INJOIGNABLE,
        };
        $comment = match ($productCode) {
            Product::CODE_VEHICULE => 'Premier contact, RDV pris',
            Product::CODE_PREVOYANCE => 'Pas de réponse',
            default => 'Injoignable depuis 3 tentatives',
        };

        $historyEntry = (new ProspectRelanceHistory())
            ->setMotif($motif)
            ->setComment($comment)
            ->setAuteur($this->commercial);
        $prospect->addRelanceHistory($historyEntry);
        $prospect->setRelance($motif);
        $prospect->setRelanceAt(new \DateTimeImmutable('+1 day'));

        $this->em->persist($product);
        $this->em->persist($prospect);
        $this->em->flush();

        return $prospect;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em->close();
    }
}
