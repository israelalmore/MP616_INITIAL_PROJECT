<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261020164244 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace nurse credentials with the single nurses table structure.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE nurse_credential DROP FOREIGN KEY FK_71DE743B7373BFAA');
        $this->addSql('DROP TABLE nurse_credential');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('RENAME TABLE nurse TO nurses');
        $this->addSql('ALTER TABLE nurses CHANGE user username VARCHAR(64) NOT NULL');
        $this->addSql('ALTER TABLE nurses DROP INDEX UNIQ_D27E6D438D93D649, ADD UNIQUE INDEX UNIQ_NURSE_USERNAME (username)');
        $this->addSql('ALTER TABLE nurses ADD name VARCHAR(100) DEFAULT NULL, ADD created_at DATETIME DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL, ADD profile_image VARCHAR(255) DEFAULT NULL, ADD email VARCHAR(180) DEFAULT NULL, ADD status VARCHAR(20) DEFAULT NULL');
        $this->addSql("UPDATE nurses SET name = username, created_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP, email = CONCAT(username, '@example.invalid'), status = 'active'");
        $this->addSql('ALTER TABLE nurses MODIFY name VARCHAR(100) NOT NULL, MODIFY created_at DATETIME NOT NULL, MODIFY updated_at DATETIME NOT NULL, MODIFY email VARCHAR(180) NOT NULL, MODIFY status VARCHAR(20) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_NURSE_EMAIL ON nurses (email)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_NURSE_EMAIL ON nurses');
        $this->addSql('ALTER TABLE nurses DROP name, DROP created_at, DROP updated_at, DROP profile_image, DROP email, DROP status');
        $this->addSql('ALTER TABLE nurses CHANGE username user VARCHAR(64) NOT NULL');
        $this->addSql('ALTER TABLE nurses DROP INDEX UNIQ_NURSE_USERNAME, ADD UNIQUE INDEX UNIQ_D27E6D438D93D649 (user)');
        $this->addSql('RENAME TABLE nurses TO nurse');
        $this->addSql('CREATE TABLE nurse_credential (id INT AUTO_INCREMENT NOT NULL, license_number VARCHAR(30) NOT NULL, certification VARCHAR(150) NOT NULL, issuing_body VARCHAR(150) NOT NULL, issue_date DATE NOT NULL, expiration_date DATE NOT NULL, nurse_id INT NOT NULL, INDEX IDX_71DE743B7373BFAA (nurse_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE nurse_credential ADD CONSTRAINT FK_71DE743B7373BFAA FOREIGN KEY (nurse_id) REFERENCES nurse (id)');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id))');
    }
}
