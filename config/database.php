<?php
/**
 * Configuration de la base de données pour Escales Tunisiennes
 */

// Paramètres de connexion
define('DB_HOST', 'localhost');
define('DB_NAME', 'escales_tunisiennes');
define('DB_USER', 'root');
define('DB_PASS', '');

// Connexion PDO avec gestion des erreurs
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    // En production, logger l'erreur plutôt que l'afficher
    error_log("Erreur de connexion BDD: " . $e->getMessage());
    
    // En développement, afficher une erreur plus conviviale
    die("Erreur de connexion à la base de données. Veuillez réessayer plus tard.");
}

/**
 * Fonction utilitaire pour exécuter des requêtes préparées
 */
function executeQuery($sql, $params = []) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Erreur SQL: " . $e->getMessage() . " - SQL: " . $sql);
        throw $e;
    }
}

/**
 * Fonction pour générer une référence de réservation unique
 */
function generateReference() {
    return 'ET-' . date('Y') . '-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
}

/**
 * Fonction pour nettoyer et valider les entrées
 */
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Fonction pour valider un email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Fonction pour envoyer une réponse JSON
 */
function jsonResponse($success, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}
?>
