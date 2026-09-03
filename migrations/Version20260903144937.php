<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260903144937 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE client (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) DEFAULT NULL, prenom VARCHAR(255) DEFAULT NULL, raison_sociale VARCHAR(255) DEFAULT NULL, phone VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL, adresse VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, team_id INT DEFAULT NULL, commercial_id INT DEFAULT NULL, prospect_id INT DEFAULT NULL, INDEX IDX_C7440455296CD8AE (team_id), INDEX IDX_C74404557854071C (commercial_id), UNIQUE INDEX UNIQ_C7440455D182060A (prospect_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_C7440455296CD8AE FOREIGN KEY (team_id) REFERENCES team (id)');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_C74404557854071C FOREIGN KEY (commercial_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_C7440455D182060A FOREIGN KEY (prospect_id) REFERENCES prospect (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_C7440455296CD8AE');
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_C74404557854071C');
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_C7440455D182060A');
        $this->addSql('DROP TABLE client');
    }
}
