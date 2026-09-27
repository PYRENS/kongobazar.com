<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260926041832 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD civility VARCHAR(10) DEFAULT NULL, ADD date_of_birth DATETIME DEFAULT NULL, ADD accepts_newsletter TINYINT DEFAULT 0 NOT NULL, ADD verification_token VARCHAR(64) DEFAULT NULL, ADD verification_token_expires_at DATETIME DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649C1CC006B ON user (verification_token)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_8D93D649C1CC006B ON `user`');
        $this->addSql('ALTER TABLE `user` DROP civility, DROP date_of_birth, DROP accepts_newsletter, DROP verification_token, DROP verification_token_expires_at');
    }
}
