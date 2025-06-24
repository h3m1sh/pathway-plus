<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250624000334 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE conversation (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, mode VARCHAR(50) NOT NULL, message LONGTEXT NOT NULL, response LONGTEXT NOT NULL, timestamp DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', personalized TINYINT(1) NOT NULL, INDEX IDX_8A8E26E9A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26E9A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE conversation DROP FOREIGN KEY FK_8A8E26E9A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE conversation
        SQL);
    }
}
