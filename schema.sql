CREATE DATABASE IF NOT EXISTS danslebueno CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE danslebueno;

DROP TABLE IF EXISTS favoris;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS annonces;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pseudo VARCHAR(60) NOT NULL,
    nom VARCHAR(80) DEFAULT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    actif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE annonces (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    titre VARCHAR(120) NOT NULL,
    prix DECIMAL(10,2) NOT NULL,
    etat ENUM('neuf', 'bon', 'correct') NOT NULL DEFAULT 'bon',
    description TEXT NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_annonces_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    annonce_id INT DEFAULT NULL,
    contenu TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_messages_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_messages_receiver FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_messages_annonce FOREIGN KEY (annonce_id) REFERENCES annonces(id) ON DELETE SET NULL
);

CREATE TABLE favoris (
    user_id INT NOT NULL,
    annonce_id INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, annonce_id),
    CONSTRAINT fk_favoris_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_favoris_annonce FOREIGN KEY (annonce_id) REFERENCES annonces(id) ON DELETE CASCADE
);

INSERT INTO users (pseudo, nom, email, password, role, actif) VALUES
('Admin', 'DansLeBueno', 'admin@danslebueno.local', '$2y$12$z8oH5UrpTvKl2l43C.TOA.TDgjjlueLtUyYePfjkiaZv3dSHcyv.G', 'admin', 1),
('Marie', 'Dupont', 'marie@example.com', '$2y$12$z8oH5UrpTvKl2l43C.TOA.TDgjjlueLtUyYePfjkiaZv3dSHcyv.G', 'user', 1),
('Jean', 'Martin', 'jean@example.com', '$2y$12$z8oH5UrpTvKl2l43C.TOA.TDgjjlueLtUyYePfjkiaZv3dSHcyv.G', 'user', 1);

INSERT INTO annonces (user_id, titre, prix, etat, description, image) VALUES
(2, 'Casque Sony WH-1000XM5', 250.00, 'bon', 'Casque en excellent état, vendu avec sa housse et son câble de charge.', NULL),
(3, 'Vélo de ville', 180.00, 'correct', 'Vélo fonctionnel, quelques traces d’usage mais roule très bien.', NULL),
(2, 'iPhone 13 128 Go', 430.00, 'neuf', 'Téléphone débloqué, très peu utilisé, batterie en très bon état.', NULL);

INSERT INTO favoris (user_id, annonce_id) VALUES (2, 2), (3, 1);
INSERT INTO messages (sender_id, receiver_id, annonce_id, contenu, is_read) VALUES
(2, 3, 2, 'Bonjour, le vélo est-il toujours disponible ?', 1),
(3, 2, 2, 'Bonjour, oui il est toujours disponible.', 0),
(2, 1, 1, 'Bonjour, j’aimerais plus de détails sur le casque.', 1),
(1, 2, 1, 'Bonjour, bien sûr, il est complet et fonctionne parfaitement.', 0);
