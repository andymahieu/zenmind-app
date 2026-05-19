<?php
require_once 'includes/auth.php';
require_login();
require_once 'db.php';

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';
$date_today = date('Y-m-d');

// ── Handle: Add custom habit ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $habit_name = trim($_POST['habit_name'] ?? '');
    if ($habit_name) {
        $stmt = $pdo->prepare("INSERT INTO habits (user_id, habit_name, last_updated) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $habit_name, $date_today]);
        $success = $lang['habit_added'];
    }
}

// ── Handle: Delete habit ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $habit_id = (int)($_POST['habit_id'] ?? 0);
    if ($habit_id) {
        $stmt = $pdo->prepare("DELETE FROM habits WHERE id = ? AND user_id = ?");
        $stmt->execute([$habit_id, $user_id]);
        $success = $lang['habit_deleted'];
    }
}

// ── Default habits if none exist ────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT id FROM habits WHERE user_id = ? LIMIT 1");
$stmt->execute([$user_id]);
if (!$stmt->fetch()) {
    $defaults = [$lang['habit_water'], $lang['habit_meditate'], $lang['habit_walk']];
    $insert = $pdo->prepare("INSERT INTO habits (user_id, habit_name, last_updated) VALUES (?, ?, ?)");
    foreach ($defaults as $name) {
        $insert->execute([$user_id, $name, $date_today]);
    }
}

// ── Handle: Save checked habits ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    $completed_ids = $_POST['habits'] ?? [];

    // Reset all habits for this user
    $pdo->prepare("UPDATE habits SET is_completed_today = FALSE WHERE user_id = ?")->execute([$user_id]);

    if (!empty($completed_ids)) {
        $placeholders = implode(',', array_fill(0, count($completed_ids), '?'));
        $pdo->prepare("UPDATE habits SET is_completed_today = TRUE WHERE user_id = ? AND id IN ($placeholders)")
            ->execute(array_merge([$user_id], $completed_ids));
    }

    // ── Streak calculation ────────────────────────────────────────────────────
    // Fetch all habits
    $all = $pdo->prepare("SELECT id, is_completed_today, streak, best_streak, last_updated FROM habits WHERE user_id = ?");
    $all->execute([$user_id]);
    $all_habits = $all->fetchAll();

    $upd = $pdo->prepare("UPDATE habits SET streak = ?, best_streak = ?, last_updated = ? WHERE id = ?");
    foreach ($all_habits as $h) {
        $completed = in_array((string)$h['id'], array_map('strval', $completed_ids));
        $last = $h['last_updated'];
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $streak = (int)$h['streak'];
        $best   = (int)$h['best_streak'];

        if ($completed) {
            if ($last === $yesterday || $last === $date_today) {
                $streak = ($last === $date_today) ? $streak : $streak + 1;
            } else {
                $streak = 1; // Reset — streak broken
            }
        } else {
            // Not completed today — if last_updated was yesterday or earlier, reset
            if ($last !== $date_today) {
                $streak = 0;
            }
        }
        $best = max($best, $streak);
        $upd->execute([$streak, $best, $date_today, $h['id']]);
    }

    $success = $lang['habits_saved'];
}

// ── Load habits (with daily reset if needed) ─────────────────────────────────
$stmt = $pdo->prepare("SELECT id, habit_name, is_completed_today, last_updated, streak, best_streak FROM habits WHERE user_id = ?");
$stmt->execute([$user_id]);
$habits = $stmt->fetchAll();

foreach ($habits as &$habit) {
    if ($habit['last_updated'] !== $date_today && $habit['last_updated'] !== null) {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        if ($habit['last_updated'] !== $yesterday) {
            // Missed a day — reset streak
            $pdo->prepare("UPDATE habits SET is_completed_today = FALSE, streak = 0, last_updated = ? WHERE id = ?")
                ->execute([$date_today, $habit['id']]);
            $habit['is_completed_today'] = 0;
            $habit['streak'] = 0;
        } else {
            $pdo->prepare("UPDATE habits SET is_completed_today = FALSE WHERE id = ?")
                ->execute([$habit['id']]);
            $habit['is_completed_today'] = 0;
        }
    }
}
unset($habit);

// Reload fresh
$stmt->execute([$user_id]);
$habits = $stmt->fetchAll();

