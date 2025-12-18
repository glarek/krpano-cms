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
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Grit Projects Admin</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600" rel="stylesheet">

    <style>
        :root {
            --primary: oklch(0.6 0.2 255.45);
            --primary-hover: oklch(0.55 0.2 255.45);
            --bg-color: #0B0F19;
            --text-color: #FFFFFF;
            --input-bg: #FFFFFF;
            --input-border: rgba(255, 255, 255, 0.1);
            --input-focus: #FFFFFF;
        }

        * {
            font-family: 'Outfit', sans-serif;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            overflow: hidden;
            position: relative;
            opacity: 0; /* FOUC Prevention */
            transition: opacity 0.3s ease-in-out;
        }

        /* Ambient Glow Effect */
        .glow {
            position: absolute;
            width: 800px;
            height: 800px;
            background: var(--primary);
            opacity: 0.05;
            filter: blur(120px);
            border-radius: 50%;
            z-index: -1;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            pointer-events: none;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 2rem;
            z-index: 1;
        }

        h2 {
            margin-top: 0;
            text-align: center;
            font-weight: 600;
            font-size: 2rem;
            margin-bottom: 2rem;
            letter-spacing: -0.02em;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            opacity: 0.8;
            margin-left: 0.5rem;
        }

        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 1rem 1.25rem;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 9999px; /* Pill shape inputs */
            color: #000000;
            font-family: 'Outfit', sans-serif !important;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
            outline: none;
        }

        input:-webkit-autofill,
        input:-webkit-autofill:hover, 
        input:-webkit-autofill:focus, 
        input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 30px white inset !important;
            -webkit-text-fill-color: black !important;
            transition: background-color 5000s ease-in-out 0s;
            font-family: 'Outfit', sans-serif !important;
        }

        input::placeholder {
            font-family: 'Outfit', sans-serif !important;
            opacity: 0.6;
        }

        input[type="text"]:focus, input[type="password"]:focus {
            background: var(--input-focus);
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); /* Subtle blue glow matching primary hue roughly */
        }

        button {
            width: 100%;
            padding: 1rem;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 9999px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 500;
            font-family: inherit;
            margin-top: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
        }

        button:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
        }

        .error {
            background: rgba(239, 68, 68, 0.1);
            color: #ff6b6b;
            text-align: center;
            padding: 0.75rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 2rem;
            color: rgba(255, 255, 255, 0.4);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.2s;
        }
        
        .back-link:hover {
            color: white;
        }
    </style>
</head>
<body>

<div class="glow"></div>

<div class="login-container">
    <h2>Admin Login</h2>
    
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="username">Användarnamn</label>
            <input type="text" id="username" name="username" required placeholder="Admin">
        </div>
        <div class="form-group">
            <label for="password">Lösenord</label>
            <input type="password" id="password" name="password" required placeholder="••••••••">
        </div>
        <button type="submit">Logga in</button>
    </form>
    
    <a href="../index.php" class="back-link">← Tillbaka till startsidan</a>
</div>

<script>
    // FOUC Prevention: Wait for fonts, then reveal
    document.fonts.ready.then(function() {
        document.body.style.opacity = 1;
    });

    // Fallback in case font loading event fails or hangs
    setTimeout(function() {
        if (document.body.style.opacity === '0' || document.body.style.opacity === '') {
            document.body.style.opacity = 1;
        }
    }, 500);
</script>

</body>
</html>
