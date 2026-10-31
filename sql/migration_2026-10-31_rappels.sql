-- ======================================================
-- MIGRATION BOOKPRO - RAPPELS AUTOMATIQUES
-- ======================================================

-- Date d'envoi du rappel au client (évite les doublons)
ALTER TABLE reservations
    ADD COLUMN rappel_envoye_at DATETIME NULL AFTER annule_par;

-- Le pro peut désactiver les rappels envoyés à ses clients
ALTER TABLE users
    ADD COLUMN rappels_clients TINYINT(1) NOT NULL DEFAULT 1 AFTER photo;

-- Résumé "vos RDV de demain" déjà envoyé au pro (un par jour)
CREATE TABLE IF NOT EXISTS rappels_pro (
    pro_id INT NOT NULL,
    jour DATE NOT NULL,
    envoye_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (pro_id, jour),
    CONSTRAINT fk_rappels_pro FOREIGN KEY (pro_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
