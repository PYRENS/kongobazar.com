<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260809192929 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Catalogue global de caractéristiques (Characteristic/CharacteristicOption) — migration des données existantes';
    }

    public function up(Schema $schema): void
    {
        // 1. Nouvelles tables
        $this->addSql("CREATE TABLE characteristic (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, unit VARCHAR(20) DEFAULT NULL, data_type VARCHAR(20) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`");
        $this->addSql("CREATE TABLE characteristic_option (id INT AUTO_INCREMENT NOT NULL, characteristic_id INT NOT NULL, label VARCHAR(100) NOT NULL, position INT DEFAULT 0 NOT NULL, color_hex VARCHAR(7) DEFAULT NULL, legacy_option_id INT DEFAULT NULL, INDEX IDX_char_opt_characteristic (characteristic_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`");
        $this->addSql("ALTER TABLE characteristic_option ADD CONSTRAINT FK_char_opt_characteristic FOREIGN KEY (characteristic_id) REFERENCES characteristic (id) ON DELETE CASCADE");

        // 2. Un Characteristic par combinaison distincte (name, unit, data_type) déjà présente
        $this->addSql("INSERT INTO characteristic (name, unit, data_type) SELECT DISTINCT name, unit, data_type FROM category_attribute");

        // 3. Migration des options, avec trace de l'ancien id pour pouvoir remapper les valeurs produit ensuite
        $this->addSql("
            INSERT INTO characteristic_option (characteristic_id, label, position, color_hex, legacy_option_id)
            SELECT c.id, cao.label, cao.position, cao.color_hex, cao.id
            FROM category_attribute_option cao
            JOIN category_attribute ca ON cao.category_attribute_id = ca.id
            JOIN characteristic c ON c.name = ca.name AND (c.unit <=> ca.unit) AND c.data_type = ca.data_type
        ");

        // 4. Remappage des valeurs produit déjà enregistrées vers les nouvelles options
        $this->addSql("
            UPDATE product_attribute_value pav
            JOIN characteristic_option co ON co.legacy_option_id = pav.category_attribute_option_id
            SET pav.category_attribute_option_id = co.id
            WHERE pav.category_attribute_option_id IS NOT NULL
        ");
        $this->addSql("
            UPDATE part_catalog_attribute_value pcav
            JOIN characteristic_option co ON co.legacy_option_id = pcav.category_attribute_option_id
            SET pcav.category_attribute_option_id = co.id
            WHERE pcav.category_attribute_option_id IS NOT NULL
        ");

        $this->addSql("ALTER TABLE characteristic_option DROP COLUMN legacy_option_id");

        // 5. category_attribute pointe désormais vers characteristic
        $this->addSql("ALTER TABLE category_attribute ADD characteristic_id INT DEFAULT NULL");
        $this->addSql("
            UPDATE category_attribute ca
            JOIN characteristic c ON c.name = ca.name AND (c.unit <=> ca.unit) AND c.data_type = ca.data_type
            SET ca.characteristic_id = c.id
        ");
        $this->addSql("ALTER TABLE category_attribute MODIFY characteristic_id INT NOT NULL");
        $this->addSql("ALTER TABLE category_attribute ADD CONSTRAINT FK_cat_attr_characteristic FOREIGN KEY (characteristic_id) REFERENCES characteristic (id)");
        $this->addSql("CREATE INDEX IDX_cat_attr_characteristic ON category_attribute (characteristic_id)");

        // 6. Bascule des FK product_attribute_value / part_catalog_attribute_value vers characteristic_option
        $this->addSql("ALTER TABLE product_attribute_value DROP FOREIGN KEY FK_CCC4BE1F91B687D0");
        $this->addSql("ALTER TABLE product_attribute_value ADD CONSTRAINT FK_pav_characteristic_option FOREIGN KEY (category_attribute_option_id) REFERENCES characteristic_option (id) ON DELETE SET NULL");

        $this->addSql("ALTER TABLE part_catalog_attribute_value DROP FOREIGN KEY FK_A4F8C33A91B687D0");
        $this->addSql("ALTER TABLE part_catalog_attribute_value ADD CONSTRAINT FK_pcav_characteristic_option FOREIGN KEY (category_attribute_option_id) REFERENCES characteristic_option (id) ON DELETE SET NULL");

        // 7. Nettoyage — l'ancienne structure n'est plus référencée par rien
        $this->addSql("ALTER TABLE category_attribute_option DROP FOREIGN KEY FK_FD5A976E6C310D68");
        $this->addSql("DROP TABLE category_attribute_option");
        $this->addSql("ALTER TABLE category_attribute DROP COLUMN name, DROP COLUMN unit, DROP COLUMN data_type");
    }

    public function down(Schema $schema): void
    {
        throw new \RuntimeException('Migration non réversible automatiquement — restaurer depuis une sauvegarde si besoin.');
    }
}