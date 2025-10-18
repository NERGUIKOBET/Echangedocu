CREATE DATABASE IF NOT EXISTS enerca_db;
USE enerca_db;

CREATE TABLE entreprises (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    telephone VARCHAR(20),
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE utilisateurs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    entreprise_id INT,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('admin', 'utilisateur') DEFAULT 'utilisateur',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (entreprise_id) REFERENCES entreprises(id)
);

CREATE TABLE documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom_fichier VARCHAR(255) NOT NULL,
    type_fichier ENUM('pdf', 'photo', 'dossier') NOT NULL,
    chemin_fichier VARCHAR(255) NOT NULL,
    taille BIGINT NOT NULL,
    entreprise_source_id INT,
    entreprise_dest_id INT,
    utilisateur_id INT,
    statut ENUM('en_attente', 'recu', 'refuse') DEFAULT 'en_attente',
    date_envoi DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_reception DATETIME,
    FOREIGN KEY (entreprise_source_id) REFERENCES entreprises(id),
    FOREIGN KEY (entreprise_dest_id) REFERENCES entreprises(id),
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)
);

CREATE TABLE historique_echanges (
    id INT PRIMARY KEY AUTO_INCREMENT,
    document_id INT,
    action ENUM('envoi', 'reception', 'refus') NOT NULL,
    utilisateur_id INT,
    date_action DATETIME DEFAULT CURRENT_TIMESTAMP,
    commentaire TEXT,
    FOREIGN KEY (document_id) REFERENCES documents(id),
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)
);