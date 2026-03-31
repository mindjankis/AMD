<?php
session_start();
require_once 'db.php';

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if ($email === false) {
        $errors[] = 'Enter a valid email address.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }
    if ($password !== $password_confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        $pdo = getPDO();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'A user with this email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $insert = $pdo->prepare('INSERT INTO users (email, password_hash) VALUES (?, ?)');
            $insert->execute([$email, $hash]);
            $_SESSION['flash'] = 'Registration successful. Please log in.';
            header('Location: login.php');
            exit;
        }
    }
}

?><!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Simple Login</title>
    <style>
        body{
            font-family: Arial, sans-serif;
            max-width: 450px;
            margin: 30px auto;
            background-image: url('Wallpaper.jpg');
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
        .corner-picture{
            position: fixed;
            right: 20px;
            bottom: 20px;
            width: min(360px, 42vw);
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.35);
            pointer-events: none;
            z-index: 1;
        }
    </style>
</head>
<body>
    <h1>Register</h1>
    <div class="box">
        <?php if ($errors): ?>
            <div class="error"><ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>

        <form method="post" novalidate>
            <label>Email address</label>
            <input type="email" name="email" required value="<?= htmlspecialchars($email) ?>">

            <label>Password</label>
            <input type="password" name="password" required>

            <label>Confirm password</label>
            <input type="password" name="password_confirm" required>

            <button type="submit">Register</button>
        </form>

        <p>Already have account? <a href="login.php">Login</a></p>
    </div>

    <img src="Picture_1.jpg" alt="Corner picture" class="corner-picture">
</body>
</html>
