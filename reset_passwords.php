<?php
require_once 'db_connect.php';
// Script pour réinitialiser les mots de passe des utilisateurs (admin et standard)
// Mot de passe admin : Admin123! | utilisateur : User123!
try {
    $admin_hash = password_hash('Admin123!', PASSWORD_DEFAULT);
    $user_hash = password_hash('User123!', PASSWORD_DEFAULT);
    // Mise à jour pour les admins
    $pdo->exec("UPDATE utilisateurs SET mot_de_passe = '$admin_hash' WHERE role = 'admin'");
    // Mise à jour pour les utilisateurs standards
    $pdo->exec("UPDATE utilisateurs SET mot_de_passe = '$user_hash' WHERE role = 'utilisateur'");
    echo "Mots de passe réinitialisés avec succès !<br>";
    echo "Admin : Admin123!<br>Utilisateur : User123!<br>";
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?>