<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260804145936 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout du rayon Auto-Moto (Auto/Moto > Offre/Pièces détachées/Accessoires)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES ('Auto-Moto', 'auto-moto', NULL, 0, 'bi-car-front', 1)");
        $this->addSql('SET @automoto_id = LAST_INSERT_ID()');

        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES ('Auto', 'auto', @automoto_id, 1, 'bi-car-front-fill', 1)");
        $this->addSql('SET @auto_id = LAST_INSERT_ID()');

        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES ('Moto', 'moto', @automoto_id, 2, 'bi-scooter', 1)");
        $this->addSql('SET @moto_id = LAST_INSERT_ID()');

        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES ('Offre', 'offre', @auto_id, 1, 'bi-tag', 1)");
        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES ('Pièces détachées', 'pieces-detachees', @auto_id, 2, 'bi-gear', 1)");
        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES ('Accessoires', 'accessoires', @auto_id, 3, 'bi-collection', 1)");
        $this->addSql('SET @auto_accessoires_id = LAST_INSERT_ID()');

        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES
            ('Tapis', 'tapis', @auto_accessoires_id, 1, 'bi-square', 1),
            ('Garniture', 'garniture', @auto_accessoires_id, 2, 'bi-square', 1),
            ('Housses', 'housses', @auto_accessoires_id, 3, 'bi-square', 1)
        ");

        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES ('Offre', 'offre', @moto_id, 1, 'bi-tag', 1)");
        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES ('Pièces détachées', 'pieces-detachees', @moto_id, 2, 'bi-gear', 1)");
        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES ('Accessoires', 'accessoires', @moto_id, 3, 'bi-collection', 1)");
        $this->addSql('SET @moto_accessoires_id = LAST_INSERT_ID()');

        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES ('Nettoyage', 'nettoyage', @moto_accessoires_id, 1, 'bi-square', 1)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM category WHERE parent_id IN (
            SELECT id FROM (SELECT id FROM category WHERE parent_id IN (
                SELECT id FROM (SELECT id FROM category WHERE parent_id IN (
                    SELECT id FROM (SELECT id FROM category WHERE slug = 'auto-moto') t1
                )) t2
            )) t3
        )");
        $this->addSql("DELETE FROM category WHERE parent_id IN (
            SELECT id FROM (SELECT id FROM category WHERE parent_id IN (
                SELECT id FROM (SELECT id FROM category WHERE slug = 'auto-moto') t1
            )) t2
        )");
        $this->addSql("DELETE FROM category WHERE parent_id IN (SELECT id FROM (SELECT id FROM category WHERE slug = 'auto-moto') t)");
        $this->addSql("DELETE FROM category WHERE slug = 'auto-moto'");
    }
}