<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260303082409 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE sortie_participant (sortie_id INT NOT NULL, participant_id INT NOT NULL, INDEX IDX_E6D4CDADCC72D953 (sortie_id), INDEX IDX_E6D4CDAD9D1C3019 (participant_id), PRIMARY KEY (sortie_id, participant_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE sortie_participant ADD CONSTRAINT FK_E6D4CDADCC72D953 FOREIGN KEY (sortie_id) REFERENCES sortie (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sortie_participant ADD CONSTRAINT FK_E6D4CDAD9D1C3019 FOREIGN KEY (participant_id) REFERENCES participant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE participant ADD CONSTRAINT FK_D79F6B11AF5D55E1 FOREIGN KEY (campus_id) REFERENCES campus (id)');
        $this->addSql('ALTER TABLE sortie ADD organisateur_id INT NOT NULL, ADD campus_id INT NOT NULL');
        $this->addSql('ALTER TABLE sortie ADD CONSTRAINT FK_3C3FD3F2D936B2FA FOREIGN KEY (organisateur_id) REFERENCES participant (id)');
        $this->addSql('ALTER TABLE sortie ADD CONSTRAINT FK_3C3FD3F2AF5D55E1 FOREIGN KEY (campus_id) REFERENCES campus (id)');
        $this->addSql('CREATE INDEX IDX_3C3FD3F2D936B2FA ON sortie (organisateur_id)');
        $this->addSql('CREATE INDEX IDX_3C3FD3F2AF5D55E1 ON sortie (campus_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sortie_participant DROP FOREIGN KEY FK_E6D4CDADCC72D953');
        $this->addSql('ALTER TABLE sortie_participant DROP FOREIGN KEY FK_E6D4CDAD9D1C3019');
        $this->addSql('DROP TABLE sortie_participant');
        $this->addSql('ALTER TABLE participant DROP FOREIGN KEY FK_D79F6B11AF5D55E1');
        $this->addSql('ALTER TABLE sortie DROP FOREIGN KEY FK_3C3FD3F2D936B2FA');
        $this->addSql('ALTER TABLE sortie DROP FOREIGN KEY FK_3C3FD3F2AF5D55E1');
        $this->addSql('DROP INDEX IDX_3C3FD3F2D936B2FA ON sortie');
        $this->addSql('DROP INDEX IDX_3C3FD3F2AF5D55E1 ON sortie');
        $this->addSql('ALTER TABLE sortie DROP organisateur_id, DROP campus_id');
    }
}
