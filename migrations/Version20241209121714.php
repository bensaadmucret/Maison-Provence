<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241209121714 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C1727ACA70');
        $this->addSql('ALTER TABLE category ADD seo_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C197E3DD86 FOREIGN KEY (seo_id) REFERENCES seo (id)');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C1727ACA70 FOREIGN KEY (parent_id) REFERENCES category (id) ON DELETE SET NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_64C19C1989D9B62 ON category (slug)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_64C19C197E3DD86 ON category (seo_id)');
        $this->addSql('CREATE INDEX idx_category_name ON category (name)');
        $this->addSql('CREATE INDEX idx_category_slug ON category (slug)');
        $this->addSql('ALTER TABLE category RENAME INDEX idx_64c19c1727aca70 TO idx_category_parent');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD97E3DD86');
        $this->addSql('ALTER TABLE product CHANGE price price NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD97E3DD86 FOREIGN KEY (seo_id) REFERENCES seo (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX idx_product_name ON product (name)');
        $this->addSql('CREATE INDEX idx_product_active_featured ON product (is_active, is_featured)');
        $this->addSql('ALTER TABLE product RENAME INDEX idx_d34a04ad12469de2 TO idx_product_category');
        $this->addSql('ALTER TABLE product RENAME INDEX uniq_d34a04ad989d9b62 TO UNIQ_SLUG');
        $this->addSql('ALTER TABLE seo DROP FOREIGN KEY FK_6C71EC3012469DE2');
        $this->addSql('DROP INDEX IDX_6C71EC3012469DE2 ON seo');
        $this->addSql('ALTER TABLE seo DROP category_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE seo ADD category_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE seo ADD CONSTRAINT FK_6C71EC3012469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('CREATE INDEX IDX_6C71EC3012469DE2 ON seo (category_id)');
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C197E3DD86');
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C1727ACA70');
        $this->addSql('DROP INDEX UNIQ_64C19C1989D9B62 ON category');
        $this->addSql('DROP INDEX UNIQ_64C19C197E3DD86 ON category');
        $this->addSql('DROP INDEX idx_category_name ON category');
        $this->addSql('DROP INDEX idx_category_slug ON category');
        $this->addSql('ALTER TABLE category DROP seo_id');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C1727ACA70 FOREIGN KEY (parent_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE category RENAME INDEX idx_category_parent TO IDX_64C19C1727ACA70');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD97E3DD86');
        $this->addSql('DROP INDEX idx_product_name ON product');
        $this->addSql('DROP INDEX idx_product_active_featured ON product');
        $this->addSql('ALTER TABLE product CHANGE price price DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD97E3DD86 FOREIGN KEY (seo_id) REFERENCES seo (id)');
        $this->addSql('ALTER TABLE product RENAME INDEX idx_product_category TO IDX_D34A04AD12469DE2');
        $this->addSql('ALTER TABLE product RENAME INDEX uniq_slug TO UNIQ_D34A04AD989D9B62');
    }
}
