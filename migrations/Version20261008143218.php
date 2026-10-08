<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261008143218 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE nurse (id INT AUTO_INCREMENT NOT NULL, user VARCHAR(64) NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_D27E6D438D93D649 (user), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE nurse_credential (id INT AUTO_INCREMENT NOT NULL, license_number VARCHAR(30) NOT NULL, certification VARCHAR(150) NOT NULL, issuing_body VARCHAR(150) NOT NULL, issue_date DATE NOT NULL, expiration_date DATE NOT NULL, nurse_id INT NOT NULL, INDEX IDX_71DE743B7373BFAA (nurse_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE nurse_credential ADD CONSTRAINT FK_71DE743B7373BFAA FOREIGN KEY (nurse_id) REFERENCES nurse (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE nurse_credential DROP FOREIGN KEY FK_71DE743B7373BFAA');
        $this->addSql('DROP TABLE nurse');
        $this->addSql('DROP TABLE nurse_credential');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
