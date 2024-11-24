<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241124194937 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE seo (id SERIAL NOT NULL, meta_title VARCHAR(60) NOT NULL, meta_description VARCHAR(160) NOT NULL, canonical_url VARCHAR(255) NOT NULL, meta_keywords JSON NOT NULL, indexable BOOLEAN NOT NULL, followable BOOLEAN NOT NULL, open_graph_data JSON NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE product ADD seo_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD97E3DD86 FOREIGN KEY (seo_id) REFERENCES seo (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D34A04AD97E3DD86 ON product (seo_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE product DROP CONSTRAINT FK_D34A04AD97E3DD86');
        $this->addSql('DROP TABLE seo');
        $this->addSql('DROP INDEX UNIQ_D34A04AD97E3DD86');
        $this->addSql('ALTER TABLE product DROP seo_id');
    }
}
