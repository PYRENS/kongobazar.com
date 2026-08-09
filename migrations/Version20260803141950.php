<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260803141950 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout du rayon Immobilier (Location / Offres) et ses sous-catégories';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES ('Immobilier', 'immobilier', NULL, 0, 'bi-house-door', 1)");
        $this->addSql('SET @immo_id = LAST_INSERT_ID()');

        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES ('Location', 'location', @immo_id, 1, 'bi-key', 1)");
        $this->addSql('SET @location_id = LAST_INSERT_ID()');

        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES ('Offres', 'offres', @immo_id, 2, 'bi-tag', 1)");
        $this->addSql('SET @offres_id = LAST_INSERT_ID()');

        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES
            ('Maison', 'maison', @location_id, 1, 'bi-house', 1),
            ('Appartement', 'appartement', @location_id, 2, 'bi-building', 1),
            ('Bureau', 'bureau', @location_id, 3, 'bi-briefcase', 1),
            ('Villa', 'villa', @location_id, 4, 'bi-house-heart', 1),
            ('Parking', 'parking', @location_id, 5, 'bi-p-square', 1),
            ('Terrain', 'terrain', @location_id, 6, 'bi-map', 1),
            ('Maison commerciale', 'maison-commerciale', @location_id, 7, 'bi-shop', 1)
        ");

        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES
            ('Maison', 'maison', @offres_id, 1, 'bi-house', 1),
            ('Appartement', 'appartement', @offres_id, 2, 'bi-building', 1),
            ('Bureau', 'bureau', @offres_id, 3, 'bi-briefcase', 1),
            ('Villa', 'villa', @offres_id, 4, 'bi-house-heart', 1),
            ('Parking', 'parking', @offres_id, 5, 'bi-p-square', 1),
            ('Terrain', 'terrain', @offres_id, 6, 'bi-map', 1),
            ('Maison commerciale', 'maison-commerciale', @offres_id, 7, 'bi-shop', 1)
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM category WHERE parent_id IN (SELECT id FROM (SELECT id FROM category WHERE parent_id IN (SELECT id FROM (SELECT id FROM category WHERE slug = 'immobilier') t)) t2)");
        $this->addSql("DELETE FROM category WHERE parent_id IN (SELECT id FROM (SELECT id FROM category WHERE slug = 'immobilier') t)");
        $this->addSql("DELETE FROM category WHERE slug = 'immobilier'");
    }
}