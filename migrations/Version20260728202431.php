<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260728202431 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE product_availability_alert (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, notified_at DATETIME DEFAULT NULL, product_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_21FA96F94584665A (product_id), INDEX IDX_21FA96F9A76ED395 (user_id), UNIQUE INDEX UNIQ_AVAILABILITY_ALERT_PRODUCT_USER (product_id, user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE product_availability_alert ADD CONSTRAINT FK_21FA96F94584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_availability_alert ADD CONSTRAINT FK_21FA96F9A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product ADD preorder_enabled TINYINT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_availability_alert DROP FOREIGN KEY FK_21FA96F94584665A');
        $this->addSql('ALTER TABLE product_availability_alert DROP FOREIGN KEY FK_21FA96F9A76ED395');
        $this->addSql('DROP TABLE product_availability_alert');
        $this->addSql('ALTER TABLE product DROP preorder_enabled');
    }
}
