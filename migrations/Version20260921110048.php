<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260921110048 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE prospect ADD ville VARCHAR(255) DEFAULT NULL, ADD adresse VARCHAR(255) DEFAULT NULL, ADD code_postal VARCHAR(20) DEFAULT NULL, ADD date_naissance DATE DEFAULT NULL, ADD source VARCHAR(255) DEFAULT NULL, ADD type_prospect VARCHAR(255) DEFAULT NULL, ADD raison_sociale VARCHAR(255) DEFAULT NULL, ADD activites LONGTEXT DEFAULT NULL, ADD url VARCHAR(255) DEFAULT NULL, ADD last_assure VARCHAR(255) DEFAULT NULL, ADD motif_resil VARCHAR(255) DEFAULT NULL, ADD motif_saisi VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE prospect DROP ville, DROP adresse, DROP code_postal, DROP date_naissance, DROP source, DROP type_prospect, DROP raison_sociale, DROP activites, DROP url, DROP last_assure, DROP motif_resil, DROP motif_saisi');
    }
}
