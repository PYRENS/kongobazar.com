<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260729171751 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE individual_profile (id INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE individual_profile ADD CONSTRAINT FK_B88B2B82BF396750 FOREIGN KEY (id) REFERENCES seller_profile (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product ADD `condition` VARCHAR(10) DEFAULT \'new\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE individual_profile DROP FOREIGN KEY FK_B88B2B82BF396750');
        $this->addSql('DROP TABLE individual_profile');
        $this->addSql('ALTER TABLE product DROP `condition`');
    }
}
