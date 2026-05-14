<?php
require_once 'includes/auth.php';
require_login();
require_once 'db.php';

$user_id = $_SESSION['user_id'];
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mood_score = (int)($_POST['mood_score'] ?? 3);
    $note = trim($_POST['note'] ?? '');
    
    $stmt = $pdo->prepare("INSERT INTO mood_logs (user_id, mood_score, note) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $mood_score, $note]);
    $success = true;
}
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?= $lang['app_name'] ?> - <?= $lang['nav_mood'] ?></title>
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
        .mood-btn {
            transition: all 0.2s ease-in-out;
            filter: grayscale(100%);
            opacity: 0.5;
        }
        .mood-btn:hover {
            transform: scale(1.1);
            filter: grayscale(0%);
            opacity: 1;
        }
        input[type="radio"]:checked + .mood-btn {
            filter: grayscale(0%);
            opacity: 1;
            transform: scale(1.2);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            border-color: #6b9080;
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
        <h1 class="text-3xl font-bold tracking-tight"><?= $lang['mood_title'] ?></h1>
        <p class="text-on-surface/60 mt-1 text-sm"><?= $lang['mood_subtitle'] ?></p>
    </div>
</header>

<main class="mt-8 px-6 max-w-4xl mx-auto space-y-6">

    <?php if ($success): ?>
        <div class="bg-green-100 text-green-800 p-4 rounded-2xl mb-6 text-center text-sm font-semibold flex items-center justify-center gap-2">
            <span class="material-symbols-outlined">check_circle</span>
            <?= $lang['mood_saved'] ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="mood.php" class="glass-card rounded-3xl p-6 shadow-sm">
        
        <div class="flex justify-between items-center py-6 mb-4">
            <label class="cursor-pointer flex flex-col items-center">
                <input type="radio" name="mood_score" value="1" class="hidden" required>
                <div class="mood-btn w-12 h-12 md:w-16 md:h-16 flex items-center justify-center rounded-full bg-white border-2 border-transparent text-3xl md:text-4xl">😭</div>
                <span class="text-xs mt-2 text-on-surface/50 font-semibold"><?= $lang['mood_terrible'] ?></span>
            </label>
            
            <label class="cursor-pointer flex flex-col items-center">
                <input type="radio" name="mood_score" value="2" class="hidden">
                <div class="mood-btn w-12 h-12 md:w-16 md:h-16 flex items-center justify-center rounded-full bg-white border-2 border-transparent text-3xl md:text-4xl">😔</div>
                <span class="text-xs mt-2 text-on-surface/50 font-semibold"><?= $lang['mood_bad'] ?></span>
            </label>

            <label class="cursor-pointer flex flex-col items-center">
                <input type="radio" name="mood_score" value="3" class="hidden" checked>
                <div class="mood-btn w-12 h-12 md:w-16 md:h-16 flex items-center justify-center rounded-full bg-white border-2 border-transparent text-3xl md:text-4xl">😐</div>
                <span class="text-xs mt-2 text-on-surface/50 font-semibold"><?= $lang['mood_okay'] ?></span>
            </label>

            <label class="cursor-pointer flex flex-col items-center">
                <input type="radio" name="mood_score" value="4" class="hidden">
                <div class="mood-btn w-12 h-12 md:w-16 md:h-16 flex items-center justify-center rounded-full bg-white border-2 border-transparent text-3xl md:text-4xl">🙂</div>
                <span class="text-xs mt-2 text-on-surface/50 font-semibold"><?= $lang['mood_good'] ?></span>
            </label>

            <label class="cursor-pointer flex flex-col items-center">
                <input type="radio" name="mood_score" value="5" class="hidden">
                <div class="mood-btn w-12 h-12 md:w-16 md:h-16 flex items-center justify-center rounded-full bg-white border-2 border-transparent text-3xl md:text-4xl">😍</div>
                <span class="text-xs mt-2 text-on-surface/50 font-semibold"><?= $lang['mood_awesome'] ?></span>
            </label>
        </div>

        <div class="mb-6">
            <textarea name="note" rows="3" placeholder="<?= $lang['mood_note'] ?>" class="w-full px-5 py-4 rounded-2xl border border-primary/20 bg-background/50 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all resize-none"></textarea>
        </div>

        <button type="submit" class="w-full bg-primary hover:bg-primary-hover text-white font-bold py-4 rounded-2xl transition-colors shadow-lg shadow-primary/20 flex justify-center items-center gap-2">
            <span class="material-symbols-outlined">favorite</span>
            <?= $lang['save_mood'] ?>
        </button>
    </form>

</main>

<nav class="fixed bottom-0 left-0 w-full bg-white/90 backdrop-blur-xl border-t border-primary/5 z-50 pb-safe">
    <div class="flex justify-around items-center h-20 px-6 max-w-4xl mx-auto">
        <a class="flex flex-col items-center justify-center text-on-surface/40 hover:text-primary transition-colors" href="index.php">
            <span class="material-symbols-outlined text-3xl">home_mini</span>
            <span class="text-xs font-semibold mt-1"><?= $lang['nav_home'] ?></span>
        </a>
        <a class="flex flex-col items-center justify-center nav-active" href="mood.php">
            <span class="material-symbols-outlined text-3xl">mood</span>
            <span class="text-xs font-semibold mt-1"><?= $lang['nav_mood'] ?></span>
        </a>
        <a class="flex flex-col items-center justify-center text-on-surface/40 hover:text-primary transition-colors" href="journal.php">
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
