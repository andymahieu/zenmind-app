<?php
require_once 'includes/auth.php';
require_login();
require_once 'db.php';

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';

// Get today's mood if logged
$date_today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT mood_score FROM mood_logs WHERE user_id = ? AND DATE(created_at) = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$user_id, $date_today]);
$mood_today = $stmt->fetchColumn();

// Get today's habits progress
$stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(is_completed_today) as completed FROM habits WHERE user_id = ?");
$stmt->execute([$user_id]);
$habits_stats = $stmt->fetch();
$total_habits = $habits_stats['total'] ?? 0;
$completed_habits = $habits_stats['completed'] ?? 0;
$habit_progress = ($total_habits > 0) ? round(($completed_habits / $total_habits) * 100) : 0;

function getMoodEmoji($score) {
    $emojis = [1 => '😭', 2 => '😔', 3 => '😐', 4 => '🙂', 5 => '😍'];
    return $emojis[$score] ?? '☁️';
}
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?= $lang['app_name'] ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
        <?php
        $themes = ['sage'=>['primary'=>'#6b9080','primary_hover'=>'#5a7c6f','accent'=>'#f6bd60'],'lavender'=>['primary'=>'#7c6b9f','primary_hover'=>'#6a5a8a','accent'=>'#c084fc'],'sunset'=>['primary'=>'#b05e38','primary_hover'=>'#93492a','accent'=>'#f97316']];
        $t = $themes[$current_theme] ?? $themes['sage'];
        ?>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        background: '#f8f9fa',
                        primary: '<?= $t["primary"] ?>',
                        'primary-hover': '<?= $t["primary_hover"] ?>',
                        'on-surface': '#2b2d42',
                        'surface-container': '#ffffff',
                        'accent': '<?= $t["accent"] ?>',
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
            color: <?= $t['primary'] ?>;
            font-weight: bold;
        }
        .nav-active span.material-symbols-outlined {
            font-variation-settings: 'FILL' 1;
        }
    </style>
</head>
<body class="bg-background text-on-surface font-sans min-h-screen pb-32">

<header class="pt-12 px-6 max-w-4xl mx-auto flex justify-between items-start">
    <div>
        <h1 class="text-3xl font-bold tracking-tight"><?= $lang['welcome_back'] ?>, <?= htmlspecialchars($username) ?></h1>
        <p class="text-on-surface/60 mt-1 italic text-sm max-w-xs"><?= $lang['quote_of_day'] ?></p>
    </div>
    <div class="flex items-center gap-3">
        <a href="settings.php" class="w-12 h-12 bg-surface-container rounded-full shadow-sm flex items-center justify-center text-on-surface/50 hover:text-primary transition-colors">
            <span class="material-symbols-outlined">settings</span>
        </a>
        <a href="logout.php" class="w-12 h-12 bg-surface-container rounded-full shadow-sm flex items-center justify-center text-on-surface/50 hover:text-primary transition-colors">
            <span class="material-symbols-outlined">logout</span>
        </a>
    </div>
</header>

