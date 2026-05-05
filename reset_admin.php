<?php
// Script pour réinitialiser le mot de passe admin
require_once 'config/database.php';

// Supprimer l'admin existant
$pdo->exec("DELETE FROM admins WHERE username = 'admin'");

// Créer un nouvel admin avec le mot de passe admin123
$password = 'admin123';
$hash = password_hash($password, PASSWORD_BCRYPT);

$stmt = $pdo->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?)");
$stmt->execute(['admin', $hash]);

echo "Admin réinitialisé avec succès\n";
echo "Username: admin\n";
echo "Password: admin123\n";
echo "Hash: " . $hash . "\n";

// Vérification
$test = password_verify('admin123', $hash);
echo "Vérification du hash: " . ($test ? 'OK' : 'FAIL') . "\n";
?>
