-- ======================================================
-- MIGRATION BOOKPRO - 31/10/2026
-- Sauvegarde préalable : backup_avant_refonte_2026-10-31.sql
-- ======================================================

-- InnoDB partout : transactions + clés étrangères
ALTER TABLE services       ENGINE=InnoDB;
ALTER TABLE disponibilites ENGINE=InnoDB;
ALTER TABLE reservations   ENGINE=InnoDB;
ALTER TABLE rendez_vous    ENGINE=InnoDB;

-- Statut obligatoire, valeurs connues
UPDATE reservations SET statut = 'en_attente' WHERE statut IS NULL OR statut = '';
ALTER TABLE reservations
    MODIFY statut ENUM('en_attente','confirmee','refusee','annulee') NOT NULL DEFAULT 'en_attente';

-- Un créneau ne peut avoir qu'UNE réservation active,
-- mais redevient réservable après annulation / refus
ALTER TABLE reservations
    DROP INDEX disponibilite_id,
    ADD COLUMN creneau_actif INT
        GENERATED ALWAYS AS (IF(statut IN ('annulee','refusee'), NULL, disponibilite_id)) STORED,
    ADD UNIQUE KEY creneau_actif (creneau_actif),
    ADD KEY disponibilite_id (disponibilite_id);

-- Un seul avis par réservation
ALTER TABLE avis ADD UNIQUE KEY reservation_id (reservation_id);

-- Clés étrangères
ALTER TABLE services
    ADD CONSTRAINT fk_services_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
ALTER TABLE disponibilites
    ADD CONSTRAINT fk_dispo_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE;
ALTER TABLE reservations
    ADD CONSTRAINT fk_resa_user  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    -- RESTRICT : MySQL refuse CASCADE sur une colonne utilisée par une colonne générée
    ADD CONSTRAINT fk_resa_dispo FOREIGN KEY (disponibilite_id) REFERENCES disponibilites(id) ON DELETE RESTRICT;
ALTER TABLE avis
    ADD CONSTRAINT fk_avis_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_avis_resa FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE;

-- Mot de passe oublié : jetons de réinitialisation
CREATE TABLE IF NOT EXISTS password_resets (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY token_hash (token_hash),
    CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
