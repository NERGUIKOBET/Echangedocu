<?php
/**
 * Fichier contenant les fonctions de contrôle d'accès basées sur la session et les rôles.
 * Doit être inclus en haut de toutes les pages sécurisées (PHP).
 */

// Démarre la session si elle n'est pas déjà active (essentiel pour lire $_SESSION)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Vérifie si un utilisateur est actuellement connecté.
 * Si non, le redirige vers la page de connexion.
 */
function require_login() {
    // Vérifie si la variable de session 'loggedin' est définie et vraie
    if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
        
        // Redirige vers la page de connexion
        // On passe en paramètre l'URL actuelle pour une redirection après connexion (optionnel)
        $redirect_url = urlencode($_SERVER['REQUEST_URI']);
        header("Location: login.html?redirect=" . $redirect_url);
        exit;
    }
}

/**
 * Vérifie si l'utilisateur connecté possède le rôle requis.
 * Si non, le redirige vers son tableau de bord par défaut.
 * @param string $required_role Le rôle exigé ('Admin' ou 'Operateur').
 */
function require_role($required_role) {
    // Exige d'abord que l'utilisateur soit connecté
    require_login(); 
    
    // Vérifie le rôle
    if ($_SESSION["role"] !== $required_role) {
        
        // Redirige vers l'espace non-admin ou la page d'accueil avec un message d'erreur
        if ($_SESSION["role"] === 'Operateur') {
             header("Location: dashboard.php?error=unauthorized_access");
        } else {
            // Dans le cas où le rôle n'est ni 'Admin' ni 'Operateur' (erreur)
            header("Location: index.html?error=unauthorized_access");
        }
        exit;
    }
}

function logout() {
    // Démarre la session si elle n'est pas déjà démarrée
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Vide toutes les variables de session
    $_SESSION = array();

    // Détruit la session
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();
    
    // Redirige l'utilisateur vers la page d'accueil
    header("Location: index.html");
    exit;
}
?>