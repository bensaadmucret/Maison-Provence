<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241204024753 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_ecommerce_enabled_new field with default value';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE site_configuration ADD is_ecommerce_enabled_new BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('UPDATE site_configuration SET is_ecommerce_enabled_new = is_ecommerce_enabled');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE site_configuration DROP is_ecommerce_enabled_new');
    }
}
