<?php
/**
 * Configuration et fonctions d'authentification
 */

// Démarrer la session si pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Vérifie si l'utilisateur est connecté en tant qu'admin
 */
function isLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Vérifie les identifiants de connexion
 */
function login($username, $password) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT id, username, password_hash FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password_hash'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['login_time'] = time();
            
            // Régénérer l'ID de session pour prévenir les attaques
            session_regenerate_id(true);
            
            return true;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Erreur login: " . $e->getMessage());
        return false;
    }
}

/**
 * Déconnexion de l'admin
 */
function logout() {
    // Détruire toutes les variables de session
    $_SESSION = [];
    
    // Détruire le cookie de session
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Détruire la session
    session_destroy();
}

/**
 * Protéger une page - rediriger si non connecté
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Rediriger si déjà connecté
 */
function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

/**
 * Obtenir les informations de l'admin connecté
 */
function getCurrentAdmin() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['admin_id'],
        'username' => $_SESSION['admin_username'],
        'login_time' => $_SESSION['login_time']
    ];
}

/**
 * Vérifier si la session est expirée (24h)
 */
function isSessionExpired() {
    if (!isset($_SESSION['login_time'])) {
        return true;
    }
    
    return (time() - $_SESSION['login_time']) > 24 * 60 * 60; // 24 heures
}

/**
 * Rafraîchir le temps de session
 */
function refreshSession() {
    if (isLoggedIn()) {
        $_SESSION['login_time'] = time();
    }
}
?>
