<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251127221806 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE emprunt ADD emprunteur_id INT NOT NULL, ADD livre_id INT NOT NULL, CHANGE date_emprunt date_emprunt DATETIME NOT NULL, CHANGE date_retour_prevue date_retour_prevue DATETIME NOT NULL, CHANGE frais_emprunt frais_emprunt NUMERIC(10, 2) NOT NULL, CHANGE caution caution NUMERIC(10, 2) NOT NULL, CHANGE statut statut VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE emprunt ADD CONSTRAINT FK_364071D7F0840037 FOREIGN KEY (emprunteur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE emprunt ADD CONSTRAINT FK_364071D737D925CB FOREIGN KEY (livre_id) REFERENCES livre (id)');
        $this->addSql('CREATE INDEX IDX_364071D7F0840037 ON emprunt (emprunteur_id)');
        $this->addSql('CREATE INDEX IDX_364071D737D925CB ON emprunt (livre_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE emprunt DROP FOREIGN KEY FK_364071D7F0840037');
        $this->addSql('ALTER TABLE emprunt DROP FOREIGN KEY FK_364071D737D925CB');
        $this->addSql('DROP INDEX IDX_364071D7F0840037 ON emprunt');
        $this->addSql('DROP INDEX IDX_364071D737D925CB ON emprunt');
        $this->addSql('ALTER TABLE emprunt DROP emprunteur_id, DROP livre_id, CHANGE date_emprunt date_emprunt DATETIME DEFAULT NULL, CHANGE date_retour_prevue date_retour_prevue DATETIME DEFAULT NULL, CHANGE frais_emprunt frais_emprunt VARCHAR(10) DEFAULT NULL, CHANGE caution caution VARCHAR(10) DEFAULT NULL, CHANGE statut statut VARCHAR(20) NOT NULL');
    }
}
