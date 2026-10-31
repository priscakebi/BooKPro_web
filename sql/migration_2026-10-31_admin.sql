-- ======================================================
-- MIGRATION BOOKPRO - ADMINISTRATION
-- ======================================================

-- Compte suspendu par un administrateur : connexion impossible,
-- pro masqué des recherches et de sa page publique
ALTER TABLE users
    ADD COLUMN suspendu_at DATETIME NULL AFTER rappels_clients,
    ADD COLUMN suspendu_motif VARCHAR(255) NULL AFTER suspendu_at;
