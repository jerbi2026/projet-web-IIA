CREATE DATABASE IF NOT EXISTS escales_tunisiennes CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE escales_tunisiennes;

-- ─────────────────────────────────────────────────────────────
--  Table admins
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS admins (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- Mot de passe par défaut : admin123
-- Hash généré avec : password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO admins (username, password_hash)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
ON DUPLICATE KEY UPDATE username = username;

-- ─────────────────────────────────────────────────────────────
--  Table circuits
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS circuits (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    nom              VARCHAR(100) NOT NULL,
    categorie        ENUM('culture','beach','desert','adventure') NOT NULL,
    description      TEXT,
    duree            VARCHAR(20),
    min_participants INT          DEFAULT 2,
    prix             DECIMAL(10,2) NOT NULL,
    image            VARCHAR(255),
    actif            TINYINT(1)   DEFAULT 1,
    created_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- Données initiales (correspondent aux options de order.html)
INSERT INTO circuits (nom, categorie, description, duree, min_participants, prix, image) VALUES
('Circuit des Civilisations', 'culture',   'Découverte des sites historiques de Tunis, Carthage et Dougga.', '7 jours',  2, 890.00,  'src/sidi-bou-said.jpg'),
('Escapade Bleue',            'beach',     'Détente et soleil sur les côtes de Djerba et Hammamet.',          '5 jours',  2, 1200.00, 'src/djerba.jpg'),
('Sahara Profond',            'desert',    'Aventure dans les dunes de Douz et Ksar Ghilane.',                '6 jours',  2, 1050.00, 'src/douz.jpg'),
('Grand Nord Tunisien',       'adventure', 'Randonnée dans les forêts de Tabarka et les gorges de Ain Draham.', '5 jours', 2, 950.00, 'src/tabarka.jpg'),
('Trek des Djebels',          'adventure', 'Trek entre Zaghouan et les collines du Cap Bon.',                  '4 jours',  2, 780.00,  'src/zaghouan.avif'),
('Saveurs & Médinas',         'culture',   'Circuit gastronomique dans les médinas de Tunis, Sfax et Kairouan.','4 jours',  2, 650.00,  'src/food.png')
ON DUPLICATE KEY UPDATE nom = nom;

-- ─────────────────────────────────────────────────────────────
--  Table réservations
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS reservations (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    ref              VARCHAR(20)   NOT NULL UNIQUE,
    circuit_id       INT           DEFAULT NULL,
    circuit_nom      VARCHAR(100),
    prenom           VARCHAR(50)   NOT NULL,
    nom              VARCHAR(50)   NOT NULL,
    email            VARCHAR(100)  NOT NULL,
    telephone        VARCHAR(20),
    nationalite      VARCHAR(50),
    nb_participants  INT           DEFAULT 1,
    date_depart      DATE,
    hebergement      VARCHAR(20),
    demandes         TEXT,
    mode_paiement    VARCHAR(30),
    prix_total       DECIMAL(10,2),
    statut           ENUM('en_attente','confirmee','annulee') DEFAULT 'en_attente',
    created_at       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (circuit_id) REFERENCES circuits(id) ON DELETE SET NULL
);

-- ─────────────────────────────────────────────────────────────
--  Table messages de contact
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS messages_contact (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    prenom              VARCHAR(50)  NOT NULL,
    nom                 VARCHAR(50)  NOT NULL,
    email               VARCHAR(100) NOT NULL,
    telephone           VARCHAR(20),
    sujet               VARCHAR(50),
    preference_contact  VARCHAR(20),
    message             TEXT         NOT NULL,
    newsletter          TINYINT(1)   DEFAULT 0,
    lu                  TINYINT(1)   DEFAULT 0,
    created_at          TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- ─────────────────────────────────────────────────────────────
--  Table témoignages
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS temoignages (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    prenom     VARCHAR(50)  NOT NULL,
    ville      VARCHAR(50),
    circuit    VARCHAR(100),
    note       INT          NOT NULL CHECK (note BETWEEN 1 AND 5),
    message    TEXT         NOT NULL,
    approuve   TINYINT(1)   DEFAULT 0,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);