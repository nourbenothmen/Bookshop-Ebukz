<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251127200013 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande ADD utilisateur_id INT DEFAULT NULL, ADD telephone VARCHAR(255) NOT NULL, CHANGE total total NUMERIC(10, 2) NOT NULL, CHANGE statut statut VARCHAR(50) NOT NULL, CHANGE prenom prenom VARCHAR(255) NOT NULL, CHANGE nom nom VARCHAR(255) NOT NULL, CHANGE email email VARCHAR(255) NOT NULL, CHANGE adresse_facturation adresse_facturation LONGTEXT NOT NULL, CHANGE adresse_livraison adresse_livraison LONGTEXT DEFAULT NULL, CHANGE notes notes LONGTEXT DEFAULT NULL, CHANGE mode_paiement mode_paiement VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_6EEAA67DFB88E14F ON commande (utilisateur_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DFB88E14F');
        $this->addSql('DROP INDEX IDX_6EEAA67DFB88E14F ON commande');
        $this->addSql('ALTER TABLE commande DROP utilisateur_id, DROP telephone, CHANGE total total VARCHAR(20) NOT NULL, CHANGE statut statut VARCHAR(10) NOT NULL, CHANGE prenom prenom VARCHAR(20) NOT NULL, CHANGE nom nom VARCHAR(20) NOT NULL, CHANGE email email VARCHAR(40) NOT NULL, CHANGE adresse_facturation adresse_facturation VARCHAR(50) DEFAULT NULL, CHANGE adresse_livraison adresse_livraison VARCHAR(50) DEFAULT NULL, CHANGE notes notes VARCHAR(50) DEFAULT NULL, CHANGE mode_paiement mode_paiement VARCHAR(10) DEFAULT NULL');
    }
}
