<?php
require_once 'includes/auth.php';
require_login();
require_once 'db.php';

$user_id = $_SESSION['user_id'];
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $prompt = trim($_POST['prompt'] ?? '');
    $content = trim($_POST['content'] ?? '');
    
    if ($content) {
        $stmt = $pdo->prepare("INSERT INTO journal_entries (user_id, prompt, content) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $prompt, $content]);
        $success = true;
    }
}

// Select a random prompt for today
$prompts = [
    $lang['prompt_1'],
    $lang['prompt_2'],
    $lang['prompt_3']
];
$daily_prompt = $prompts[array_rand($prompts)];
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?= $lang['app_name'] ?> - <?= $lang['nav_journal'] ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        background: '#f8f9fa',
                        primary: '#6b9080',
                        'primary-hover': '#5a7c6f',
                        'on-surface': '#2b2d42',
                        'surface-container': '#ffffff',
                        'accent': '#f6bd60',
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
        .nav-active {
            color: #6b9080;
            font-weight: bold;
        }
        .nav-active span.material-symbols-outlined {
            font-variation-settings: 'FILL' 1;
        }
    </style>
</head>
<body class="bg-background text-on-surface font-sans min-h-screen pb-32">

<header class="pt-12 px-6 max-w-4xl mx-auto flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold tracking-tight"><?= $lang['journal_title'] ?></h1>
        <p class="text-on-surface/60 mt-1 text-sm"><?= $lang['journal_subtitle'] ?></p>
    </div>
</header>

<main class="mt-8 px-6 max-w-4xl mx-auto space-y-6">

    <?php if ($success): ?>
        <div class="bg-green-100 text-green-800 p-4 rounded-2xl mb-6 text-center text-sm font-semibold flex items-center justify-center gap-2">
            <span class="material-symbols-outlined">check_circle</span>
            <?= $lang['journal_saved'] ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="journal.php" class="glass-card rounded-3xl p-6 shadow-sm">
        
        <div class="mb-6">
            <label class="block text-accent font-bold mb-3 flex items-start gap-2">
                <span class="material-symbols-outlined mt-0.5">psychiatry</span>
                <span class="text-lg"><?= htmlspecialchars($daily_prompt) ?></span>
            </label>
            <input type="hidden" name="prompt" value="<?= htmlspecialchars($daily_prompt) ?>">
            
            <textarea name="content" rows="6" required placeholder="<?= $lang['journal_placeholder'] ?>" class="w-full px-5 py-4 rounded-2xl border border-primary/20 bg-background/50 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all resize-none"></textarea>
        </div>

        <button type="submit" class="w-full bg-accent hover:bg-yellow-500 text-white font-bold py-4 rounded-2xl transition-colors shadow-lg shadow-accent/20 flex justify-center items-center gap-2">
            <span class="material-symbols-outlined">edit_document</span>
            <?= $lang['save_journal'] ?>
        </button>
    </form>

</main>

<nav class="fixed bottom-0 left-0 w-full bg-white/90 backdrop-blur-xl border-t border-primary/5 z-50 pb-safe">
    <div class="flex justify-around items-center h-20 px-6 max-w-4xl mx-auto">
        <a class="flex flex-col items-center justify-center text-on-surface/40 hover:text-primary transition-colors" href="index.php">
            <span class="material-symbols-outlined text-3xl">home_mini</span>
            <span class="text-xs font-semibold mt-1"><?= $lang['nav_home'] ?></span>
        </a>
        <a class="flex flex-col items-center justify-center text-on-surface/40 hover:text-primary transition-colors" href="mood.php">
            <span class="material-symbols-outlined text-3xl">mood</span>
            <span class="text-xs font-semibold mt-1"><?= $lang['nav_mood'] ?></span>
        </a>
        <a class="flex flex-col items-center justify-center nav-active" href="journal.php">
            <span class="material-symbols-outlined text-3xl">edit_note</span>
            <span class="text-xs font-semibold mt-1"><?= $lang['nav_journal'] ?></span>
        </a>
        <a class="flex flex-col items-center justify-center text-on-surface/40 hover:text-primary transition-colors" href="habits.php">
            <span class="material-symbols-outlined text-3xl">checklist</span>
            <span class="text-xs font-semibold mt-1"><?= $lang['nav_habits'] ?></span>
        </a>
    </div>
</nav>

</body>
</html>
