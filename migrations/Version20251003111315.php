<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251003111315 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE document (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', created_by BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', updated_by BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', transaction_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', project_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', uploaded_by_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', active TINYINT(1) NOT NULL, deleted_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, notes LONGTEXT DEFAULT NULL, document_type VARCHAR(32) NOT NULL, file_name VARCHAR(255) NOT NULL, file_path VARCHAR(500) NOT NULL, mime_type VARCHAR(100) DEFAULT NULL, file_size INT DEFAULT NULL, description LONGTEXT DEFAULT NULL, is_public TINYINT(1) NOT NULL, version INT NOT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_D8698A76DE12AB56 (created_by), INDEX IDX_D8698A7616FE72E1 (updated_by), INDEX IDX_D8698A762FC0CB0F (transaction_id), INDEX IDX_D8698A76166D1F9C (project_id), INDEX IDX_D8698A76A2B28FE8 (uploaded_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE invoice (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', created_by BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', updated_by BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', transaction_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', sent_by_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', active TINYINT(1) NOT NULL, deleted_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, notes LONGTEXT DEFAULT NULL, invoice_number VARCHAR(50) NOT NULL, invoice_type VARCHAR(32) NOT NULL, status VARCHAR(32) NOT NULL, payment_status VARCHAR(32) NOT NULL, invoice_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', due_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', subtotal NUMERIC(10, 2) NOT NULL, tax_rate NUMERIC(5, 2) NOT NULL, tax_amount NUMERIC(10, 2) NOT NULL, total_amount NUMERIC(10, 2) NOT NULL, paid_amount NUMERIC(10, 2) NOT NULL, payment_terms VARCHAR(255) DEFAULT NULL, sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', paid_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', payment_method VARCHAR(100) DEFAULT NULL, payment_reference VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_906517442DA68207 (invoice_number), INDEX IDX_90651744DE12AB56 (created_by), INDEX IDX_9065174416FE72E1 (updated_by), INDEX IDX_906517442FC0CB0F (transaction_id), INDEX IDX_90651744A45BB98C (sent_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE invoice_item (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', created_by BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', updated_by BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', invoice_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', project_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', active TINYINT(1) NOT NULL, deleted_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, notes LONGTEXT DEFAULT NULL, position INT NOT NULL, description VARCHAR(500) NOT NULL, quantity NUMERIC(10, 2) NOT NULL, unit VARCHAR(50) NOT NULL, unit_price NUMERIC(10, 2) NOT NULL, total_price NUMERIC(10, 2) NOT NULL, INDEX IDX_1DDE477BDE12AB56 (created_by), INDEX IDX_1DDE477B16FE72E1 (updated_by), INDEX IDX_1DDE477B2989F1FD (invoice_id), INDEX IDX_1DDE477B166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE offer (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', created_by BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', updated_by BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', transaction_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', sent_by_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', active TINYINT(1) NOT NULL, deleted_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, notes LONGTEXT DEFAULT NULL, offer_number VARCHAR(50) NOT NULL, version INT NOT NULL, status VARCHAR(32) NOT NULL, valid_until DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', subtotal NUMERIC(10, 2) NOT NULL, tax_rate NUMERIC(5, 2) NOT NULL, tax_amount NUMERIC(10, 2) NOT NULL, total_amount NUMERIC(10, 2) NOT NULL, terms LONGTEXT DEFAULT NULL, customer_notes LONGTEXT DEFAULT NULL, sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', accepted_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', accepted_by VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_29D6873E4FC568F5 (offer_number), INDEX IDX_29D6873EDE12AB56 (created_by), INDEX IDX_29D6873E16FE72E1 (updated_by), INDEX IDX_29D6873E2FC0CB0F (transaction_id), INDEX IDX_29D6873EA45BB98C (sent_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE offer_item (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', created_by BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', updated_by BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', offer_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', project_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', active TINYINT(1) NOT NULL, deleted_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, notes LONGTEXT DEFAULT NULL, position INT NOT NULL, description VARCHAR(500) NOT NULL, quantity NUMERIC(10, 2) NOT NULL, unit VARCHAR(50) NOT NULL, unit_price NUMERIC(10, 2) NOT NULL, total_price NUMERIC(10, 2) NOT NULL, INDEX IDX_E1E30B09DE12AB56 (created_by), INDEX IDX_E1E30B0916FE72E1 (updated_by), INDEX IDX_E1E30B0953C674EE (offer_id), INDEX IDX_E1E30B09166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE transaction (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', created_by BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', updated_by BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', customer_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', primary_contact_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', assigned_to_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', category_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', active TINYINT(1) NOT NULL, deleted_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, notes LONGTEXT DEFAULT NULL, transaction_number VARCHAR(50) NOT NULL, transaction_type VARCHAR(32) NOT NULL, status VARCHAR(32) NOT NULL, total_value NUMERIC(10, 2) DEFAULT NULL, currency VARCHAR(3) NOT NULL, description LONGTEXT DEFAULT NULL, internal_notes LONGTEXT DEFAULT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(255) DEFAULT NULL, started_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ended_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_723705D1E0ED6D14 (transaction_number), INDEX IDX_723705D1DE12AB56 (created_by), INDEX IDX_723705D116FE72E1 (updated_by), INDEX IDX_723705D19395C3F3 (customer_id), INDEX IDX_723705D1D905C92C (primary_contact_id), INDEX IDX_723705D1F4BD7827 (assigned_to_id), INDEX IDX_723705D112469DE2 (category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE transaction_contact (transaction_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', contact_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', INDEX IDX_3AC8DADA2FC0CB0F (transaction_id), INDEX IDX_3AC8DADAE7A1254A (contact_id), PRIMARY KEY(transaction_id, contact_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A76DE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A7616FE72E1 FOREIGN KEY (updated_by) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A762FC0CB0F FOREIGN KEY (transaction_id) REFERENCES transaction (id)');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A76166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A76A2B28FE8 FOREIGN KEY (uploaded_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_90651744DE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_9065174416FE72E1 FOREIGN KEY (updated_by) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_906517442FC0CB0F FOREIGN KEY (transaction_id) REFERENCES transaction (id)');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_90651744A45BB98C FOREIGN KEY (sent_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE invoice_item ADD CONSTRAINT FK_1DDE477BDE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE invoice_item ADD CONSTRAINT FK_1DDE477B16FE72E1 FOREIGN KEY (updated_by) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE invoice_item ADD CONSTRAINT FK_1DDE477B2989F1FD FOREIGN KEY (invoice_id) REFERENCES invoice (id)');
        $this->addSql('ALTER TABLE invoice_item ADD CONSTRAINT FK_1DDE477B166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE offer ADD CONSTRAINT FK_29D6873EDE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE offer ADD CONSTRAINT FK_29D6873E16FE72E1 FOREIGN KEY (updated_by) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE offer ADD CONSTRAINT FK_29D6873E2FC0CB0F FOREIGN KEY (transaction_id) REFERENCES transaction (id)');
        $this->addSql('ALTER TABLE offer ADD CONSTRAINT FK_29D6873EA45BB98C FOREIGN KEY (sent_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE offer_item ADD CONSTRAINT FK_E1E30B09DE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE offer_item ADD CONSTRAINT FK_E1E30B0916FE72E1 FOREIGN KEY (updated_by) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE offer_item ADD CONSTRAINT FK_E1E30B0953C674EE FOREIGN KEY (offer_id) REFERENCES offer (id)');
        $this->addSql('ALTER TABLE offer_item ADD CONSTRAINT FK_E1E30B09166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1DE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D116FE72E1 FOREIGN KEY (updated_by) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D19395C3F3 FOREIGN KEY (customer_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1D905C92C FOREIGN KEY (primary_contact_id) REFERENCES contacts (id)');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1F4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D112469DE2 FOREIGN KEY (category_id) REFERENCES categories (id)');
        $this->addSql('ALTER TABLE transaction_contact ADD CONSTRAINT FK_3AC8DADA2FC0CB0F FOREIGN KEY (transaction_id) REFERENCES transaction (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE transaction_contact ADD CONSTRAINT FK_3AC8DADAE7A1254A FOREIGN KEY (contact_id) REFERENCES contacts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE campaign ADD transaction_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE campaign ADD CONSTRAINT FK_1F1512DD2FC0CB0F FOREIGN KEY (transaction_id) REFERENCES transaction (id)');
        $this->addSql('CREATE INDEX IDX_1F1512DD2FC0CB0F ON campaign (transaction_id)');
        $this->addSql('ALTER TABLE project ADD transaction_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', ADD estimated_hours NUMERIC(8, 2) DEFAULT NULL, ADD actual_hours NUMERIC(8, 2) DEFAULT NULL, ADD estimated_cost NUMERIC(10, 2) DEFAULT NULL, ADD actual_cost NUMERIC(10, 2) DEFAULT NULL, ADD billing_status VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE2FC0CB0F FOREIGN KEY (transaction_id) REFERENCES transaction (id)');
        $this->addSql('CREATE INDEX IDX_2FB3D0EE2FC0CB0F ON project (transaction_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE campaign DROP FOREIGN KEY FK_1F1512DD2FC0CB0F');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EE2FC0CB0F');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A76DE12AB56');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A7616FE72E1');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A762FC0CB0F');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A76166D1F9C');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A76A2B28FE8');
        $this->addSql('ALTER TABLE invoice DROP FOREIGN KEY FK_90651744DE12AB56');
        $this->addSql('ALTER TABLE invoice DROP FOREIGN KEY FK_9065174416FE72E1');
        $this->addSql('ALTER TABLE invoice DROP FOREIGN KEY FK_906517442FC0CB0F');
        $this->addSql('ALTER TABLE invoice DROP FOREIGN KEY FK_90651744A45BB98C');
        $this->addSql('ALTER TABLE invoice_item DROP FOREIGN KEY FK_1DDE477BDE12AB56');
        $this->addSql('ALTER TABLE invoice_item DROP FOREIGN KEY FK_1DDE477B16FE72E1');
        $this->addSql('ALTER TABLE invoice_item DROP FOREIGN KEY FK_1DDE477B2989F1FD');
        $this->addSql('ALTER TABLE invoice_item DROP FOREIGN KEY FK_1DDE477B166D1F9C');
        $this->addSql('ALTER TABLE offer DROP FOREIGN KEY FK_29D6873EDE12AB56');
        $this->addSql('ALTER TABLE offer DROP FOREIGN KEY FK_29D6873E16FE72E1');
        $this->addSql('ALTER TABLE offer DROP FOREIGN KEY FK_29D6873E2FC0CB0F');
        $this->addSql('ALTER TABLE offer DROP FOREIGN KEY FK_29D6873EA45BB98C');
        $this->addSql('ALTER TABLE offer_item DROP FOREIGN KEY FK_E1E30B09DE12AB56');
        $this->addSql('ALTER TABLE offer_item DROP FOREIGN KEY FK_E1E30B0916FE72E1');
        $this->addSql('ALTER TABLE offer_item DROP FOREIGN KEY FK_E1E30B0953C674EE');
        $this->addSql('ALTER TABLE offer_item DROP FOREIGN KEY FK_E1E30B09166D1F9C');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D1DE12AB56');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D116FE72E1');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D19395C3F3');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D1D905C92C');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D1F4BD7827');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D112469DE2');
        $this->addSql('ALTER TABLE transaction_contact DROP FOREIGN KEY FK_3AC8DADA2FC0CB0F');
        $this->addSql('ALTER TABLE transaction_contact DROP FOREIGN KEY FK_3AC8DADAE7A1254A');
        $this->addSql('DROP TABLE document');
        $this->addSql('DROP TABLE invoice');
        $this->addSql('DROP TABLE invoice_item');
        $this->addSql('DROP TABLE offer');
        $this->addSql('DROP TABLE offer_item');
        $this->addSql('DROP TABLE transaction');
        $this->addSql('DROP TABLE transaction_contact');
        $this->addSql('DROP INDEX IDX_2FB3D0EE2FC0CB0F ON project');
        $this->addSql('ALTER TABLE project DROP transaction_id, DROP estimated_hours, DROP actual_hours, DROP estimated_cost, DROP actual_cost, DROP billing_status');
        $this->addSql('DROP INDEX IDX_1F1512DD2FC0CB0F ON campaign');
        $this->addSql('ALTER TABLE campaign DROP transaction_id');
    }
}
