<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260719194314 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, phone VARCHAR(20) DEFAULT NULL, status VARCHAR(20) NOT NULL, gps_lat DOUBLE PRECISION DEFAULT NULL, gps_lng DOUBLE PRECISION DEFAULT NULL, created_at DATETIME NOT NULL, administrative_unit_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649444F97DD (phone), INDEX IDX_8D93D649E66451E1 (administrative_unit_id), UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D649E66451E1 FOREIGN KEY (administrative_unit_id) REFERENCES administrative_unit (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE administrative_unit ADD CONSTRAINT FK_C86D61BE727ACA70 FOREIGN KEY (parent_id) REFERENCES administrative_unit (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649E66451E1');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('ALTER TABLE administrative_unit DROP FOREIGN KEY FK_C86D61BE727ACA70');
    }
}
