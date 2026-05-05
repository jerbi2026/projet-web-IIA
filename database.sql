CREATE DATABASE IF NOT EXISTS escales_tunisiennes CHARACTER SET utf8mb4;
USE escales_tunisiennes;

-- Table admin
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insérer un admin par défaut (mot de passe: admin123)
INSERT INTO admins (username, password_hash) 
VALUES ('admin', '$2y$10$YourHashHere');

-- Table circuits (pour rendre products.html dynamique)
CREATE TABLE circuits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    categorie ENUM('culture','beach','desert','adventure') NOT NULL,
    description TEXT,
    duree VARCHAR(20),
    min_participants INT DEFAULT 2,
    prix DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    actif TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table réservations
CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ref VARCHAR(20) NOT NULL UNIQUE,
    circuit_id INT,
    circuit_nom VARCHAR(100),
    prenom VARCHAR(50) NOT NULL,
    nom VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telephone VARCHAR(20),
    nationalite VARCHAR(50),
    nb_participants INT DEFAULT 1,
    date_depart DATE,
    hebergement VARCHAR(20),
    demandes TEXT,
    mode_paiement VARCHAR(30),
    prix_total DECIMAL(10,2),
    statut ENUM('en_attente','confirmee','annulee') DEFAULT 'en_attente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (circuit_id) REFERENCES circuits(id) ON DELETE SET NULL
);

-- Table messages de contact
CREATE TABLE messages_contact (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prenom VARCHAR(50) NOT NULL,
    nom VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telephone VARCHAR(20),
    sujet VARCHAR(50),
    preference_contact VARCHAR(20),
    message TEXT NOT NULL,
    newsletter TINYINT(1) DEFAULT 0,
    lu TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table témoignages
CREATE TABLE temoignages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prenom VARCHAR(50) NOT NULL,
    ville VARCHAR(50),
    circuit VARCHAR(100),
    note INT NOT NULL CHECK (note BETWEEN 1 AND 5),
    message TEXT NOT NULL,
    approuve TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);