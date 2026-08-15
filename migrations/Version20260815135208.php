<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260815135208 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE advertisement ADD slug VARCHAR(200) DEFAULT NULL, ADD description LONGTEXT DEFAULT NULL, CHANGE impression_count impression_count INT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C95F6AEE989D9B62 ON advertisement (slug)');
        $this->addSql('ALTER TABLE advertisement_zone_placement RENAME INDEX idx_placement_advertisement TO IDX_46E383AEA1FBF71B');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_C95F6AEE989D9B62 ON advertisement');
        $this->addSql('ALTER TABLE advertisement DROP slug, DROP description, CHANGE impression_count impression_count INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE advertisement_zone_placement RENAME INDEX idx_46e383aea1fbf71b TO IDX_placement_advertisement');
    }
}
