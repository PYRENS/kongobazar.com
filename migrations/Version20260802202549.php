<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260802202549 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout du rayon Services et ses sous-catégories';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES ('Services', 'services', NULL, 0, 'bi-tools', 1)");
        $this->addSql('SET @services_id = LAST_INSERT_ID()');

        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES
            ('Plomberie', 'plomberie', @services_id, 1, 'bi-droplet', 1),
            ('Électricien', 'electricien', @services_id, 2, 'bi-lightning-charge', 1),
            ('Mécanicien', 'mecanicien', @services_id, 3, 'bi-wrench', 1),
            ('Maçon', 'macon', @services_id, 4, 'bi-bricks', 1),
            ('Jardinier', 'jardinier', @services_id, 5, 'bi-flower1', 1),
            ('Nettoyage', 'nettoyage', @services_id, 6, 'bi-brush', 1),
            ('Coiffure', 'coiffure', @services_id, 7, 'bi-scissors', 1),
            ('Peintre', 'peintre', @services_id, 8, 'bi-palette', 1),
            ('Menuisier', 'menuisier', @services_id, 9, 'bi-hammer', 1),
            ('Serrurier', 'serrurier', @services_id, 10, 'bi-key', 1),
            ('Climatisation', 'climatisation', @services_id, 11, 'bi-thermometer-half', 1),
            ('Déménagement', 'demenagement', @services_id, 12, 'bi-truck', 1),
            ('Couture / Tailleur', 'couture-tailleur', @services_id, 13, 'bi-vector-pen', 1),
            ('Beauté / Esthétique', 'beaute-esthetique', @services_id, 14, 'bi-flower2', 1),
            ('Réparation électroménager', 'reparation-electromenager', @services_id, 15, 'bi-tools', 1),
            ('Cours particuliers', 'cours-particuliers', @services_id, 16, 'bi-book', 1),
            ('Photographe', 'photographe', @services_id, 17, 'bi-camera', 1),
            ('Traiteur / Cuisine', 'traiteur-cuisine', @services_id, 18, 'bi-egg-fried', 1),
            ('Informatique / Réparation', 'informatique-reparation', @services_id, 19, 'bi-laptop', 1)
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM category WHERE slug = 'services' OR parent_id = (SELECT id FROM category WHERE slug = 'services')");
    }
}