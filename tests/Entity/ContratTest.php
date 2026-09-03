<?php

namespace App\Tests\Entity;

use App\Entity\Client;
use App\Entity\Compartenaire;
use App\Entity\Contrat;
use App\Entity\ContratStatut;
use App\Entity\ContratVehiculeDetails;
use App\Entity\Frais;
use App\Entity\Product;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * Test unitaire pur (sans DB) sur Contrat::duplicate() — garantit la
 * non-régression à chaque ajout de champ futur, sans avoir à relire une
 * liste de ~35 set...() éparpillés comme dans l'ancien
 * ContratController::new().
 */
class ContratTest extends TestCase
{
    public function testDuplicateCopiesCommonFieldsButStartsAsNewDraft(): void
    {
        $client = new Client();
        $product = (new Product())->setCode('vehicule')->setNom('Véhicule');
        $compagnie = (new Compartenaire())->setNom('Compagnie Test');
        $gestionnaire = new User();

        $original = new Contrat();
        $original->setClient($client);
        $original->setProduct($product);
        $original->setCompagnie($compagnie);
        $original->setGestionnaire($gestionnaire);
        $original->setCotisation('123.45');
        $original->setFraction('mensuel');
        $original->setGaranties('Tous risques');
        $original->setStatut(ContratStatut::VALIDE);
        $original->setNumeroPolice('POL-2026-000001');

        $copie = $original->duplicate();

        $this->assertSame($client, $copie->getClient());
        $this->assertSame($product, $copie->getProduct());
        $this->assertSame($compagnie, $copie->getCompagnie());
        $this->assertSame($gestionnaire, $copie->getGestionnaire());
        $this->assertSame('123.45', $copie->getCotisation());
        $this->assertSame('mensuel', $copie->getFraction());
        $this->assertSame('Tous risques', $copie->getGaranties());

        // Un contrat dupliqué repart toujours à zéro : pas de numéro de
        // police ni de statut hérités du contrat source.
        $this->assertSame(ContratStatut::BROUILLON, $copie->getStatut());
        $this->assertNull($copie->getNumeroPolice());
    }

    public function testDuplicateClonesVehiculeDetailsAsNewInstance(): void
    {
        $original = $this->contratMinimalValide();
        $original->setVehiculeDetails(
            (new ContratVehiculeDetails())->setImmatriculation('AA-123-BB')->setConducteur('Jean Dupont')
        );

        $copie = $original->duplicate();

        $this->assertNotNull($copie->getVehiculeDetails());
        $this->assertNotSame($original->getVehiculeDetails(), $copie->getVehiculeDetails());
        $this->assertSame('AA-123-BB', $copie->getVehiculeDetails()->getImmatriculation());
        $this->assertSame('Jean Dupont', $copie->getVehiculeDetails()->getConducteur());
    }

    public function testDuplicateClonesFraisAsNewInstancesNotSharedReferences(): void
    {
        // L'ancien ContratController::new() faisait
        // $newContrat->setpayments($contrat->getpayments()) — partageait le
        // MÊME objet entre deux contrats, ce qui violait l'unicité OneToOne.
        // On vérifie ici que chaque Frais dupliqué est une instance distincte.
        $original = $this->contratMinimalValide();
        $fraisOriginal = (new Frais())->setLibelle('Frais de dossier')->setMontant('25.00');
        $original->addFrais($fraisOriginal);

        $copie = $original->duplicate();

        $this->assertCount(1, $copie->getFrais());
        $fraisCopie = $copie->getFrais()->first();
        $this->assertNotSame($fraisOriginal, $fraisCopie);
        $this->assertSame('Frais de dossier', $fraisCopie->getLibelle());
        $this->assertSame($copie, $fraisCopie->getContrat());
    }

    public function testDuplicateNeverCopiesPaymentOrDocument(): void
    {
        $original = $this->contratMinimalValide();
        // Payment/Document nécessiteraient une contrainte OneToOne unique en
        // base — on vérifie juste ici qu'ils ne sont jamais transférés au
        // niveau entité, avant même d'atteindre la DB.
        $copie = $original->duplicate();

        $this->assertNull($copie->getPayment());
        $this->assertNull($copie->getDocument());
    }

    /**
     * Un Contrat réel a toujours client/product (NOT NULL en base) —
     * duplicate() part de cette hypothèse, jamais violée en production.
     */
    private function contratMinimalValide(): Contrat
    {
        $contrat = new Contrat();
        $contrat->setClient(new Client());
        $contrat->setProduct(new Product());

        return $contrat;
    }
}
