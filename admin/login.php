<?php
require_once 'config.php';

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';

    if ($user === ADMIN_USER && password_verify($pass, ADMIN_HASH)) {
        $_SESSION['logged_in'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid credentials';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Krpano CMS</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            color: #333;
        }
        .login-container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 350px;
        }
        h2 { margin-top: 0; text-align: center; color: #333; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box; /* Fix for padding increasing width */
        }
        button {
            width: 100%;
            padding: 0.75rem;
            background-color: #333;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            margin-top: 1rem;
            transition: background-color 0.2s;
        }
        button:hover { background-color: #555; }
        .error { color: red; text-align: center; margin-bottom: 1rem; font-size: 0.9rem; }
    </style>
</head>
<body>

<div class="login-container">
    <h2>CMS Login</h2>
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required autofocus>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit">Log In</button>
    </form>
    <p style="text-align:center; font-size:0.8rem; color:#888; margin-top:1rem;">Kontakta Adrian</p>
</div>

</body>
</html>
