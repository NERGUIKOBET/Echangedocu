<?php
// Fichier : db_connect.php

// Paramètres de connexion à la base de données
define('DB_SERVER', 'localhost'); // Souvent 'localhost' avec WAMP
define('DB_USERNAME', 'root');    // Utilisateur par défaut de WAMP
define('DB_PASSWORD', '');        // Mot de passe par défaut (souvent vide)
define('DB_NAME', 'enerca_db'); // Base de données pour le système d'échange de documents

try {
    // Chaîne de connexion PDO
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME . ";charset=utf8", DB_USERNAME, DB_PASSWORD);
    
    // Configuration pour émettre des exceptions en cas d'erreur
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Si la connexion échoue, enregistrer l'erreur et arrêter le script
    error_log("ERROR: Could not connect. " . $e->getMessage());
    die("ERROR: La connexion à la base de données a échoué. Veuillez vérifier les logs.");
}
?>