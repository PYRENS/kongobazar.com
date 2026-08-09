<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260807213116  extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Arborescence complète des Pièces détachées Auto et Moto (branches système + feuilles)';
    }

    public function up(Schema $schema): void
    {
        // Retrouve les catégories "Pièces détachées" déjà créées sous Auto et Moto
        $this->addSql("SET @auto_pieces_id = (SELECT id FROM category WHERE slug = 'pieces-detachees' AND parent_id = (SELECT id FROM category WHERE slug = 'auto' AND parent_id = (SELECT id FROM category WHERE slug = 'auto-moto')))");
        $this->addSql("SET @moto_pieces_id = (SELECT id FROM category WHERE slug = 'pieces-detachees' AND parent_id = (SELECT id FROM category WHERE slug = 'moto' AND parent_id = (SELECT id FROM category WHERE slug = 'auto-moto')))");

        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES ('Moteur', 'moteur', @auto_pieces_id, 1, 'bi-gear-wide-connected', 1)");
        $this->addSql('SET @auto_pieces_id_b1 = LAST_INSERT_ID()');
        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES
            ('Courroie de distribution', 'courroie-de-distribution', @auto_pieces_id_b1, 1, 'bi-square', 1),
            ('Kit de distribution', 'kit-de-distribution', @auto_pieces_id_b1, 2, 'bi-square', 1),
            ('Courroie d\'accessoires', 'courroie-d-accessoires', @auto_pieces_id_b1, 3, 'bi-square', 1),
            ('Galet tendeur', 'galet-tendeur', @auto_pieces_id_b1, 4, 'bi-square', 1),
            ('Joint de culasse', 'joint-de-culasse', @auto_pieces_id_b1, 5, 'bi-square', 1),
            ('Turbo', 'turbo', @auto_pieces_id_b1, 6, 'bi-square', 1),
            ('Injecteurs', 'injecteurs', @auto_pieces_id_b1, 7, 'bi-square', 1),
            ('Bougies d\'allumage', 'bougies-d-allumage', @auto_pieces_id_b1, 8, 'bi-square', 1),
            ('Bougies de préchauffage', 'bougies-de-prechauffage', @auto_pieces_id_b1, 9, 'bi-square', 1),
            ('Vilebrequin', 'vilebrequin', @auto_pieces_id_b1, 10, 'bi-square', 1),
            ('Pistons & segments', 'pistons-segments', @auto_pieces_id_b1, 11, 'bi-square', 1)
        ");

        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES ('Freinage', 'freinage', @auto_pieces_id, 2, 'bi-disc', 1)");
        $this->addSql('SET @auto_pieces_id_b2 = LAST_INSERT_ID()');
        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES
            ('Plaquettes de frein', 'plaquettes-de-frein', @auto_pieces_id_b2, 1, 'bi-square', 1),
            ('Disques de frein', 'disques-de-frein', @auto_pieces_id_b2, 2, 'bi-square', 1),
            ('Étriers de frein', 'etriers-de-frein', @auto_pieces_id_b2, 3, 'bi-square', 1),
            ('Tambours de frein', 'tambours-de-frein', @auto_pieces_id_b2, 4, 'bi-square', 1),
            ('Mâchoires de frein', 'machoires-de-frein', @auto_pieces_id_b2, 5, 'bi-square', 1),
            ('Flexibles de frein', 'flexibles-de-frein', @auto_pieces_id_b2, 6, 'bi-square', 1),
            ('Maître-cylindre', 'maitre-cylindre', @auto_pieces_id_b2, 7, 'bi-square', 1)
        ");

        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES ('Suspension & Direction', 'suspension-direction', @auto_pieces_id, 3, 'bi-arrows-collapse', 1)");
        $this->addSql('SET @auto_pieces_id_b3 = LAST_INSERT_ID()');
        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES
            ('Amortisseurs', 'amortisseurs', @auto_pieces_id_b3, 1, 'bi-square', 1),
            ('Ressorts de suspension', 'ressorts-de-suspension', @auto_pieces_id_b3, 2, 'bi-square', 1),
            ('Rotules de suspension', 'rotules-de-suspension', @auto_pieces_id_b3, 3, 'bi-square', 1),
            ('Biellettes de barre stabilisatrice', 'biellettes-de-barre-stabilisatrice', @auto_pieces_id_b3, 4, 'bi-square', 1),
            ('Silentblocs', 'silentblocs', @auto_pieces_id_b3, 5, 'bi-square', 1),
            ('Crémaillère de direction', 'cremaillere-de-direction', @auto_pieces_id_b3, 6, 'bi-square', 1)
        ");

        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES ('Transmission & Embrayage', 'transmission-embrayage', @auto_pieces_id, 4, 'bi-arrow-repeat', 1)");
        $this->addSql('SET @auto_pieces_id_b4 = LAST_INSERT_ID()');
        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES
            ('Kit d\'embrayage', 'kit-d-embrayage', @auto_pieces_id_b4, 1, 'bi-square', 1),
            ('Volant moteur', 'volant-moteur', @auto_pieces_id_b4, 2, 'bi-square', 1),
            ('Cardans', 'cardans', @auto_pieces_id_b4, 3, 'bi-square', 1),
            ('Boîte de vitesses', 'boite-de-vitesses', @auto_pieces_id_b4, 4, 'bi-square', 1)
        ");

        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES ('Refroidissement', 'refroidissement', @auto_pieces_id, 5, 'bi-thermometer-snow', 1)");
        $this->addSql('SET @auto_pieces_id_b5 = LAST_INSERT_ID()');
        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES
            ('Radiateur', 'radiateur', @auto_pieces_id_b5, 1, 'bi-square', 1),
            ('Pompe à eau', 'pompe-a-eau', @auto_pieces_id_b5, 2, 'bi-square', 1),
            ('Thermostat', 'thermostat', @auto_pieces_id_b5, 3, 'bi-square', 1),
            ('Durites de refroidissement', 'durites-de-refroidissement', @auto_pieces_id_b5, 4, 'bi-square', 1),
            ('Vase d\'expansion', 'vase-d-expansion', @auto_pieces_id_b5, 5, 'bi-square', 1),
            ('Ventilateur', 'ventilateur', @auto_pieces_id_b5, 6, 'bi-square', 1)
        ");

        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES ('Échappement', 'echappement', @auto_pieces_id, 6, 'bi-wind', 1)");
        $this->addSql('SET @auto_pieces_id_b6 = LAST_INSERT_ID()');
        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES
            ('Pot catalytique', 'pot-catalytique', @auto_pieces_id_b6, 1, 'bi-square', 1),
            ('Silencieux', 'silencieux', @auto_pieces_id_b6, 2, 'bi-square', 1),
            ('Ligne d\'échappement', 'ligne-d-echappement', @auto_pieces_id_b6, 3, 'bi-square', 1),
            ('Sonde lambda', 'sonde-lambda', @auto_pieces_id_b6, 4, 'bi-square', 1),
            ('Filtre à particules', 'filtre-a-particules', @auto_pieces_id_b6, 5, 'bi-square', 1)
        ");

        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES ('Électrique', 'electrique', @auto_pieces_id, 7, 'bi-lightning-charge', 1)");
        $this->addSql('SET @auto_pieces_id_b7 = LAST_INSERT_ID()');
        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES
            ('Batterie', 'batterie', @auto_pieces_id_b7, 1, 'bi-square', 1),
            ('Alternateur', 'alternateur', @auto_pieces_id_b7, 2, 'bi-square', 1),
            ('Démarreur', 'demarreur', @auto_pieces_id_b7, 3, 'bi-square', 1),
            ('Bobines d\'allumage', 'bobines-d-allumage', @auto_pieces_id_b7, 4, 'bi-square', 1),
            ('Capteurs', 'capteurs', @auto_pieces_id_b7, 5, 'bi-square', 1),
            ('Câblage', 'cablage', @auto_pieces_id_b7, 6, 'bi-square', 1)
        ");

        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES ('Éclairage', 'eclairage', @auto_pieces_id, 8, 'bi-lightbulb', 1)");
        $this->addSql('SET @auto_pieces_id_b8 = LAST_INSERT_ID()');
        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES
            ('Phares avant', 'phares-avant', @auto_pieces_id_b8, 1, 'bi-square', 1),
            ('Feux arrière', 'feux-arriere', @auto_pieces_id_b8, 2, 'bi-square', 1),
            ('Ampoules', 'ampoules', @auto_pieces_id_b8, 3, 'bi-square', 1),
            ('Clignotants', 'clignotants', @auto_pieces_id_b8, 4, 'bi-square', 1)
        ");

        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES ('Filtration', 'filtration', @auto_pieces_id, 9, 'bi-funnel', 1)");
        $this->addSql('SET @auto_pieces_id_b9 = LAST_INSERT_ID()');
        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES
            ('Filtre à huile', 'filtre-a-huile', @auto_pieces_id_b9, 1, 'bi-square', 1),
            ('Filtre à air', 'filtre-a-air', @auto_pieces_id_b9, 2, 'bi-square', 1),
            ('Filtre habitacle', 'filtre-habitacle', @auto_pieces_id_b9, 3, 'bi-square', 1),
            ('Filtre à carburant', 'filtre-a-carburant', @auto_pieces_id_b9, 4, 'bi-square', 1)
        ");

        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES ('Climatisation', 'climatisation', @auto_pieces_id, 10, 'bi-snow', 1)");
        $this->addSql('SET @auto_pieces_id_b10 = LAST_INSERT_ID()');
        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES
            ('Compresseur clim', 'compresseur-clim', @auto_pieces_id_b10, 1, 'bi-square', 1),
            ('Condenseur', 'condenseur', @auto_pieces_id_b10, 2, 'bi-square', 1),
            ('Détendeur', 'detendeur', @auto_pieces_id_b10, 3, 'bi-square', 1)
        ");

        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES ('Carrosserie', 'carrosserie', @auto_pieces_id, 11, 'bi-car-front', 1)");
        $this->addSql('SET @auto_pieces_id_b11 = LAST_INSERT_ID()');
        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES
            ('Pare-chocs', 'pare-chocs', @auto_pieces_id_b11, 1, 'bi-square', 1),
            ('Rétroviseurs', 'retroviseurs', @auto_pieces_id_b11, 2, 'bi-square', 1),
            ('Pare-brise', 'pare-brise', @auto_pieces_id_b11, 3, 'bi-square', 1),
            ('Portières', 'portieres', @auto_pieces_id_b11, 4, 'bi-square', 1),
            ('Capot', 'capot', @auto_pieces_id_b11, 5, 'bi-square', 1),
            ('Ailes', 'ailes', @auto_pieces_id_b11, 6, 'bi-square', 1)
        ");

        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES ('Moteur', 'moteur', @moto_pieces_id, 1, 'bi-gear-wide-connected', 1)");
        $this->addSql('SET @moto_pieces_id_b1 = LAST_INSERT_ID()');
        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES
            ('Kit chaîne', 'kit-chaine', @moto_pieces_id_b1, 1, 'bi-square', 1),
            ('Bougies', 'bougies', @moto_pieces_id_b1, 2, 'bi-square', 1),
            ('Filtre à huile', 'filtre-a-huile', @moto_pieces_id_b1, 3, 'bi-square', 1),
            ('Filtre à air', 'filtre-a-air', @moto_pieces_id_b1, 4, 'bi-square', 1),
            ('Segments & pistons', 'segments-pistons', @moto_pieces_id_b1, 5, 'bi-square', 1),
            ('Carburateur / Injection', 'carburateur-injection', @moto_pieces_id_b1, 6, 'bi-square', 1)
        ");

        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES ('Freinage', 'freinage', @moto_pieces_id, 2, 'bi-disc', 1)");
        $this->addSql('SET @moto_pieces_id_b2 = LAST_INSERT_ID()');
        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES
            ('Plaquettes de frein', 'plaquettes-de-frein', @moto_pieces_id_b2, 1, 'bi-square', 1),
            ('Disques de frein', 'disques-de-frein', @moto_pieces_id_b2, 2, 'bi-square', 1),
            ('Maître-cylindre', 'maitre-cylindre', @moto_pieces_id_b2, 3, 'bi-square', 1),
            ('Durites de frein', 'durites-de-frein', @moto_pieces_id_b2, 4, 'bi-square', 1)
        ");

        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES ('Transmission', 'transmission', @moto_pieces_id, 3, 'bi-arrow-repeat', 1)");
        $this->addSql('SET @moto_pieces_id_b3 = LAST_INSERT_ID()');
        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES
            ('Chaîne', 'chaine', @moto_pieces_id_b3, 1, 'bi-square', 1),
            ('Couronne', 'couronne', @moto_pieces_id_b3, 2, 'bi-square', 1),
            ('Pignon', 'pignon', @moto_pieces_id_b3, 3, 'bi-square', 1)
        ");

        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES ('Électrique', 'electrique', @moto_pieces_id, 4, 'bi-lightning-charge', 1)");
        $this->addSql('SET @moto_pieces_id_b4 = LAST_INSERT_ID()');
        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES
            ('Batterie', 'batterie', @moto_pieces_id_b4, 1, 'bi-square', 1),
            ('Alternateur / Stator', 'alternateur-stator', @moto_pieces_id_b4, 2, 'bi-square', 1),
            ('Bobines d\'allumage', 'bobines-d-allumage', @moto_pieces_id_b4, 3, 'bi-square', 1),
            ('Faisceau électrique', 'faisceau-electrique', @moto_pieces_id_b4, 4, 'bi-square', 1)
        ");

        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES ('Éclairage', 'eclairage', @moto_pieces_id, 5, 'bi-lightbulb', 1)");
        $this->addSql('SET @moto_pieces_id_b5 = LAST_INSERT_ID()');
        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES
            ('Phare avant', 'phare-avant', @moto_pieces_id_b5, 1, 'bi-square', 1),
            ('Feu arrière', 'feu-arriere', @moto_pieces_id_b5, 2, 'bi-square', 1),
            ('Clignotants', 'clignotants', @moto_pieces_id_b5, 3, 'bi-square', 1),
            ('Ampoules', 'ampoules', @moto_pieces_id_b5, 4, 'bi-square', 1)
        ");

        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES ('Carrosserie / Carénage', 'carrosserie-carenage', @moto_pieces_id, 6, 'bi-car-front', 1)");
        $this->addSql('SET @moto_pieces_id_b6 = LAST_INSERT_ID()');
        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES
            ('Carénages', 'carenages', @moto_pieces_id_b6, 1, 'bi-square', 1),
            ('Rétroviseurs', 'retroviseurs', @moto_pieces_id_b6, 2, 'bi-square', 1),
            ('Selle', 'selle', @moto_pieces_id_b6, 3, 'bi-square', 1),
            ('Garde-boue', 'garde-boue', @moto_pieces_id_b6, 4, 'bi-square', 1)
        ");

        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES ('Suspension', 'suspension', @moto_pieces_id, 7, 'bi-arrows-collapse', 1)");
        $this->addSql('SET @moto_pieces_id_b7 = LAST_INSERT_ID()');
        $this->addSql("INSERT INTO category (name, slug, parent_id, position, icon, active) VALUES
            ('Amortisseurs', 'amortisseurs', @moto_pieces_id_b7, 1, 'bi-square', 1),
            ('Fourche', 'fourche', @moto_pieces_id_b7, 2, 'bi-square', 1),
            ('Roulements de direction', 'roulements-de-direction', @moto_pieces_id_b7, 3, 'bi-square', 1)
        ");
    }

    public function down(Schema $schema): void
    {
        // Supprime toutes les feuilles ajoutées (petits-enfants de Pièces détachées), puis les branches système elles-mêmes
        $this->addSql("DELETE FROM category WHERE parent_id IN (
            SELECT id FROM (
                SELECT c.id FROM category c
                WHERE c.parent_id IN (
                    SELECT id FROM (SELECT id FROM category WHERE slug = 'pieces-detachees') t
                )
            ) t2
        )");
        $this->addSql("DELETE FROM category WHERE parent_id IN (SELECT id FROM (SELECT id FROM category WHERE slug = 'pieces-detachees') t)");
    }
}
