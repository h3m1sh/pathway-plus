<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250610225609 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE user_job_role_interests (user_id INT NOT NULL, job_role_id INT NOT NULL, INDEX IDX_A77D6008A76ED395 (user_id), INDEX IDX_A77D60084CEC9537 (job_role_id), PRIMARY KEY(user_id, job_role_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_job_role_interests ADD CONSTRAINT FK_A77D6008A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_job_role_interests ADD CONSTRAINT FK_A77D60084CEC9537 FOREIGN KEY (job_role_id) REFERENCES job_role (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE user_job_role_interests DROP FOREIGN KEY FK_A77D6008A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_job_role_interests DROP FOREIGN KEY FK_A77D60084CEC9537
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_job_role_interests
        SQL);
    }
}
