<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251127220739 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE livre ADD frais_emprunt NUMERIC(10, 2) DEFAULT NULL, ADD caution NUMERIC(10, 2) DEFAULT NULL, ADD emprunt_disponible TINYINT(1) NOT NULL, ADD duree_max_emprunt INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE livre DROP frais_emprunt, DROP caution, DROP emprunt_disponible, DROP duree_max_emprunt');
    }
}