// Theme colours
$themes = [
    'sage'     => ['primary' => '#6b9080', 'primary_hover' => '#5a7c6f', 'secondary_container' => '#abf4ac', 'accent' => '#5c9ce6'],
    'lavender' => ['primary' => '#7c6b9f', 'primary_hover' => '#6a5a8a', 'secondary_container' => '#e0d7f5', 'accent' => '#c084fc'],
    'sunset'   => ['primary' => '#b05e38', 'primary_hover' => '#93492a', 'secondary_container' => '#fde8d8', 'accent' => '#f97316'],
];
$theme = $themes[$current_theme] ?? $themes['sage'];
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?= $lang['app_name'] ?> – <?= $lang['habits_title'] ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        background: '#f8f9fa',
                        primary: '<?= $theme['primary'] ?>',
                        'primary-hover': '<?= $theme['primary_hover'] ?>',
                        'on-surface': '#2b2d42',
                        'surface-container': '#ffffff',
                        'accent-blue': '<?= $theme['accent'] ?>',
                    },
                    fontFamily: { sans: ['Outfit', 'sans-serif'] }
                }
            }
        }
    </script>
    <style>
        .glass-card { background: rgba(255,255,255,0.9); backdrop-filter: blur(10px); border: 1px solid rgba(107,144,128,0.1); }
        .nav-active { color: <?= $theme['primary'] ?>; font-weight: bold; }
        .nav-active span.material-symbols-outlined { font-variation-settings: 'FILL' 1; }
        .habit-checkbox { appearance: none; width: 2rem; height: 2rem; border: 2px solid <?= $theme['accent'] ?>; border-radius: 0.5rem; outline: none; cursor: pointer; position: relative; transition: all 0.2s; }
        .habit-checkbox:checked { background-color: <?= $theme['accent'] ?>; }
        .habit-checkbox:checked::after { content: '✓'; position: absolute; color: white; font-size: 1.2rem; font-weight: bold; top: 50%; left: 50%; transform: translate(-50%, -50%); }
        .habit-item:has(input:checked) { opacity: 0.65; }
        .habit-item:has(input:checked) .habit-name { text-decoration: line-through; color: #8d99ae; }
        .streak-fire { display: inline-flex; align-items: center; gap: 2px; font-size: 0.75rem; font-weight: 700; color: #ea580c; }
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
        <div class="bg-blue-100 text-blue-800 p-4 rounded-2xl text-center text-sm font-semibold flex items-center justify-center gap-2">
            <span class="material-symbols-outlined">check_circle</span>
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <!-- Habit List Form -->
    <form method="POST" action="habits.php" class="glass-card rounded-3xl p-6 shadow-sm">
        <input type="hidden" name="action" value="save">
        <div class="space-y-3 mb-6">
            <?php foreach ($habits as $habit): ?>
                <label class="habit-item flex items-center gap-4 p-4 rounded-2xl border border-primary/10 transition-colors cursor-pointer hover:bg-white">
                    <input type="checkbox" name="habits[]" value="<?= $habit['id'] ?>" class="habit-checkbox" <?= $habit['is_completed_today'] ? 'checked' : '' ?>>
                    <div class="flex-grow min-w-0">
                        <span class="habit-name text-base font-semibold block"><?= htmlspecialchars($habit['habit_name']) ?></span>
                        <div class="flex gap-3 mt-0.5">
                            <?php if ($habit['streak'] > 0): ?>
                                <span class="streak-fire">🔥 <?= $habit['streak'] ?> <?= $lang['streak_label'] ?></span>
                            <?php endif; ?>
                            <?php if ($habit['best_streak'] > 1): ?>
                                <span class="text-xs text-on-surface/40 font-semibold">⭐ Best: <?= $habit['best_streak'] ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Delete button -->
                    <button type="button"
                        onclick="deleteHabit(<?= $habit['id'] ?>)"
                        class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-red-100 text-on-surface/30 hover:text-red-500 transition-colors flex-shrink-0"
                        title="<?= $lang['delete_habit'] ?>">
                        <span class="material-symbols-outlined text-sm">delete</span>
                    </button>
                </label>
            <?php endforeach; ?>
        </div>

        <button type="submit" class="w-full bg-accent-blue hover:opacity-90 text-white font-bold py-4 rounded-2xl transition-colors shadow-lg flex justify-center items-center gap-2">
            <span class="material-symbols-outlined">save</span>
            <?= $lang['save_habits'] ?>
        </button>
    </form>

    <!-- Add Custom Habit -->
    <div class="glass-card rounded-3xl p-6 shadow-sm">
        <h2 class="font-bold text-base mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">add_circle</span>
            <?= $lang['add_custom_habit'] ?>
        </h2>
        <form method="POST" action="habits.php" class="flex gap-3">
            <input type="hidden" name="action" value="add">
            <input type="text" name="habit_name" required placeholder="<?= $lang['custom_habit_placeholder'] ?>"
                class="flex-grow px-4 py-3 rounded-2xl border border-primary/20 bg-background/50 focus:outline-none focus:ring-2 focus:ring-primary transition-all text-sm">
            <button type="submit" class="px-5 py-3 bg-primary hover:bg-primary-hover text-white rounded-2xl font-bold transition-colors flex items-center gap-1 text-sm">
                <span class="material-symbols-outlined text-base">add</span>
            </button>
        </form>
    </div>

</main>

<!-- Hidden delete form -->
<form id="delete-form" method="POST" action="habits.php" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="habit_id" id="delete-habit-id">
</form>

<nav class="fixed bottom-0 left-0 w-full bg-white/90 backdrop-blur-xl border-t border-primary/5 z-50">
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
        <a class="flex flex-col items-center justify-center text-on-surface/40 hover:text-primary transition-colors" href="settings.php">
            <span class="material-symbols-outlined text-3xl">settings</span>
            <span class="text-xs font-semibold mt-1"><?= $lang['nav_settings'] ?></span>
        </a>
    </div>
</nav>

<script>
function deleteHabit(id) {
    if (confirm('<?= addslashes($lang['delete_habit']) ?>?')) {
        document.getElementById('delete-habit-id').value = id;
        document.getElementById('delete-form').submit();
    }
}
</script>
</body>
</html>
