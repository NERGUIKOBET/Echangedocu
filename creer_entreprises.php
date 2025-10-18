<?php
require_once 'db_connect.php';

try {
    // Création des entreprises
    $entreprises = [
        [
            'nom' => 'ENERCA',
            'email' => 'contact@enerca.com',
            'telephone' => '+237222222222',
            'description' => 'Entreprise nationale d\'électricité'
        ],
        [
            'nom' => 'SONATREL',
            'email' => 'contact@sonatrel.cm',
            'telephone' => '+237233333333',
            'description' => 'Société nationale de transport d\'électricité'
        ]
    ];

    // Préparation de la requête d'insertion
    $sql = "INSERT INTO entreprises (nom, email, telephone, description) VALUES (:nom, :email, :telephone, :description)";
    $stmt = $pdo->prepare($sql);

    // Insertion des entreprises
    foreach ($entreprises as $entreprise) {
        $stmt->execute($entreprise);
        echo "Entreprise " . $entreprise['nom'] . " créée avec succès! ID: " . $pdo->lastInsertId() . "\n";
    }

    echo "\nToutes les entreprises ont été créées avec succès!";

} catch(PDOException $e) {
    if($e->getCode() == '42S02') {
        // La table n'existe pas, créons-la d'abord
        $sql_create_table = "CREATE TABLE IF NOT EXISTS entreprises (
            id INT PRIMARY KEY AUTO_INCREMENT,
            nom VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            telephone VARCHAR(20),
            description TEXT,
            date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        
        $pdo->exec($sql_create_table);
        echo "Table entreprises créée.\n";
        
        // Réessayons l'insertion
        echo "Veuillez relancer le script pour ajouter les entreprises.\n";
    } else {
        die("Erreur : " . $e->getMessage());
    }
}
?>