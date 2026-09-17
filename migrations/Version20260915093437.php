<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260915093437 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // DEFAULT '{}' évite qu'un backfill sur les lignes existantes écrive une
        // chaîne vide, invalide pour la contrainte CHECK JSON de MariaDB (voir
        // Version20260915151343 qui corrige les bases déjà migrées sans ce défaut).
        $this->addSql("ALTER TABLE user ADD permissions JSON NOT NULL DEFAULT '{}'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user DROP permissions');
    }
}
