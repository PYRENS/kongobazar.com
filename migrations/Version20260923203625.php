<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260923203625 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE legal_acceptance (id INT AUTO_INCREMENT NOT NULL, accepted_at DATETIME NOT NULL, ip_address VARCHAR(45) DEFAULT NULL, user_id INT NOT NULL, version_id INT NOT NULL, INDEX IDX_9680462FA76ED395 (user_id), INDEX IDX_9680462F4BBC2705 (version_id), UNIQUE INDEX UNIQ_9680462FA76ED3954BBC2705 (user_id, version_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE legal_article (id INT AUTO_INCREMENT NOT NULL, position INT NOT NULL, title VARCHAR(200) NOT NULL, body LONGTEXT NOT NULL, change_type VARCHAR(20) DEFAULT \'unchanged\' NOT NULL, version_id INT NOT NULL, INDEX IDX_C50190084BBC2705 (version_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE legal_document (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(50) NOT NULL, label VARCHAR(150) NOT NULL, target_space VARCHAR(20) DEFAULT \'public\' NOT NULL, requires_acceptance TINYINT DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_72A4FDB777153098 (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE legal_document_version (id INT AUTO_INCREMENT NOT NULL, version_number INT NOT NULL, status VARCHAR(20) DEFAULT \'draft\' NOT NULL, change_summary LONGTEXT DEFAULT NULL, published_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, document_id INT NOT NULL, INDEX IDX_1351564EC33F7837 (document_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE legal_acceptance ADD CONSTRAINT FK_9680462FA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE legal_acceptance ADD CONSTRAINT FK_9680462F4BBC2705 FOREIGN KEY (version_id) REFERENCES legal_document_version (id)');
        $this->addSql('ALTER TABLE legal_article ADD CONSTRAINT FK_C50190084BBC2705 FOREIGN KEY (version_id) REFERENCES legal_document_version (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE legal_document_version ADD CONSTRAINT FK_1351564EC33F7837 FOREIGN KEY (document_id) REFERENCES legal_document (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE legal_acceptance DROP FOREIGN KEY FK_9680462FA76ED395');
        $this->addSql('ALTER TABLE legal_acceptance DROP FOREIGN KEY FK_9680462F4BBC2705');
        $this->addSql('ALTER TABLE legal_article DROP FOREIGN KEY FK_C50190084BBC2705');
        $this->addSql('ALTER TABLE legal_document_version DROP FOREIGN KEY FK_1351564EC33F7837');
        $this->addSql('DROP TABLE legal_acceptance');
        $this->addSql('DROP TABLE legal_article');
        $this->addSql('DROP TABLE legal_document');
        $this->addSql('DROP TABLE legal_document_version');
    }
}
