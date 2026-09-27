<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260926162638 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE product_review (id INT AUTO_INCREMENT NOT NULL, rating INT NOT NULL, comment LONGTEXT DEFAULT NULL, status VARCHAR(20) DEFAULT \'pending\' NOT NULL, created_at DATETIME NOT NULL, product_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_1B3FC0624584665A (product_id), INDEX IDX_1B3FC062A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE product_review ADD CONSTRAINT FK_1B3FC0624584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_review ADD CONSTRAINT FK_1B3FC062A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_review DROP FOREIGN KEY FK_1B3FC0624584665A');
        $this->addSql('ALTER TABLE product_review DROP FOREIGN KEY FK_1B3FC062A76ED395');
        $this->addSql('DROP TABLE product_review');
    }
}
