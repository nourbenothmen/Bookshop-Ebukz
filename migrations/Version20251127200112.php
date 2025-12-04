<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251127200112 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande_item ADD livre_id INT NOT NULL, ADD commande_id INT NOT NULL, CHANGE quantite quantite INT NOT NULL, CHANGE prix_unitaire prix_unitaire NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE commande_item ADD CONSTRAINT FK_747724FD37D925CB FOREIGN KEY (livre_id) REFERENCES livre (id)');
        $this->addSql('ALTER TABLE commande_item ADD CONSTRAINT FK_747724FD82EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id)');
        $this->addSql('CREATE INDEX IDX_747724FD37D925CB ON commande_item (livre_id)');
        $this->addSql('CREATE INDEX IDX_747724FD82EA2E54 ON commande_item (commande_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande_item DROP FOREIGN KEY FK_747724FD37D925CB');
        $this->addSql('ALTER TABLE commande_item DROP FOREIGN KEY FK_747724FD82EA2E54');
        $this->addSql('DROP INDEX IDX_747724FD37D925CB ON commande_item');
        $this->addSql('DROP INDEX IDX_747724FD82EA2E54 ON commande_item');
        $this->addSql('ALTER TABLE commande_item DROP livre_id, DROP commande_id, CHANGE quantite quantite INT DEFAULT NULL, CHANGE prix_unitaire prix_unitaire VARCHAR(10) DEFAULT NULL');
    }
}
