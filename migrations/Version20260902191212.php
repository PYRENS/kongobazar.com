<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260902191212 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE seller_profile ADD reference_number VARCHAR(20) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5A59131E8BF1AE50 ON seller_profile (reference_number)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_5A59131E8BF1AE50 ON seller_profile');
        $this->addSql('ALTER TABLE seller_profile DROP reference_number');
    }
}
