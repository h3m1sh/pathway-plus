<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250613044402 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE user_skills (user_id INT NOT NULL, skill_id INT NOT NULL, INDEX IDX_B0630D4DA76ED395 (user_id), INDEX IDX_B0630D4D5585C142 (skill_id), PRIMARY KEY(user_id, skill_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user_micro_credentials (user_id INT NOT NULL, micro_credential_id INT NOT NULL, INDEX IDX_1A3D3655A76ED395 (user_id), INDEX IDX_1A3D3655ACDB9113 (micro_credential_id), PRIMARY KEY(user_id, micro_credential_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_skills ADD CONSTRAINT FK_B0630D4DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_skills ADD CONSTRAINT FK_B0630D4D5585C142 FOREIGN KEY (skill_id) REFERENCES skill (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_micro_credentials ADD CONSTRAINT FK_1A3D3655A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_micro_credentials ADD CONSTRAINT FK_1A3D3655ACDB9113 FOREIGN KEY (micro_credential_id) REFERENCES micro_credential (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE user_skills DROP FOREIGN KEY FK_B0630D4DA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_skills DROP FOREIGN KEY FK_B0630D4D5585C142
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_micro_credentials DROP FOREIGN KEY FK_1A3D3655A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_micro_credentials DROP FOREIGN KEY FK_1A3D3655ACDB9113
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_skills
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_micro_credentials
        SQL);
    }
}
