<?php
session_start();
require_once 'db.php'; // login.php - Simple Login System;

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';

    if ($email === false) {
        $errors[] = 'Enter a valid email address.';
    }
    if (!$password) {
        $errors[] = 'Enter your password.';
    }

    if (!$errors) {
        $pdo = getPDO();
        $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = 'Email or password is incorrect.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $email;
            header('Location: index.php');
            exit;
        }
    }
}

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

?><!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Simple Login</title>
    <style>
        body{
            font-family: Arial, sans-serif;
            max-width: 450px;
            margin: 30px auto;
            background-image: url('https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=1400&q=80');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            min-height: 100vh;
            color: #fff;
        }
        .error{color:#ffb3b3;}
        .success{color:#b9ffb9;}
        input{width:100%;padding:8px;margin:6px 0;box-sizing:border-box;}
        button{padding:10px 15px;}
        .box{
            border:1px solid rgba(255,255,255,0.25);
            padding:18px;
            border-radius:6px;
            background: rgba(0, 0, 0, 0.55);
            backdrop-filter: blur(4px);
        }
        a{color:#cfe8ff;}
    </style>
</head>
<body>
    <h1>Login</h1>
    <div class="box">
        <?php if ($flash): ?><div class="success"><?= htmlspecialchars($flash) ?></div><?php endif; ?>
        <?php if ($errors): ?>
            <div class="error"><ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form method="post" novalidate>
            <label>Email address</label>
            <input type="email" name="email" required value="<?= htmlspecialchars($email) ?>">

            <label>Password</label>
            <input type="password" name="password" required>

            <button type="submit">Login</button>
        </form>

        <p>Don't have account? <a href="register.php">Register</a></p>
    </div>
</body>
</html>
