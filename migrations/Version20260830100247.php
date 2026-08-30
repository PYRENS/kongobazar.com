<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260830100247 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE home_deals_targeted_category (home_deals_setting_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_97DC98522B38D070 (home_deals_setting_id), INDEX IDX_97DC985212469DE2 (category_id), PRIMARY KEY (home_deals_setting_id, category_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE home_deals_targeted_category ADD CONSTRAINT FK_97DC98522B38D070 FOREIGN KEY (home_deals_setting_id) REFERENCES home_deals_setting (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE home_deals_targeted_category ADD CONSTRAINT FK_97DC985212469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE home_deals_targeted_category DROP FOREIGN KEY FK_97DC98522B38D070');
        $this->addSql('ALTER TABLE home_deals_targeted_category DROP FOREIGN KEY FK_97DC985212469DE2');
        $this->addSql('DROP TABLE home_deals_targeted_category');
    }
}
