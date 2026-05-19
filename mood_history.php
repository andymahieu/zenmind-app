<?php
require_once 'includes/auth.php';
require_login();
require_once 'db.php';

$user_id = $_SESSION['user_id'];

// Filter
$filter = $_GET['filter'] ?? 'all';
$allowed_filters = ['all', '7days', '30days'];
if (!in_array($filter, $allowed_filters)) $filter = 'all';

$where_date = '';
if ($filter === '7days')  $where_date = "AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
if ($filter === '30days') $where_date = "AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";

// Fetch mood logs
$stmt = $pdo->prepare("SELECT mood_score, note, created_at FROM mood_logs WHERE user_id = ? $where_date ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$logs = $stmt->fetchAll();

// Statistics: count per score
$counts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
foreach ($logs as $l) { $counts[$l['mood_score']]++; }
$total = array_sum($counts);
$most_frequent_score = $total > 0 ? array_search(max($counts), $counts) : null;

// Last 7 days chart data
$chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $day_stmt = $pdo->prepare("SELECT AVG(mood_score) as avg FROM mood_logs WHERE user_id = ? AND DATE(created_at) = ?");
    $day_stmt->execute([$user_id, $d]);
    $chart_data[] = ['date' => date('D', strtotime($d)), 'avg' => round((float)($day_stmt->fetchColumn() ?? 0), 1)];
}

function moodEmoji($s) {
    return [1=>'😭',2=>'😔',3=>'😐',4=>'🙂',5=>'😍'][$s] ?? '☁️';
}
function moodColor($s) {
    return [1=>'#e56b6f',2=>'#f4a261',3=>'#e9c46a',4=>'#90be6d',5=>'#43aa8b'][$s] ?? '#ccc';
}

// Theme
$themes = [
    'sage'     => ['primary' => '#6b9080', 'primary_hover' => '#5a7c6f'],
    'lavender' => ['primary' => '#7c6b9f', 'primary_hover' => '#6a5a8a'],
    'sunset'   => ['primary' => '#b05e38', 'primary_hover' => '#93492a'],
];
$theme = $themes[$current_theme] ?? $themes['sage'];
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?= $lang['app_name'] ?> – <?= $lang['mood_history_title'] ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        background: '#f8f9fa', primary: '<?= $theme['primary'] ?>',
                        'primary-hover': '<?= $theme['primary_hover'] ?>',
                        'on-surface': '#2b2d42', 'surface-container': '#ffffff',
                    },
                    fontFamily: { sans: ['Outfit', 'sans-serif'] }
                }
            }
        }
    </script>
    <style>
        .glass-card { background: rgba(255,255,255,0.9); backdrop-filter: blur(10px); border: 1px solid rgba(107,144,128,0.1); }
        .nav-active { color: <?= $theme['primary'] ?>; font-weight: bold; }
        .bar-wrap { display: flex; align-items: flex-end; gap: 6px; height: 80px; }
        .bar { border-radius: 6px 6px 0 0; min-width: 28px; flex: 1; transition: height 0.6s ease; }
    </style>
</head>
<body class="bg-background text-on-surface font-sans min-h-screen pb-32">

<header class="pt-12 px-6 max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold tracking-tight"><?= $lang['mood_history_title'] ?></h1>
    <p class="text-on-surface/60 mt-1 text-sm"><?= $lang['mood_history_subtitle'] ?></p>
</header>

