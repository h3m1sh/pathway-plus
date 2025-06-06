<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250606035105 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Added More Fields into JobRole';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE job_role ADD updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', ADD anzsco VARCHAR(6) NOT NULL, ADD job_code VARCHAR(50) NOT NULL, ADD entry_requirements VARCHAR(50) NOT NULL, ADD job_opportunities VARCHAR(255) NOT NULL, ADD years_of_training VARCHAR(10) NOT NULL, ADD job_opportunities_caption VARCHAR(255) NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE job_role DROP updated_at, DROP anzsco, DROP job_code, DROP entry_requirements, DROP job_opportunities, DROP years_of_training, DROP job_opportunities_caption
        SQL);
    }
}
