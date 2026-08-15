<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260815130622 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Placements par zone (AdvertisementZonePlacement) — une bannière peut désormais apparaître dans plusieurs zones, statistiques distinctes par zone';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE advertisement_zone_placement (id INT AUTO_INCREMENT NOT NULL, advertisement_id INT NOT NULL, zone_key VARCHAR(50) NOT NULL, impression_count INT NOT NULL, click_count INT NOT NULL, UNIQUE INDEX uniq_ad_zone (advertisement_id, zone_key), INDEX IDX_placement_advertisement (advertisement_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`");
        $this->addSql("ALTER TABLE advertisement_zone_placement ADD CONSTRAINT FK_placement_advertisement FOREIGN KEY (advertisement_id) REFERENCES advertisement (id) ON DELETE CASCADE");

        // Un placement existant est créé pour chaque Advertisement, sur sa zone actuelle, en reportant ses statistiques déjà accumulées.
        $this->addSql("
            INSERT INTO advertisement_zone_placement (advertisement_id, zone_key, impression_count, click_count)
            SELECT id, zone_key, impression_count, click_count FROM advertisement
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE advertisement_zone_placement');
    }
}