<main class="mt-8 px-6 max-w-4xl mx-auto space-y-6">

    <!-- Filter Pills -->
    <div class="flex gap-2 flex-wrap">
        <?php foreach (['all' => $lang['filter_all'], '7days' => $lang['filter_7days'], '30days' => $lang['filter_30days']] as $f => $label): ?>
            <a href="?filter=<?= $f ?>" class="px-4 py-1.5 rounded-full text-sm font-semibold transition-colors <?= $filter === $f ? 'bg-primary text-white shadow' : 'bg-white border border-primary/20 text-on-surface/60 hover:border-primary' ?>">
                <?= $label ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($total === 0): ?>
        <div class="glass-card rounded-3xl p-10 text-center text-on-surface/50">
            <span class="material-symbols-outlined text-5xl mb-3 block text-primary/40">mood</span>
            <?= $lang['no_moods_yet'] ?>
        </div>
    <?php else: ?>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 gap-4">
        <div class="glass-card rounded-3xl p-5 text-center shadow-sm">
            <span class="text-on-surface/50 text-xs font-semibold uppercase tracking-wider block mb-2"><?= $lang['most_frequent_mood'] ?></span>
            <div class="text-5xl"><?= moodEmoji($most_frequent_score) ?></div>
        </div>
        <div class="glass-card rounded-3xl p-5 text-center shadow-sm">
            <span class="text-on-surface/50 text-xs font-semibold uppercase tracking-wider block mb-2"><?= $lang['mood_entries'] ?></span>
            <div class="text-4xl font-bold text-primary"><?= $total ?></div>
        </div>
    </div>

    <!-- Score Distribution -->
    <div class="glass-card rounded-3xl p-6 shadow-sm">
        <h2 class="font-bold text-base mb-4"><?= $lang['mood_stats'] ?></h2>
        <div class="space-y-3">
            <?php foreach ([5,4,3,2,1] as $score): ?>
                <?php $pct = $total > 0 ? round(($counts[$score] / $total) * 100) : 0; ?>
                <div class="flex items-center gap-3">
                    <span class="text-2xl w-8 text-center"><?= moodEmoji($score) ?></span>
                    <div class="flex-grow bg-on-surface/5 rounded-full h-3 overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-700" style="width:<?= $pct ?>%; background:<?= moodColor($score) ?>"></div>
                    </div>
                    <span class="text-sm font-bold text-on-surface/60 w-10 text-right"><?= $pct ?>%</span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 7-Day Bar Chart -->
    <div class="glass-card rounded-3xl p-6 shadow-sm">
        <h2 class="font-bold text-base mb-4"><?= $lang['mood_chart'] ?> (7 <?= $lang['filter_7days'] ?>)</h2>
        <div class="bar-wrap">
            <?php foreach ($chart_data as $d): ?>
                <?php $h = $d['avg'] > 0 ? (int)(($d['avg'] / 5) * 80) : 4; ?>
                <div class="flex flex-col items-center flex-1 gap-1">
                    <span class="text-xs font-bold text-primary"><?= $d['avg'] > 0 ? $d['avg'] : '' ?></span>
                    <div class="bar" style="height:<?= $h ?>px; background: <?= $d['avg'] >= 4 ? '#43aa8b' : ($d['avg'] >= 3 ? '#e9c46a' : ($d['avg'] > 0 ? '#e56b6f' : '#e4eade')) ?>"></div>
                    <span class="text-xs text-on-surface/50 font-semibold"><?= $d['date'] ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Log Entries -->
    <div class="glass-card rounded-3xl p-6 shadow-sm">
        <h2 class="font-bold text-base mb-4"><?= $lang['mood_entries'] ?></h2>
        <div class="space-y-3 max-h-96 overflow-y-auto pr-1">
            <?php foreach ($logs as $log): ?>
                <div class="flex gap-4 items-start p-3 rounded-2xl border border-on-surface/5 hover:bg-on-surface/5 transition-colors">
                    <div class="text-3xl flex-shrink-0"><?= moodEmoji($log['mood_score']) ?></div>
                    <div class="flex-grow min-w-0">
                        <div class="text-xs text-on-surface/40 font-semibold mb-0.5">
                            <?= date('d M Y · H:i', strtotime($log['created_at'])) ?>
                        </div>
                        <?php if ($log['note']): ?>
                            <p class="text-sm text-on-surface/70 truncate"><?= htmlspecialchars($log['note']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php endif; ?>

</main>

<nav class="fixed bottom-0 left-0 w-full bg-white/90 backdrop-blur-xl border-t border-primary/5 z-50">
    <div class="flex justify-around items-center h-20 px-6 max-w-4xl mx-auto">
        <a class="flex flex-col items-center justify-center text-on-surface/40 hover:text-primary transition-colors" href="index.php">
            <span class="material-symbols-outlined text-3xl">home_mini</span>
            <span class="text-xs font-semibold mt-1"><?= $lang['nav_home'] ?></span>
        </a>
        <a class="flex flex-col items-center justify-center nav-active" href="mood_history.php">
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
        <a class="flex flex-col items-center justify-center text-on-surface/40 hover:text-primary transition-colors" href="settings.php">
            <span class="material-symbols-outlined text-3xl">settings</span>
            <span class="text-xs font-semibold mt-1"><?= $lang['nav_settings'] ?></span>
        </a>
    </div>
</nav>
</body>
</html>
