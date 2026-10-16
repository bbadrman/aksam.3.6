<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261001154937 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Client : statut de décision (pré-client / validé / rejeté / annulé)';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client ADD status SMALLINT DEFAULT NULL, ADD status_at DATETIME DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_client_status ON client (status)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_client_status ON client');
        $this->addSql('ALTER TABLE client DROP status, DROP status_at');
    }
}
