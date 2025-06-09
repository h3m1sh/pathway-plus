<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250609230746 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE job_role CHANGE anzsco anzsco VARCHAR(20) DEFAULT NULL, CHANGE job_code job_code VARCHAR(20) NOT NULL, CHANGE entry_requirements entry_requirements LONGTEXT DEFAULT NULL, CHANGE job_opportunities job_opportunities LONGTEXT DEFAULT NULL, CHANGE years_of_training years_of_training VARCHAR(50) DEFAULT NULL, CHANGE job_opportunities_caption job_opportunities_caption LONGTEXT DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE job_role CHANGE anzsco anzsco VARCHAR(255) NOT NULL, CHANGE job_code job_code VARCHAR(255) NOT NULL, CHANGE entry_requirements entry_requirements VARCHAR(255) NOT NULL, CHANGE job_opportunities job_opportunities VARCHAR(255) NOT NULL, CHANGE years_of_training years_of_training VARCHAR(255) NOT NULL, CHANGE job_opportunities_caption job_opportunities_caption VARCHAR(255) NOT NULL
        SQL);
    }
}
