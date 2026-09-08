<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260908092842 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE sav (id INT AUTO_INCREMENT NOT NULL, nature_demande VARCHAR(255) DEFAULT NULL, motif LONGTEXT DEFAULT NULL, statut VARCHAR(20) NOT NULL, montant NUMERIC(10, 2) DEFAULT NULL, created_at DATETIME NOT NULL, relance_at DATETIME DEFAULT NULL, closed_at DATETIME DEFAULT NULL, contrat_id INT NOT NULL, gestionnaire_id INT DEFAULT NULL, INDEX IDX_6C7681F41823061F (contrat_id), INDEX IDX_6C7681F46885AC1B (gestionnaire_id), INDEX idx_sav_statut (statut), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE sav_relance_history (id INT AUTO_INCREMENT NOT NULL, comment LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, sav_id INT NOT NULL, auteur_id INT DEFAULT NULL, INDEX IDX_157607264F726353 (sav_id), INDEX IDX_1576072660BB6FE6 (auteur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE sav ADD CONSTRAINT FK_6C7681F41823061F FOREIGN KEY (contrat_id) REFERENCES contrat (id)');
        $this->addSql('ALTER TABLE sav ADD CONSTRAINT FK_6C7681F46885AC1B FOREIGN KEY (gestionnaire_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE sav_relance_history ADD CONSTRAINT FK_157607264F726353 FOREIGN KEY (sav_id) REFERENCES sav (id)');
        $this->addSql('ALTER TABLE sav_relance_history ADD CONSTRAINT FK_1576072660BB6FE6 FOREIGN KEY (auteur_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sav DROP FOREIGN KEY FK_6C7681F41823061F');
        $this->addSql('ALTER TABLE sav DROP FOREIGN KEY FK_6C7681F46885AC1B');
        $this->addSql('ALTER TABLE sav_relance_history DROP FOREIGN KEY FK_157607264F726353');
        $this->addSql('ALTER TABLE sav_relance_history DROP FOREIGN KEY FK_1576072660BB6FE6');
        $this->addSql('DROP TABLE sav');
        $this->addSql('DROP TABLE sav_relance_history');
    }
}
