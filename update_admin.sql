-- Mettre à jour le mot de passe admin pour qu'il corresponde à admin123
UPDATE admins 
SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE username = 'admin';

-- Ce hash correspond au mot de passe "password", pas "admin123"
-- Pour admin123, nous devons générer un nouveau hash
