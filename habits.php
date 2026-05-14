<?php
require_once 'includes/auth.php';
require_login();
require_once 'db.php';

$user_id = $_SESSION['user_id'];
$success = false;

// Default habits if none exist for user
$default_habits = [
    $lang['habit_water'],
    $lang['habit_meditate'],
    $lang['habit_walk']
];

$date_today = date('Y-m-d');

// Check if user has habits, if not create defaults
$stmt = $pdo->prepare("SELECT id, habit_name, is_completed_today, last_updated FROM habits WHERE user_id = ?");
$stmt->execute([$user_id]);
$habits = $stmt->fetchAll();

if (empty($habits)) {
    $insert = $pdo->prepare("INSERT INTO habits (user_id, habit_name, is_completed_today, last_updated) VALUES (?, ?, FALSE, ?)");
    foreach ($default_habits as $name) {
        $insert->execute([$user_id, $name, $date_today]);
    }
    // Refresh habits
    $stmt->execute([$user_id]);
    $habits = $stmt->fetchAll();
}

// Check if we need to reset for a new day
$needs_update = false;
foreach ($habits as &$habit) {
    if ($habit['last_updated'] !== $date_today) {
        $habit['is_completed_today'] = 0;
        $habit['last_updated'] = $date_today;
        
        $update = $pdo->prepare("UPDATE habits SET is_completed_today = FALSE, last_updated = ? WHERE id = ?");
        $update->execute([$date_today, $habit['id']]);
    }
}
unset($habit);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $completed_ids = $_POST['habits'] ?? [];
    
    // First reset all to false
    $reset = $pdo->prepare("UPDATE habits SET is_completed_today = FALSE WHERE user_id = ?");
    $reset->execute([$user_id]);
    
    // Set checked ones to true
    if (!empty($completed_ids)) {
        $inQuery = implode(',', array_fill(0, count($completed_ids), '?'));
        $update = $pdo->prepare("UPDATE habits SET is_completed_today = TRUE WHERE user_id = ? AND id IN ($inQuery)");
        $params = array_merge([$user_id], $completed_ids);
        $update->execute($params);
    }
    
    $success = true;
    
    // Refresh
    $stmt->execute([$user_id]);
    $habits = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?= $lang['app_name'] ?> - <?= $lang['nav_habits'] ?></title>
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
                        'accent-blue': '#5c9ce6',
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
        
        /* Custom Checkbox */
        .habit-checkbox {
            appearance: none;
            width: 2rem;
            height: 2rem;
            border: 2px solid #5c9ce6;
            border-radius: 0.5rem;
            outline: none;
            cursor: pointer;
            position: relative;
            transition: all 0.2s;
        }
        .habit-checkbox:checked {
            background-color: #5c9ce6;
        }
        .habit-checkbox:checked::after {
            content: '✓';
            position: absolute;
            color: white;
            font-size: 1.2rem;
            font-weight: bold;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        .habit-item:has(input:checked) {
            opacity: 0.7;
            background-color: rgba(92, 156, 230, 0.05);
        }
        .habit-item:has(input:checked) span {
            text-decoration: line-through;
            color: #8d99ae;
        }
    </style>
</head>
<body class="bg-background text-on-surface font-sans min-h-screen pb-32">

<header class="pt-12 px-6 max-w-4xl mx-auto flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold tracking-tight"><?= $lang['habits_title'] ?></h1>
        <p class="text-on-surface/60 mt-1 text-sm"><?= $lang['habits_subtitle'] ?></p>
    </div>
</header>

<main class="mt-8 px-6 max-w-4xl mx-auto space-y-6">

    <?php if ($success): ?>
        <div class="bg-blue-100 text-blue-800 p-4 rounded-2xl mb-6 text-center text-sm font-semibold flex items-center justify-center gap-2">
            <span class="material-symbols-outlined">check_circle</span>
            <?= $lang['habits_saved'] ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="habits.php" class="glass-card rounded-3xl p-6 shadow-sm">
        
        <div class="space-y-4 mb-8">
            <?php foreach ($habits as $habit): ?>
                <label class="habit-item flex items-center gap-4 p-4 rounded-2xl border border-primary/10 transition-colors cursor-pointer hover:bg-surface-container">
                    <input type="checkbox" name="habits[]" value="<?= $habit['id'] ?>" class="habit-checkbox" <?= $habit['is_completed_today'] ? 'checked' : '' ?>>
                    <span class="text-lg font-semibold transition-all"><?= htmlspecialchars($habit['habit_name']) ?></span>
                </label>
            <?php endforeach; ?>
        </div>

        <button type="submit" class="w-full bg-accent-blue hover:bg-blue-600 text-white font-bold py-4 rounded-2xl transition-colors shadow-lg shadow-blue-500/20 flex justify-center items-center gap-2">
            <span class="material-symbols-outlined">save</span>
            <?= $lang['save_habits'] ?>
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
        <a class="flex flex-col items-center justify-center text-on-surface/40 hover:text-primary transition-colors" href="journal.php">
            <span class="material-symbols-outlined text-3xl">edit_note</span>
            <span class="text-xs font-semibold mt-1"><?= $lang['nav_journal'] ?></span>
        </a>
        <a class="flex flex-col items-center justify-center nav-active" href="habits.php">
            <span class="material-symbols-outlined text-3xl">checklist</span>
            <span class="text-xs font-semibold mt-1"><?= $lang['nav_habits'] ?></span>
        </a>
    </div>
</nav>

</body>
</html>
