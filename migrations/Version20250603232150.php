<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250603232150 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE job_role (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, industry VARCHAR(100) DEFAULT NULL, salary_range VARCHAR(100) DEFAULT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE job_role_skill (job_role_id INT NOT NULL, skill_id INT NOT NULL, INDEX IDX_C3969C454CEC9537 (job_role_id), INDEX IDX_C3969C455585C142 (skill_id), PRIMARY KEY(job_role_id, skill_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE micro_credential (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, badge_url VARCHAR(500) DEFAULT NULL, level VARCHAR(50) DEFAULT NULL, category VARCHAR(100) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE micro_credential_skill (micro_credential_id INT NOT NULL, skill_id INT NOT NULL, INDEX IDX_20031292ACDB9113 (micro_credential_id), INDEX IDX_200312925585C142 (skill_id), PRIMARY KEY(micro_credential_id, skill_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE skill (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, category VARCHAR(100) DEFAULT NULL, difficulty VARCHAR(50) DEFAULT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE student_progress (id INT AUTO_INCREMENT NOT NULL, student_id INT DEFAULT NULL, micro_credential_id INT DEFAULT NULL, date_earned DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', status VARCHAR(255) DEFAULT NULL, verified_by VARCHAR(255) DEFAULT NULL, note LONGTEXT DEFAULT NULL, INDEX IDX_918ABEDDCB944F1A (student_id), INDEX IDX_918ABEDDACDB9113 (micro_credential_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE job_role_skill ADD CONSTRAINT FK_C3969C454CEC9537 FOREIGN KEY (job_role_id) REFERENCES job_role (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE job_role_skill ADD CONSTRAINT FK_C3969C455585C142 FOREIGN KEY (skill_id) REFERENCES skill (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE micro_credential_skill ADD CONSTRAINT FK_20031292ACDB9113 FOREIGN KEY (micro_credential_id) REFERENCES micro_credential (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE micro_credential_skill ADD CONSTRAINT FK_200312925585C142 FOREIGN KEY (skill_id) REFERENCES skill (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE student_progress ADD CONSTRAINT FK_918ABEDDCB944F1A FOREIGN KEY (student_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE student_progress ADD CONSTRAINT FK_918ABEDDACDB9113 FOREIGN KEY (micro_credential_id) REFERENCES micro_credential (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE job_role_skill DROP FOREIGN KEY FK_C3969C454CEC9537
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE job_role_skill DROP FOREIGN KEY FK_C3969C455585C142
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE micro_credential_skill DROP FOREIGN KEY FK_20031292ACDB9113
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE micro_credential_skill DROP FOREIGN KEY FK_200312925585C142
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE student_progress DROP FOREIGN KEY FK_918ABEDDCB944F1A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE student_progress DROP FOREIGN KEY FK_918ABEDDACDB9113
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE job_role
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE job_role_skill
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE micro_credential
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE micro_credential_skill
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE skill
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE student_progress
        SQL);
    }
}
