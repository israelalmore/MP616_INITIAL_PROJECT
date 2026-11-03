<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261103120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add unique indexes on nurses.username and nurses.email';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX UNIQ_NURSE_USERNAME ON nurses (username)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_NURSE_EMAIL ON nurses (email)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_NURSE_USERNAME');
        $this->addSql('DROP INDEX UNIQ_NURSE_EMAIL');
    }
}
