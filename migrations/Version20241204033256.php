<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241204033256 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migration initiale - Création de toutes les tables';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE "user" (
            id SERIAL NOT NULL,
            email VARCHAR(180) NOT NULL,
            roles JSON NOT NULL,
            password VARCHAR(255) NOT NULL,
            first_name VARCHAR(255) NOT NULL,
            last_name VARCHAR(255) NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            last_login_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');

        $this->addSql('CREATE TABLE site_configuration (
            id SERIAL NOT NULL,
            site_name VARCHAR(255) NOT NULL,
            maintenance_mode BOOLEAN NOT NULL,
            maintenance_message TEXT DEFAULT NULL,
            contact_email VARCHAR(255) NOT NULL,
            contact_phone VARCHAR(255) DEFAULT NULL,
            is_ecommerce_enabled BOOLEAN NOT NULL,
            ecommerce_disabled_message TEXT DEFAULT NULL,
            favicon VARCHAR(255) DEFAULT NULL,
            logo VARCHAR(255) DEFAULT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');

        $this->addSql('CREATE TABLE team_member (
            id SERIAL NOT NULL,
            name VARCHAR(255) NOT NULL,
            firstname VARCHAR(255) NOT NULL,
            role VARCHAR(255) NOT NULL,
            description TEXT DEFAULT NULL,
            photo VARCHAR(255) DEFAULT NULL,
            position INTEGER NOT NULL DEFAULT 0,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');

        $this->addSql('CREATE TABLE contact (
            id SERIAL NOT NULL,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');

        $this->addSql('CREATE TABLE address (
            id SERIAL NOT NULL,
            user_id INT NOT NULL,
            street VARCHAR(255) NOT NULL,
            city VARCHAR(255) NOT NULL,
            postal_code VARCHAR(10) NOT NULL,
            country VARCHAR(2) NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id),
            CONSTRAINT FK_D4E6F81A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        )');
        $this->addSql('CREATE INDEX IDX_D4E6F81A76ED395 ON address (user_id)');

        $this->addSql('CREATE TABLE "order" (
            id SERIAL NOT NULL,
            user_id INT NOT NULL,
            status VARCHAR(50) NOT NULL,
            total_amount NUMERIC(10, 2) NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id),
            CONSTRAINT FK_F5299398A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        )');
        $this->addSql('CREATE INDEX IDX_F5299398A76ED395 ON "order" (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE IF EXISTS "order"');
        $this->addSql('DROP TABLE IF EXISTS address');
        $this->addSql('DROP TABLE IF EXISTS contact');
        $this->addSql('DROP TABLE IF EXISTS team_member');
        $this->addSql('DROP TABLE IF EXISTS site_configuration');
        $this->addSql('DROP TABLE IF EXISTS "user"');
    }
}
