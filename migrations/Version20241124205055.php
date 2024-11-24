<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241124205055 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category ADD level INT NOT NULL');
        $this->addSql('ALTER TABLE seo ALTER meta_title DROP NOT NULL');
        $this->addSql('ALTER TABLE seo ALTER meta_description DROP NOT NULL');
        $this->addSql('ALTER TABLE seo ALTER canonical_url DROP NOT NULL');
        $this->addSql('ALTER TABLE seo ALTER meta_keywords DROP NOT NULL');
        $this->addSql('ALTER TABLE seo ALTER open_graph_data DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE category DROP level');
        $this->addSql('ALTER TABLE seo ALTER meta_title SET NOT NULL');
        $this->addSql('ALTER TABLE seo ALTER meta_description SET NOT NULL');
        $this->addSql('ALTER TABLE seo ALTER canonical_url SET NOT NULL');
        $this->addSql('ALTER TABLE seo ALTER meta_keywords SET NOT NULL');
        $this->addSql('ALTER TABLE seo ALTER open_graph_data SET NOT NULL');
    }
}
