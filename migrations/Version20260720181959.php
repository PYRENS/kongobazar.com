<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260720181959 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE dispute (id INT AUTO_INCREMENT NOT NULL, reason LONGTEXT NOT NULL, status VARCHAR(20) NOT NULL, resolution_type VARCHAR(20) DEFAULT NULL, compensation_type VARCHAR(20) DEFAULT NULL, resolved_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, order_id INT NOT NULL, opened_by_id INT NOT NULL, arbitrated_by_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_3C9250078D9F6D38 (order_id), INDEX IDX_3C925007AB159F5 (opened_by_id), INDEX IDX_3C925007551AA070 (arbitrated_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE dispute ADD CONSTRAINT FK_3C9250078D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE dispute ADD CONSTRAINT FK_3C925007AB159F5 FOREIGN KEY (opened_by_id) REFERENCES `user` (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE dispute ADD CONSTRAINT FK_3C925007551AA070 FOREIGN KEY (arbitrated_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dispute DROP FOREIGN KEY FK_3C9250078D9F6D38');
        $this->addSql('ALTER TABLE dispute DROP FOREIGN KEY FK_3C925007AB159F5');
        $this->addSql('ALTER TABLE dispute DROP FOREIGN KEY FK_3C925007551AA070');
        $this->addSql('DROP TABLE dispute');
    }
}
