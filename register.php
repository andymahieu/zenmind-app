<?php
require_once 'includes/auth.php';
require_once 'db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($username && $password && $password === $confirm_password) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, language) VALUES (?, ?, ?)");
            $stmt->execute([$username, $hashed_password, $current_lang]);
            
            // Auto login
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['username'] = $username;
            header("Location: index.php");
            exit;
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = $lang['register_error'];
            } else {
                $error = "Database error: " . $e->getMessage();
            }
        }
    } else {
        $error = $lang['register_error'];
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['app_name'] ?> - <?= $lang['register'] ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        background: '#f8f9fa',
                        primary: '#6b9080',
                        'primary-hover': '#5a7c6f',
                        'on-surface': '#2b2d42',
                        'surface-container': '#ffffff',
                        error: '#e56b6f',
                    },
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(107, 144, 128, 0.1);
        }
    </style>
</head>
<body class="bg-background text-on-surface font-sans min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-10">
            <div class="w-20 h-20 bg-primary/10 rounded-3xl flex items-center justify-center mx-auto mb-4 rotate-3">
                <span class="material-symbols-outlined text-primary text-5xl">self_improvement</span>
            </div>
            <h1 class="text-4xl font-bold text-primary tracking-tight"><?= $lang['app_name'] ?></h1>
        </div>

        <div class="glass-card rounded-3xl p-8 shadow-sm">
            <h2 class="text-2xl font-bold mb-6 text-center"><?= $lang['register'] ?></h2>

            <?php if ($error): ?>
                <div class="bg-error/10 text-error p-4 rounded-2xl mb-6 text-center text-sm font-semibold">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php" class="space-y-5">
                <div>
                    <label class="block text-sm font-semibold mb-2 text-on-surface/70"><?= $lang['username'] ?></label>
                    <input type="text" name="username" required class="w-full px-5 py-4 rounded-2xl border border-primary/20 bg-background/50 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2 text-on-surface/70"><?= $lang['password'] ?></label>
                    <input type="password" name="password" required class="w-full px-5 py-4 rounded-2xl border border-primary/20 bg-background/50 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2 text-on-surface/70"><?= $lang['confirm_password'] ?></label>
                    <input type="password" name="confirm_password" required class="w-full px-5 py-4 rounded-2xl border border-primary/20 bg-background/50 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                </div>

                <button type="submit" class="w-full bg-primary hover:bg-primary-hover text-white font-bold py-4 rounded-2xl transition-colors shadow-lg shadow-primary/20 mt-2">
                    <?= $lang['register'] ?>
                </button>
            </form>

            <div class="mt-8 text-center">
                <a href="login.php" class="text-primary hover:underline text-sm font-semibold"><?= $lang['has_account'] ?></a>
            </div>
            
            <div class="mt-10 flex justify-center gap-6 text-sm font-semibold text-on-surface/50">
                <a href="?lang=nl" class="hover:text-primary transition-colors <?= ($current_lang == 'nl') ? 'text-primary' : '' ?>">NL</a>
                <a href="?lang=en" class="hover:text-primary transition-colors <?= ($current_lang == 'en') ? 'text-primary' : '' ?>">EN</a>
                <a href="?lang=fr" class="hover:text-primary transition-colors <?= ($current_lang == 'fr') ? 'text-primary' : '' ?>">FR</a>
            </div>
        </div>
    </div>
</body>
</html>
