<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260903160020 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE contrat (id INT AUTO_INCREMENT NOT NULL, statut VARCHAR(20) NOT NULL, numero_police VARCHAR(255) DEFAULT NULL, date_souscription DATETIME DEFAULT NULL, date_effet DATETIME DEFAULT NULL, cotisation NUMERIC(10, 2) DEFAULT NULL, fraction VARCHAR(50) DEFAULT NULL, garanties LONGTEXT DEFAULT NULL, comment LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, client_id INT NOT NULL, product_id INT NOT NULL, compagnie_id INT DEFAULT NULL, gestionnaire_id INT DEFAULT NULL, payment_id INT DEFAULT NULL, document_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_60349993BCBB4A63 (numero_police), INDEX IDX_6034999319EB6921 (client_id), INDEX IDX_603499934584665A (product_id), INDEX IDX_6034999352FBE437 (compagnie_id), INDEX IDX_603499936885AC1B (gestionnaire_id), UNIQUE INDEX UNIQ_603499934C3A3BB (payment_id), UNIQUE INDEX UNIQ_60349993C33F7837 (document_id), INDEX idx_contrat_statut (statut), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE contrat_vehicule_details (id INT AUTO_INCREMENT NOT NULL, immatriculation VARCHAR(20) DEFAULT NULL, conducteur VARCHAR(255) DEFAULT NULL, type_permis VARCHAR(255) DEFAULT NULL, date_permis DATETIME DEFAULT NULL, crm_actuel NUMERIC(10, 2) DEFAULT NULL, contrat_id INT NOT NULL, UNIQUE INDEX UNIQ_B3EC94211823061F (contrat_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE document (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) DEFAULT NULL, chemin_fichier VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE frais (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(255) NOT NULL, montant NUMERIC(10, 2) NOT NULL, contrat_id INT NOT NULL, INDEX IDX_25404C981823061F (contrat_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE payment (id INT AUTO_INCREMENT NOT NULL, montant NUMERIC(10, 2) DEFAULT NULL, methode VARCHAR(50) DEFAULT NULL, date_paiement DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE contrat ADD CONSTRAINT FK_6034999319EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE contrat ADD CONSTRAINT FK_603499934584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE contrat ADD CONSTRAINT FK_6034999352FBE437 FOREIGN KEY (compagnie_id) REFERENCES compartenaire (id)');
        $this->addSql('ALTER TABLE contrat ADD CONSTRAINT FK_603499936885AC1B FOREIGN KEY (gestionnaire_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE contrat ADD CONSTRAINT FK_603499934C3A3BB FOREIGN KEY (payment_id) REFERENCES payment (id)');
        $this->addSql('ALTER TABLE contrat ADD CONSTRAINT FK_60349993C33F7837 FOREIGN KEY (document_id) REFERENCES document (id)');
        $this->addSql('ALTER TABLE contrat_vehicule_details ADD CONSTRAINT FK_B3EC94211823061F FOREIGN KEY (contrat_id) REFERENCES contrat (id)');
        $this->addSql('ALTER TABLE frais ADD CONSTRAINT FK_25404C981823061F FOREIGN KEY (contrat_id) REFERENCES contrat (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contrat DROP FOREIGN KEY FK_6034999319EB6921');
        $this->addSql('ALTER TABLE contrat DROP FOREIGN KEY FK_603499934584665A');
        $this->addSql('ALTER TABLE contrat DROP FOREIGN KEY FK_6034999352FBE437');
        $this->addSql('ALTER TABLE contrat DROP FOREIGN KEY FK_603499936885AC1B');
        $this->addSql('ALTER TABLE contrat DROP FOREIGN KEY FK_603499934C3A3BB');
        $this->addSql('ALTER TABLE contrat DROP FOREIGN KEY FK_60349993C33F7837');
        $this->addSql('ALTER TABLE contrat_vehicule_details DROP FOREIGN KEY FK_B3EC94211823061F');
        $this->addSql('ALTER TABLE frais DROP FOREIGN KEY FK_25404C981823061F');
        $this->addSql('DROP TABLE contrat');
        $this->addSql('DROP TABLE contrat_vehicule_details');
        $this->addSql('DROP TABLE document');
        $this->addSql('DROP TABLE frais');
        $this->addSql('DROP TABLE payment');
    }
}
