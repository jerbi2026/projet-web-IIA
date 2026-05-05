<?php

require_once '../config/auth.php';

// Déjà connecté → rediriger vers l'admin
redirectIfLoggedIn();
 
$error = '';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
 
    if ($username === '' || $password === '') {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        if (login($username, $password)) {
            $redirect = $_GET['redirect'] ?? '../admin/index.php';
            // Sécurité : ne pas rediriger vers une URL externe
            if (!str_starts_with($redirect, '/') && !str_starts_with($redirect, '../')) {
                $redirect = '../admin/index.php';
            }
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'Identifiants incorrects.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Connexion Admin – Escales Tunisiennes</title>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,600;1,300&family=Raleway:wght@300;400;700&display=swap" rel="stylesheet"/>
  <style>
    :root {
      --ocre: #C4873A; --sapphire: #1A4B7A; --dark: #0D2F4F;
      --cream: #F5F0E8; --white: #fff; --text: #3A3228;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Raleway', sans-serif;
      background: linear-gradient(135deg, var(--dark) 0%, var(--sapphire) 100%);
      min-height: 100vh;
      display: flex; align-items: center; justify-content: center;
    }
    .login-card {
      background: var(--white);
      border-radius: 12px;
      padding: 3rem 2.5rem;
      width: 100%; max-width: 420px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    }
    .login-logo {
      text-align: center; margin-bottom: 2rem;
    }
    .login-logo h1 {
      font-family: 'Cormorant Garamond', serif;
      font-size: 1.8rem; font-weight: 300; color: var(--dark);
    }
    .login-logo h1 em { color: var(--ocre); font-style: italic; }
    .login-logo p {
      font-size: 0.72rem; letter-spacing: 0.2em; text-transform: uppercase;
      color: #999; margin-top: 0.3rem;
    }
    .form-group { margin-bottom: 1.2rem; }
    label {
      display: block; font-size: 0.72rem; font-weight: 700;
      letter-spacing: 0.12em; text-transform: uppercase; color: #888;
      margin-bottom: 0.4rem;
    }
    input {
      width: 100%; border: 1.5px solid #e0d8ce; border-radius: 4px;
      padding: 0.85rem 1rem; font-family: 'Raleway', sans-serif;
      font-size: 0.9rem; color: var(--text); outline: none;
      transition: border-color 0.2s;
    }
    input:focus { border-color: var(--ocre); }
    .btn-login {
      width: 100%; background: var(--ocre); color: var(--white);
      border: none; border-radius: 4px; padding: 0.95rem;
      font-family: 'Raleway', sans-serif; font-size: 0.85rem;
      font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase;
      cursor: pointer; transition: background 0.2s;
      margin-top: 0.5rem;
    }
    .btn-login:hover { background: #9E6828; }
    .error-msg {
      background: #fde8e8; border: 1px solid #f5c6c6;
      color: #c0392b; border-radius: 4px; padding: 0.7rem 1rem;
      font-size: 0.85rem; margin-bottom: 1.2rem;
    }
    .back-link {
      display: block; text-align: center; margin-top: 1.5rem;
      font-size: 0.8rem; color: #aaa; text-decoration: none;
      transition: color 0.2s;
    }
    .back-link:hover { color: var(--ocre); }
  </style>
</head>
<body>
  <div class="login-card">
    <div class="login-logo">
      <h1>Escales <em>Tunisiennes</em></h1>
      <p>Espace Administration</p>
    </div>
 
    <?php if ($error): ?>
      <div class="error-msg">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
 
    <form method="POST" action="">
      <div class="form-group">
        <label for="username">Identifiant</label>
        <input type="text" id="username" name="username"
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
               placeholder="admin" required autofocus />
      </div>
      <div class="form-group">
        <label for="password">Mot de passe</label>
        <input type="password" id="password" name="password"
               placeholder="••••••••" required />
      </div>
      <button type="submit" class="btn-login">Se connecter →</button>
    </form>
 
    <a href="../index.html" class="back-link">← Retour au site</a>
  </div>
</body>
</html>