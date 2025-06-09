<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250608224816 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'JobRole fields for api';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE job_role ADD source VARCHAR(100) DEFAULT 'careers.govt.nz' NOT NULL, ADD last_synced_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', ADD sync_status VARCHAR(50) DEFAULT 'synced' NOT NULL, ADD sync_error LONGTEXT DEFAULT NULL, ADD manually_edited TINYINT(1) DEFAULT 0 NOT NULL, ADD is_archived TINYINT(1) DEFAULT 0 NOT NULL, CHANGE anzsco anzsco VARCHAR(255) NOT NULL, CHANGE job_code job_code VARCHAR(255) NOT NULL, CHANGE entry_requirements entry_requirements VARCHAR(255) NOT NULL, CHANGE years_of_training years_of_training VARCHAR(255) NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE job_role DROP source, DROP last_synced_at, DROP sync_status, DROP sync_error, DROP manually_edited, DROP is_archived, CHANGE anzsco anzsco VARCHAR(6) NOT NULL, CHANGE job_code job_code VARCHAR(50) NOT NULL, CHANGE entry_requirements entry_requirements VARCHAR(50) NOT NULL, CHANGE years_of_training years_of_training VARCHAR(10) NOT NULL
        SQL);
    }
}
