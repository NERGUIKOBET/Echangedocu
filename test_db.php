<?php
require_once 'db_connect.php';

try {
    // Test de connexion
    echo "Connexion à la base de données réussie!\n";
    
    // Création des premières entreprises
    $sql_entreprises = "INSERT INTO entreprises (nom, email, telephone) VALUES 
        ('Entreprise A', 'contact@entrepriseA.com', '+33123456789'),
        ('Entreprise B', 'contact@entrepriseB.com', '+33987654321')";
    $pdo->exec($sql_entreprises);
    echo "Entreprises créées avec succès!\n";
    
    // Création des administrateurs pour chaque entreprise
    // Mot de passe: Admin123! (hashé)
    $admin_password = password_hash('Admin123!', PASSWORD_DEFAULT);
    
    $sql_admins = "INSERT INTO utilisateurs (entreprise_id, nom, email, mot_de_passe, role) VALUES 
        (1, 'Admin A', 'admin@entrepriseA.com', :password, 'admin'),
        (2, 'Admin B', 'admin@entrepriseB.com', :password, 'admin')";
    
    $stmt = $pdo->prepare($sql_admins);
    $stmt->execute(['password' => $admin_password]);
    echo "Administrateurs créés avec succès!\n";
    
    // Création des utilisateurs standards
    $user_password = password_hash('User123!', PASSWORD_DEFAULT);
    
    $sql_users = "INSERT INTO utilisateurs (entreprise_id, nom, email, mot_de_passe, role) VALUES 
        (1, 'User A1', 'user1@entrepriseA.com', :password, 'utilisateur'),
        (1, 'User A2', 'user2@entrepriseA.com', :password, 'utilisateur'),
        (2, 'User B1', 'user1@entrepriseB.com', :password, 'utilisateur'),
        (2, 'User B2', 'user2@entrepriseB.com', :password, 'utilisateur')";
    
    $stmt = $pdo->prepare($sql_users);
    $stmt->execute(['password' => $user_password]);
    echo "Utilisateurs standards créés avec succès!\n";
    
    echo "\nConfiguration initiale terminée!\n";
    echo "----------------------------------------\n";
    echo "Identifiants de connexion:\n";
    echo "Admin Entreprise A:\n";
    echo "Email: admin@entrepriseA.com\n";
    echo "Mot de passe: Admin123!\n\n";
    echo "Admin Entreprise B:\n";
    echo "Email: admin@entrepriseB.com\n";
    echo "Mot de passe: Admin123!\n\n";
    echo "Utilisateurs standards:\n";
    echo "Email: user1@entrepriseA.com (ou user2)\n";
    echo "Email: user1@entrepriseB.com (ou user2)\n";
    echo "Mot de passe: User123!\n";
    
} catch(PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>