<?php
require_once 'db_connect.php';

try {
    // Vérification si la table utilisateurs existe, sinon la créer
    $sql_create_table = "CREATE TABLE IF NOT EXISTS utilisateurs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        entreprise_id INT,
        nom VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        mot_de_passe VARCHAR(255) NOT NULL,
        role ENUM('admin', 'utilisateur') DEFAULT 'utilisateur',
        date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
        dernier_acces DATETIME,
        FOREIGN KEY (entreprise_id) REFERENCES entreprises(id)
    )";
    
    $pdo->exec($sql_create_table);
    
    // Récupérer les IDs des entreprises
    $stmt = $pdo->query("SELECT id, nom FROM entreprises");
    $entreprises = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($entreprises)) {
        die("Veuillez d'abord créer les entreprises en exécutant creer_entreprises.php");
    }
    
    // Création des utilisateurs pour chaque entreprise
    foreach ($entreprises as $entreprise) {
        // Création de l'administrateur
        $admin = [
            'entreprise_id' => $entreprise['id'],
            'nom' => 'Admin ' . $entreprise['nom'],
            'email' => 'admin@' . strtolower($entreprise['nom']) . '.cm',
            'mot_de_passe' => password_hash('Admin@2023', PASSWORD_DEFAULT),
            'role' => 'admin'
        ];
        
        // Création des utilisateurs standard
        $users = [
            [
                'entreprise_id' => $entreprise['id'],
                'nom' => 'Agent 1 ' . $entreprise['nom'],
                'email' => 'agent1@' . strtolower($entreprise['nom']) . '.cm',
                'mot_de_passe' => password_hash('User@2023', PASSWORD_DEFAULT),
                'role' => 'utilisateur'
            ],
            [
                'entreprise_id' => $entreprise['id'],
                'nom' => 'Agent 2 ' . $entreprise['nom'],
                'email' => 'agent2@' . strtolower($entreprise['nom']) . '.cm',
                'mot_de_passe' => password_hash('User@2023', PASSWORD_DEFAULT),
                'role' => 'utilisateur'
            ]
        ];
        
        // Insertion de l'administrateur
        $sql = "INSERT INTO utilisateurs (entreprise_id, nom, email, mot_de_passe, role) 
                VALUES (:entreprise_id, :nom, :email, :mot_de_passe, :role)";
        $stmt = $pdo->prepare($sql);
        
        $stmt->execute($admin);
        echo "Administrateur créé pour " . $entreprise['nom'] . " (ID: " . $pdo->lastInsertId() . ")\n";
        
        // Insertion des utilisateurs standard
        foreach ($users as $user) {
            $stmt->execute($user);
            echo "Utilisateur créé pour " . $entreprise['nom'] . " (ID: " . $pdo->lastInsertId() . ")\n";
        }
    }
    
    echo "\nConfiguration terminée avec succès!\n\n";
    echo "=== Identifiants de connexion ===\n";
    foreach ($entreprises as $entreprise) {
        $nom_entreprise = strtolower($entreprise['nom']);
        echo "\n" . $entreprise['nom'] . ":\n";
        echo "Administrateur:\n";
        echo "Email: admin@{$nom_entreprise}.cm\n";
        echo "Mot de passe: Admin@2023\n\n";
        echo "Utilisateurs:\n";
        echo "Email: agent1@{$nom_entreprise}.cm\n";
        echo "Email: agent2@{$nom_entreprise}.cm\n";
        echo "Mot de passe: User@2023\n";
        echo "-----------------------------\n";
    }

} catch(PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>