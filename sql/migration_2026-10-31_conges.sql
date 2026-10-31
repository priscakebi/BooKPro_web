-- ======================================================
-- MIGRATION BOOKPRO - CONGÉS ET FERMETURES EXCEPTIONNELLES
-- ======================================================

CREATE TABLE IF NOT EXISTS conges (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    motif VARCHAR(255) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY user_dates (user_id, date_debut, date_fin),
    CONSTRAINT fk_conges_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT chk_conges_dates CHECK (date_fin >= date_debut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
