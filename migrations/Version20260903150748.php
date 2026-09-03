<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260903150748 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE prospect_relance_history (id INT AUTO_INCREMENT NOT NULL, motif INT NOT NULL, comment LONGTEXT DEFAULT NULL, relanced_at DATETIME NOT NULL, prospect_id INT NOT NULL, auteur_id INT DEFAULT NULL, INDEX IDX_7D39FE91D182060A (prospect_id), INDEX IDX_7D39FE9160BB6FE6 (auteur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE prospect_relance_history ADD CONSTRAINT FK_7D39FE91D182060A FOREIGN KEY (prospect_id) REFERENCES prospect (id)');
        $this->addSql('ALTER TABLE prospect_relance_history ADD CONSTRAINT FK_7D39FE9160BB6FE6 FOREIGN KEY (auteur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE prospect ADD relance INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE prospect_relance_history DROP FOREIGN KEY FK_7D39FE91D182060A');
        $this->addSql('ALTER TABLE prospect_relance_history DROP FOREIGN KEY FK_7D39FE9160BB6FE6');
        $this->addSql('DROP TABLE prospect_relance_history');
        $this->addSql('ALTER TABLE prospect DROP relance');
    }
}
