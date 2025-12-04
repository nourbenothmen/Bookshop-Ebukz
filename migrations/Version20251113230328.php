<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251113230328 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE livres_auteurs (livre_id INT NOT NULL, auteur_id INT NOT NULL, INDEX IDX_1191A24937D925CB (livre_id), INDEX IDX_1191A24960BB6FE6 (auteur_id), PRIMARY KEY(livre_id, auteur_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE livres_auteurs ADD CONSTRAINT FK_1191A24937D925CB FOREIGN KEY (livre_id) REFERENCES livre (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE livres_auteurs ADD CONSTRAINT FK_1191A24960BB6FE6 FOREIGN KEY (auteur_id) REFERENCES auteur (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE livres_auteurs DROP FOREIGN KEY FK_1191A24937D925CB');
        $this->addSql('ALTER TABLE livres_auteurs DROP FOREIGN KEY FK_1191A24960BB6FE6');
        $this->addSql('DROP TABLE livres_auteurs');
    }
}
