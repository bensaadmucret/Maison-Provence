<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241124213625 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE seo ADD category_id INT NOT NULL');
        $this->addSql('ALTER TABLE seo ADD CONSTRAINT FK_6C71EC3012469DE2 FOREIGN KEY (category_id) REFERENCES category (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_6C71EC3012469DE2 ON seo (category_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE seo DROP CONSTRAINT FK_6C71EC3012469DE2');
        $this->addSql('DROP INDEX IDX_6C71EC3012469DE2');
        $this->addSql('ALTER TABLE seo DROP category_id');
    }
}
