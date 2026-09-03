<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260903105319 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE prospect (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) DEFAULT NULL, phone VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL, statut VARCHAR(30) NOT NULL, created_at DATETIME NOT NULL, affect_at DATETIME DEFAULT NULL, relance_at DATETIME DEFAULT NULL, product_id INT NOT NULL, team_id INT DEFAULT NULL, commercial_id INT DEFAULT NULL, INDEX IDX_C9CE8C7D4584665A (product_id), INDEX IDX_C9CE8C7D296CD8AE (team_id), INDEX IDX_C9CE8C7D7854071C (commercial_id), INDEX idx_prospect_statut (statut), INDEX idx_prospect_relance_at (relance_at), INDEX idx_prospect_affect_at (affect_at), INDEX idx_prospect_created_at (created_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE prospect_construction_details (id INT AUTO_INCREMENT NOT NULL, type_bien VARCHAR(255) DEFAULT NULL, surface_m2 NUMERIC(10, 2) DEFAULT NULL, date_construction DATETIME DEFAULT NULL, adresse_bien VARCHAR(255) DEFAULT NULL, prospect_id INT NOT NULL, UNIQUE INDEX UNIQ_E7941378D182060A (prospect_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE prospect_prevoyance_details (id INT AUTO_INCREMENT NOT NULL, capital_souhaite NUMERIC(10, 2) DEFAULT NULL, situation_familiale VARCHAR(255) DEFAULT NULL, nombre_enfants INT DEFAULT NULL, profession VARCHAR(255) DEFAULT NULL, prospect_id INT NOT NULL, UNIQUE INDEX UNIQ_34F76F84D182060A (prospect_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE prospect_vehicule_details (id INT AUTO_INCREMENT NOT NULL, immatriculation VARCHAR(20) DEFAULT NULL, marque VARCHAR(255) DEFAULT NULL, modele VARCHAR(255) DEFAULT NULL, date_mise_circulation DATETIME DEFAULT NULL, prospect_id INT NOT NULL, UNIQUE INDEX UNIQ_684A2CA2D182060A (prospect_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE prospect ADD CONSTRAINT FK_C9CE8C7D4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE prospect ADD CONSTRAINT FK_C9CE8C7D296CD8AE FOREIGN KEY (team_id) REFERENCES team (id)');
        $this->addSql('ALTER TABLE prospect ADD CONSTRAINT FK_C9CE8C7D7854071C FOREIGN KEY (commercial_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE prospect_construction_details ADD CONSTRAINT FK_E7941378D182060A FOREIGN KEY (prospect_id) REFERENCES prospect (id)');
        $this->addSql('ALTER TABLE prospect_prevoyance_details ADD CONSTRAINT FK_34F76F84D182060A FOREIGN KEY (prospect_id) REFERENCES prospect (id)');
        $this->addSql('ALTER TABLE prospect_vehicule_details ADD CONSTRAINT FK_684A2CA2D182060A FOREIGN KEY (prospect_id) REFERENCES prospect (id)');
        $this->addSql('ALTER TABLE compartenaire ADD product_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE compartenaire ADD CONSTRAINT FK_ACBE13514584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('CREATE INDEX IDX_ACBE13514584665A ON compartenaire (product_id)');
        $this->addSql('ALTER TABLE product ADD code VARCHAR(30) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D34A04AD77153098 ON product (code)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE prospect DROP FOREIGN KEY FK_C9CE8C7D4584665A');
        $this->addSql('ALTER TABLE prospect DROP FOREIGN KEY FK_C9CE8C7D296CD8AE');
        $this->addSql('ALTER TABLE prospect DROP FOREIGN KEY FK_C9CE8C7D7854071C');
        $this->addSql('ALTER TABLE prospect_construction_details DROP FOREIGN KEY FK_E7941378D182060A');
        $this->addSql('ALTER TABLE prospect_prevoyance_details DROP FOREIGN KEY FK_34F76F84D182060A');
        $this->addSql('ALTER TABLE prospect_vehicule_details DROP FOREIGN KEY FK_684A2CA2D182060A');
        $this->addSql('DROP TABLE prospect');
        $this->addSql('DROP TABLE prospect_construction_details');
        $this->addSql('DROP TABLE prospect_prevoyance_details');
        $this->addSql('DROP TABLE prospect_vehicule_details');
        $this->addSql('ALTER TABLE compartenaire DROP FOREIGN KEY FK_ACBE13514584665A');
        $this->addSql('DROP INDEX IDX_ACBE13514584665A ON compartenaire');
        $this->addSql('ALTER TABLE compartenaire DROP product_id');
        $this->addSql('DROP INDEX UNIQ_D34A04AD77153098 ON product');
        $this->addSql('ALTER TABLE product DROP code');
    }
}
