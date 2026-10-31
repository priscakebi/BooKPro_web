-- ======================================================
-- MIGRATION BOOKPRO - PROFILS PRO + HORAIRES D'OUVERTURE
-- Sauvegarde préalable : backup_avant_design_2026-10-31.sql
-- ======================================================

-- Fiche publique du professionnel
ALTER TABLE users
    ADD COLUMN description TEXT NULL AFTER ville,
    ADD COLUMN adresse VARCHAR(255) NULL AFTER description,
    ADD COLUMN code_postal VARCHAR(10) NULL AFTER adresse,
    ADD COLUMN photo VARCHAR(255) NULL AFTER code_postal;

-- Horaires d'ouverture hebdomadaires
-- (plusieurs plages par jour possibles, ex : 8h-12h et 14h-18h)
CREATE TABLE IF NOT EXISTS horaires_ouverture (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    jour TINYINT NOT NULL COMMENT '1 = lundi ... 7 = dimanche',
    heure_ouverture TIME NOT NULL,
    heure_fermeture TIME NOT NULL,
    PRIMARY KEY (id),
    KEY user_jour (user_id, jour),
    CONSTRAINT fk_horaires_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Ancienne table de rendez-vous, vide et remplacée par reservations
DROP TABLE IF EXISTS rendez_vous;
