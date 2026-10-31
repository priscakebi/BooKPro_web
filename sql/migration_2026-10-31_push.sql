-- ======================================================
-- MIGRATION BOOKPRO - NOTIFICATIONS PUSH (application pro)
-- ======================================================

-- Un abonnement = un appareil (téléphone, ordinateur) d'un utilisateur
CREATE TABLE IF NOT EXISTS push_subscriptions (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    endpoint TEXT NOT NULL,
    endpoint_hash CHAR(64) NOT NULL,
    p256dh VARCHAR(255) NOT NULL,
    auth VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY endpoint_hash (endpoint_hash),
    KEY user_id (user_id),
    CONSTRAINT fk_push_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
