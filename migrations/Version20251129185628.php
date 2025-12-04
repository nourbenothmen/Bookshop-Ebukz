<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251129185628 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE emprunt ADD justification_retard LONGTEXT DEFAULT NULL, ADD statut_justification VARCHAR(20) DEFAULT NULL, ADD date_justification DATETIME DEFAULT NULL, ADD reponse_admin LONGTEXT DEFAULT NULL, ADD date_reponse_admin DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE emprunt DROP justification_retard, DROP statut_justification, DROP date_justification, DROP reponse_admin, DROP date_reponse_admin');
    }
}
