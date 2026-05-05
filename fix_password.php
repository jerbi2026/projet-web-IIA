<?php
// Script pour corriger le mot de passe admin
require_once 'config/database.php';

// Hash correct pour admin123
$correct_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

// Test si ce hash correspond à admin123
$test = password_verify('admin123', $correct_hash);
echo "Test du hash actuel: " . ($test ? 'OK' : 'FAIL') . "\n";

// Générer un nouveau hash pour admin123
$new_hash = password_hash('admin123', PASSWORD_BCRYPT);
echo "Nouveau hash pour admin123: " . $new_hash . "\n";

// Vérifier le nouveau hash
$test_new = password_verify('admin123', $new_hash);
echo "Test du nouveau hash: " . ($test_new ? 'OK' : 'FAIL') . "\n";

// Mettre à jour la base de données
$stmt = $pdo->prepare("UPDATE admins SET password_hash = ? WHERE username = ?");
$stmt->execute([$new_hash, 'admin']);

echo "Mot de passe admin mis à jour avec succès\n";
?>
