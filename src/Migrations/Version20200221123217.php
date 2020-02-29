<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200221123217 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE geom_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE parcel_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE geom (id INT NOT NULL, geom geometry(GEOMETRY, 0) DEFAULT NULL, original_geom geometry(GEOMETRY, 0) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE parcel (id INT NOT NULL, geom_id INT DEFAULT NULL, user_id INT DEFAULT NULL, cad_num VARCHAR(20) NOT NULL, area DOUBLE PRECISION DEFAULT NULL, use VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C99B5D60C06AE09C ON parcel (geom_id)');
        $this->addSql('CREATE INDEX IDX_C99B5D60A76ED395 ON parcel (user_id)');
        $this->addSql('COMMENT ON COLUMN parcel.use IS \'Фактичне використання\'');
        $this->addSql('ALTER TABLE parcel ADD CONSTRAINT FK_C99B5D60C06AE09C FOREIGN KEY (geom_id) REFERENCES geom (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE parcel ADD CONSTRAINT FK_C99B5D60A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE parcel DROP CONSTRAINT FK_C99B5D60C06AE09C');
        $this->addSql('DROP SEQUENCE geom_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE parcel_id_seq CASCADE');
        $this->addSql('DROP TABLE geom');
        $this->addSql('DROP TABLE parcel');
    }
}
