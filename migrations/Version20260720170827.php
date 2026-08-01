<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260720170827 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE commission_tier (id INT AUTO_INCREMENT NOT NULL, min_amount_usd NUMERIC(10, 2) NOT NULL, max_amount_usd NUMERIC(10, 2) DEFAULT NULL, percentage NUMERIC(5, 2) NOT NULL, active TINYINT NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE exchange_rate (id INT AUTO_INCREMENT NOT NULL, rate_usd_to_cdf NUMERIC(12, 4) NOT NULL, source VARCHAR(10) NOT NULL, effective_at DATETIME NOT NULL, set_by_id INT DEFAULT NULL, INDEX IDX_E9521FAB3E16DC62 (set_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE exchange_rate ADD CONSTRAINT FK_E9521FAB3E16DC62 FOREIGN KEY (set_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE product ADD currency VARCHAR(3) DEFAULT \'USD\' NOT NULL');
        $this->addSql('ALTER TABLE user ADD preferred_currency VARCHAR(3) DEFAULT \'USD\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE exchange_rate DROP FOREIGN KEY FK_E9521FAB3E16DC62');
        $this->addSql('DROP TABLE commission_tier');
        $this->addSql('DROP TABLE exchange_rate');
        $this->addSql('ALTER TABLE product DROP currency');
        $this->addSql('ALTER TABLE `user` DROP preferred_currency');
    }
}
