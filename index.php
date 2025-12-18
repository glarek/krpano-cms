<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grit Projects Admin</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: oklch(0.6 0.2 255.45);
            --primary-hover: oklch(0.55 0.2 255.45);
            --bg-color: #0B0F19; /* Deep navy/black similar to the site */
            --text-color: #FFFFFF;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: 'Outfit', sans-serif;
            height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* Ambient Glow Effect */
        .glow {
            position: absolute;
            width: 600px;
            height: 600px;
            background: var(--primary);
            opacity: 0.08;
            filter: blur(100px);
            border-radius: 50%;
            z-index: -1;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .container {
            text-align: center;
            z-index: 1;
        }

        .logo {
            width: 180px;
            margin-bottom: 3rem;
            display: block;
            margin-left: auto;
            margin-right: auto;
            filter: brightness(0) invert(1); /* Make logo white */
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: var(--primary);
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 9999px; /* Pill shape */
            text-decoration: none;
            font-weight: 500;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3), 0 4px 6px -2px rgba(0, 0, 0, 0.1);
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.1);
        }

        .btn svg {
            margin-left: 0.5rem;
            transition: transform 0.3s ease;
        }

        .btn:hover svg {
            transform: translateX(4px);
        }
    </style>
</head>
<body>

    <div class="glow"></div>

    <div class="container">
        <img src="img/GRIT_LOGO.svg" alt="Grit Projects Logo" class="logo">
        
        <a href="admin/login.php" class="btn">
            Logga in
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14M12 5l7 7-7 7"/>
            </svg>
        </a>
    </div>

</body>
</html>