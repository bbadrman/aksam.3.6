<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260923150059 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE team_product_limit (id INT AUTO_INCREMENT NOT NULL, max_prospects INT DEFAULT NULL, team_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_ABA8B6AD296CD8AE (team_id), INDEX IDX_ABA8B6AD4584665A (product_id), UNIQUE INDEX uniq_team_product (team_id, product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE team_product_limit ADD CONSTRAINT FK_ABA8B6AD296CD8AE FOREIGN KEY (team_id) REFERENCES team (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE team_product_limit ADD CONSTRAINT FK_ABA8B6AD4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE team_product_limit DROP FOREIGN KEY FK_ABA8B6AD296CD8AE');
        $this->addSql('ALTER TABLE team_product_limit DROP FOREIGN KEY FK_ABA8B6AD4584665A');
        $this->addSql('DROP TABLE team_product_limit');
    }
}
