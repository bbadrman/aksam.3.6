<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * La migration qui a ajouté `user.permissions` (JSON NOT NULL, sans DEFAULT)
 * a fait backfiller les lignes existantes avec une chaîne vide par MariaDB,
 * qui n'est pas un JSON valide. La contrainte CHECK auto-générée sur les
 * colonnes JSON bloque alors toute UPDATE sur ces lignes, même celles qui ne
 * touchent pas cette colonne (le CHECK porte sur la ligne entière).
 */
final class Version20260915151343 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Corrige les valeurs invalides de user.permissions (chaîne vide -> objet JSON vide)";
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE user SET permissions = '{}' WHERE JSON_VALID(permissions) = 0");
    }

    public function down(Schema $schema): void
    {
        // Pas de retour en arrière pertinent : on ne restaure pas une valeur invalide.
    }
}