<main class="mt-10 px-6 max-w-4xl mx-auto space-y-6">

    <!-- Status Overview -->
    <div class="grid grid-cols-2 gap-4">
        <div class="glass-card rounded-3xl p-6 shadow-sm flex flex-col items-center justify-center text-center">
            <span class="text-on-surface/60 text-sm font-semibold mb-2"><?= $lang['nav_mood'] ?></span>
            <div class="text-5xl mb-1"><?= getMoodEmoji($mood_today) ?></div>
        </div>
        <div class="glass-card rounded-3xl p-6 shadow-sm flex flex-col items-center justify-center text-center relative overflow-hidden">
            <span class="text-on-surface/60 text-sm font-semibold mb-2"><?= $lang['nav_habits'] ?></span>
            <div class="text-4xl font-bold text-primary"><?= $habit_progress ?>%</div>
            <!-- Progress background subtle effect -->
            <div class="absolute bottom-0 left-0 h-2 bg-primary/20 w-full">
                <div class="h-full bg-primary transition-all duration-1000" style="width: <?= $habit_progress ?>%"></div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div>
        <h2 class="text-lg font-bold mb-4 ml-2"><?= $lang['quick_actions'] ?></h2>
        
        <a href="mood.php" class="block mb-3 glass-card rounded-3xl p-5 shadow-sm hover:shadow-md transition-all active:scale-95 group">
            <div class="flex items-center gap-5">
                <div class="w-14 h-14 rounded-2xl bg-primary/10 flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-white transition-colors">
                    <span class="material-symbols-outlined text-3xl">mood</span>
                </div>
                <div class="flex-grow">
                    <h3 class="font-bold text-lg"><?= $lang['log_mood'] ?></h3>
                    <p class="text-xs text-on-surface/40 mt-0.5"><?= $lang['nav_mood'] ?> &rarr; <a href="mood_history.php" class="underline text-primary/60" onclick="event.stopPropagation()"><?= $lang['nav_mood_history'] ?></a></p>
                </div>
                <span class="material-symbols-outlined text-on-surface/30">arrow_forward_ios</span>
            </div>
        </a>

        <a href="journal.php" class="block mb-3 glass-card rounded-3xl p-5 shadow-sm hover:shadow-md transition-all active:scale-95 group">
            <div class="flex items-center gap-5">
                <div class="w-14 h-14 rounded-2xl bg-accent/20 flex items-center justify-center text-accent group-hover:bg-accent group-hover:text-white transition-colors">
                    <span class="material-symbols-outlined text-3xl">edit_note</span>
                </div>
                <div class="flex-grow">
                    <h3 class="font-bold text-lg"><?= $lang['write_journal'] ?></h3>
                    <p class="text-xs text-on-surface/40 mt-0.5"><?= $lang['nav_journal'] ?> &rarr; <a href="journal_history.php" class="underline text-primary/60" onclick="event.stopPropagation()"><?= $lang['nav_journal_history'] ?></a></p>
                </div>
                <span class="material-symbols-outlined text-on-surface/30">arrow_forward_ios</span>
            </div>
        </a>

        <a href="habits.php" class="block glass-card rounded-3xl p-5 shadow-sm hover:shadow-md transition-all active:scale-95 group">
            <div class="flex items-center gap-5">
                <div class="w-14 h-14 rounded-2xl bg-[#a2d2ff]/30 flex items-center justify-center text-[#5c9ce6] group-hover:bg-[#5c9ce6] group-hover:text-white transition-colors">
                    <span class="material-symbols-outlined text-3xl">checklist</span>
                </div>
                <div class="flex-grow">
                    <h3 class="font-bold text-lg"><?= $lang['check_habits'] ?></h3>
                </div>
                <span class="material-symbols-outlined text-on-surface/30">arrow_forward_ios</span>
            </div>
        </a>
    </div>

</main>

<nav class="fixed bottom-0 left-0 w-full bg-white/90 backdrop-blur-xl border-t border-primary/5 z-50 pb-safe">
    <div class="flex justify-around items-center h-20 px-2 max-w-4xl mx-auto">
        <a class="flex flex-col items-center justify-center nav-active px-2" href="index.php">
            <span class="material-symbols-outlined text-3xl">home_mini</span>
            <span class="text-xs font-semibold mt-1"><?= $lang['nav_home'] ?></span>
        </a>
        <a class="flex flex-col items-center justify-center text-on-surface/40 hover:text-primary transition-colors px-2" href="mood.php">
            <span class="material-symbols-outlined text-3xl">mood</span>
            <span class="text-xs font-semibold mt-1"><?= $lang['nav_mood'] ?></span>
        </a>
        <a class="flex flex-col items-center justify-center text-on-surface/40 hover:text-primary transition-colors px-2" href="journal.php">
            <span class="material-symbols-outlined text-3xl">edit_note</span>
            <span class="text-xs font-semibold mt-1"><?= $lang['nav_journal'] ?></span>
        </a>
        <a class="flex flex-col items-center justify-center text-on-surface/40 hover:text-primary transition-colors px-2" href="habits.php">
            <span class="material-symbols-outlined text-3xl">checklist</span>
            <span class="text-xs font-semibold mt-1"><?= $lang['nav_habits'] ?></span>
        </a>
        <a class="flex flex-col items-center justify-center text-on-surface/40 hover:text-primary transition-colors px-2" href="settings.php">
            <span class="material-symbols-outlined text-3xl">settings</span>
            <span class="text-xs font-semibold mt-1"><?= $lang['nav_settings'] ?></span>
        </a>
    </div>
</nav>

</body>
</html>
