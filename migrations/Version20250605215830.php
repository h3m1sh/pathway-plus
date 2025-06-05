<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250605215830 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Added isVisible * updatedAt to MicroCredential';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE micro_credential ADD is_visible TINYINT(1) DEFAULT NULL, ADD updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE student_progress ADD created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE micro_credential DROP is_visible, DROP updated_at
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE student_progress DROP created_at
        SQL);
    }
}
