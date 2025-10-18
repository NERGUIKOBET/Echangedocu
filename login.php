<?php
// ====================================================================
// Fichier : login.php
// Rôle : Gérer la soumission du formulaire de connexion, vérifier les
//        informations et démarrer la session utilisateur.
// ====================================================================

session_start();

// --- 1. Inclusion du fichier de connexion à la base de données (PDO) ---
// Assurez-vous que 'db_connect.php' contient bien la variable $pdo
require_once 'db_connect.php';      

// Rediriger l'utilisateur si une session est déjà active
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    // Redirige vers la page d'administration si l'utilisateur est admin, sinon vers le tableau de bord
    if ($_SESSION["role"] === 'Admin') {
        header("Location: admin.php");
    } else {
        header("Location: dashboard.php");
    }
    exit;
}

// --- 2. Vérification de la méthode de requête ---
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    // Si la page est accédée directement sans formulaire
    header("Location: login.html?error=method_not_allowed");
    exit;
}

// --- 3. Validation des champs d'entrée ---
if (empty(trim($_POST["email"])) || empty(trim($_POST["password"]))) {
    header("Location: login.html?error=empty_fields");
    exit;
}

$email = trim($_POST["email"]); 
$password = trim($_POST["password"]); // Le mot de passe non haché soumis par l'utilisateur

// --- 4. Préparation de la requête SQL (Utilisation de requêtes préparées pour la sécurité) ---
// Note : Le nom de colonne 'password_hash' est utilisé ici, il doit correspondre à votre DB.

$sql = "SELECT id, nom AS username, mot_de_passe, role, entreprise_id AS company_id FROM utilisateurs WHERE email = :email";

if ($stmt = $pdo->prepare($sql)) {
    // Lier les paramètres à la requête
    $stmt->bindParam(":email", $email, PDO::PARAM_STR);

    try {
        if ($stmt->execute()) {
            
            // --- 5. Vérification du résultat de la requête ---
            if ($stmt->rowCount() == 1) {
                // Récupération des données utilisateur
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                $hashed_password_db = $user['mot_de_passe']; 

                // --- 6. Vérification du mot de passe (Cryptographie sécurisée) ---
                // Utilise password_verify() pour vérifier le mot de passe soumis avec le hash stocké
                if (password_verify($password, $hashed_password_db)) {

                    // Mot de passe correct : Initialisation de la session
                    $_SESSION["loggedin"] = true;
                    $_SESSION["user_id"] = $user['id'];
                    $_SESSION["username"] = $user['username'];
                    $_SESSION["role"] = $user['role'];      
                    $_SESSION["company_id"] = $user['company_id']; 

                    // --- 7. Redirection basée sur le Rôle (RBAC) ---
                    if ($user['role'] === 'Admin') {
                        header("Location: admin.php"); // Espace Admin
                    } else {
                        header("Location: dashboard.php"); // Espace Opérateur/Client
                    }
                    exit;

                } else {
                    // Mot de passe incorrect
                    header("Location: login.html?error=invalid_credentials");
                    exit;
                }
            } else {
                // Email non trouvé
                header("Location: login.html?error=invalid_credentials");
                exit;
            }
        }
    } catch (PDOException $e) {
        // Enregistrement de l'erreur dans les logs du serveur
        error_log("DB Error during login: " . $e->getMessage());
        header("Location: login.html?error=db_error");
        exit;
    }
} else {
    // Erreur de préparation de la requête
    error_log("SQL Prepare Error: " . $pdo->errorInfo()[2]);
    header("Location: login.html?error=sql_prepare_error");
    exit;
}

// Libération des ressources de la base de données
$stmt = null;
$pdo = null;
?>