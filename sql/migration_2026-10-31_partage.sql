-- ======================================================
-- MIGRATION BOOKPRO - LIEN COURT, QR CODE, VISITES
-- ======================================================

-- Adresse courte de la page du pro : /p/dupont-plomberie
ALTER TABLE users
    ADD COLUMN slug VARCHAR(60) NULL AFTER photo,
    ADD UNIQUE KEY slug (slug);

-- Visites de la page publique, par jour et par provenance
CREATE TABLE IF NOT EXISTS vues_pro (
    pro_id INT NOT NULL,
    jour DATE NOT NULL,
    source ENUM('qr', 'lien', 'site', 'bouton') NOT NULL,
    nb INT NOT NULL DEFAULT 0,
    PRIMARY KEY (pro_id, jour, source),
    CONSTRAINT fk_vues_pro FOREIGN KEY (pro_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
