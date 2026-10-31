-- ======================================================
-- MIGRATION BOOKPRO - FICHES CLIENTS
-- ======================================================

-- Notes internes d'un pro sur un client (jamais visibles par le client)
CREATE TABLE IF NOT EXISTS notes_clients (
    id INT NOT NULL AUTO_INCREMENT,
    pro_id INT NOT NULL,
    client_id INT NOT NULL,
    note TEXT NOT NULL,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY pro_client (pro_id, client_id),
    CONSTRAINT fk_notes_pro FOREIGN KEY (pro_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_notes_client FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Qui a annulé une réservation (pour l'historique client)
ALTER TABLE reservations
    ADD COLUMN annule_par ENUM('client', 'pro') NULL AFTER statut;
