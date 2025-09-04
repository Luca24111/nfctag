<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250813093129 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE nfc_scans (id INT AUTO_INCREMENT NOT NULL, device_id VARCHAR(255) NOT NULL, nfc_id VARCHAR(255) NOT NULL, product_name VARCHAR(255) NOT NULL, product_status VARCHAR(50) NOT NULL, scan_type VARCHAR(50) NOT NULL, timestamp DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', user_id INT DEFAULT NULL, device_info LONGTEXT DEFAULT NULL, location VARCHAR(255) DEFAULT NULL, sync_status VARCHAR(50) NOT NULL, metadata JSON DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX UNIQ_CF2C0EB494A4C7D4 (device_id), INDEX idx_device_id (device_id), INDEX idx_user_id (user_id), INDEX idx_timestamp (timestamp), INDEX idx_sync_status (sync_status), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP TABLE nfc_scans
        SQL);
    }
}
