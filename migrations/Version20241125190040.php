<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241125190040 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "order" ADD shipping_address_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE "order" ADD billing_address_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE "order" DROP shipping_address');
        $this->addSql('ALTER TABLE "order" ADD CONSTRAINT FK_F52993984D4CFF2B FOREIGN KEY (shipping_address_id) REFERENCES address (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "order" ADD CONSTRAINT FK_F529939879D0C0E4 FOREIGN KEY (billing_address_id) REFERENCES address (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_F52993984D4CFF2B ON "order" (shipping_address_id)');
        $this->addSql('CREATE INDEX IDX_F529939879D0C0E4 ON "order" (billing_address_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE "order" DROP CONSTRAINT FK_F52993984D4CFF2B');
        $this->addSql('ALTER TABLE "order" DROP CONSTRAINT FK_F529939879D0C0E4');
        $this->addSql('DROP INDEX IDX_F52993984D4CFF2B');
        $this->addSql('DROP INDEX IDX_F529939879D0C0E4');
        $this->addSql('ALTER TABLE "order" ADD shipping_address TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE "order" DROP shipping_address_id');
        $this->addSql('ALTER TABLE "order" DROP billing_address_id');
    }
}
