<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250708221352 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE prodotto ADD id INT AUTO_INCREMENT NOT NULL, CHANGE nfc_id nfc_id INT DEFAULT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_8176041B25DD09AB ON prodotto (nfc_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE prodotti_eventi DROP FOREIGN KEY FK_4C860E27AB38982D
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE prodotti_eventi ADD CONSTRAINT FK_4C860E27AB38982D FOREIGN KEY (prodotto_id) REFERENCES prodotto (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user RENAME INDEX uniq_8d93d649e7927c74 TO UNIQ_IDENTIFIER_EMAIL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE prodotti_eventi DROP FOREIGN KEY FK_4C860E27AB38982D
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE prodotti_eventi ADD CONSTRAINT FK_4C860E27AB38982D FOREIGN KEY (prodotto_id) REFERENCES prodotto (nfc_id) ON UPDATE NO ACTION ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE prodotto MODIFY id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_8176041B25DD09AB ON prodotto
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX `PRIMARY` ON prodotto
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE prodotto DROP id, CHANGE nfc_id nfc_id INT AUTO_INCREMENT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE prodotto ADD PRIMARY KEY (nfc_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user RENAME INDEX uniq_identifier_email TO UNIQ_8D93D649E7927C74
        SQL);
    }
}
