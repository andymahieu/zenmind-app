<?php
require_once 'includes/auth.php';
require_login();
require_once 'db.php';

$user_id = $_SESSION['user_id'];
$search  = trim($_GET['q'] ?? '');

$sql = "SELECT id, prompt, content, created_at FROM journal_entries WHERE user_id = ?";
$params = [$user_id];
if ($search) {
    $sql .= " AND (content LIKE ? OR prompt LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$entries = $stmt->fetchAll();

// Theme
$themes = [
    'sage'     => ['primary' => '#6b9080', 'primary_hover' => '#5a7c6f', 'accent' => '#f6bd60'],
    'lavender' => ['primary' => '#7c6b9f', 'primary_hover' => '#6a5a8a', 'accent' => '#c084fc'],
    'sunset'   => ['primary' => '#b05e38', 'primary_hover' => '#93492a', 'accent' => '#f97316'],
];
$theme = $themes[$current_theme] ?? $themes['sage'];
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?= $lang['app_name'] ?> – <?= $lang['journal_history_title'] ?></title>
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
                        accent: '<?= $theme['accent'] ?>',
                        'on-surface': '#2b2d42',
                    },
                    fontFamily: { sans: ['Outfit', 'sans-serif'] }
                }
            }
        }
    </script>
    <style>
        .glass-card { background: rgba(255,255,255,0.9); backdrop-filter: blur(10px); border: 1px solid rgba(107,144,128,0.1); }
        .nav-active { color: <?= $theme['primary'] ?>; font-weight: bold; }
        /* Modal */
        #modal-overlay { display: none; }
        #modal-overlay.open { display: flex; }
    </style>
</head>
<body class="bg-background text-on-surface font-sans min-h-screen pb-32">

<header class="pt-12 px-6 max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold tracking-tight"><?= $lang['journal_history_title'] ?></h1>
    <p class="text-on-surface/60 mt-1 text-sm"><?= $lang['journal_history_subtitle'] ?></p>
</header>

<main class="mt-8 px-6 max-w-4xl mx-auto space-y-5">

    <!-- Search Bar -->
    <form method="GET" action="journal_history.php" class="relative">
        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface/30">search</span>
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
            placeholder="<?= $lang['search_journal'] ?>"
            class="w-full pl-11 pr-4 py-3.5 rounded-2xl border border-primary/20 bg-white focus:outline-none focus:ring-2 focus:ring-primary transition-all text-sm">
    </form>

    <?php if (empty($entries)): ?>
        <div class="glass-card rounded-3xl p-10 text-center text-on-surface/50">
            <span class="material-symbols-outlined text-5xl mb-3 block text-primary/30">edit_note</span>
            <?= $search ? 'Geen resultaten gevonden.' : $lang['no_journals_yet'] ?>
        </div>
    <?php else: ?>

        <!-- Entry Count -->
        <p class="text-xs text-on-surface/40 font-semibold px-1">
            <?= count($entries) ?> <?= $lang['mood_entries'] ?>
            <?= $search ? '– "'.htmlspecialchars($search).'"' : '' ?>
        </p>

        <!-- Entry Cards -->
        <div class="space-y-4">
            <?php foreach ($entries as $entry): ?>
                <div class="glass-card rounded-3xl p-5 shadow-sm hover:shadow-md transition-all cursor-pointer"
                     onclick="openModal(<?= htmlspecialchars(json_encode($entry)) ?>)">
                    <!-- Prompt badge -->
                    <div class="flex items-start gap-2 mb-3">
                        <span class="material-symbols-outlined text-accent text-lg mt-0.5 flex-shrink-0">psychiatry</span>
                        <p class="text-xs font-semibold text-accent leading-tight"><?= htmlspecialchars($entry['prompt']) ?></p>
                    </div>
                    <!-- Preview -->
                    <p class="text-sm text-on-surface/70 line-clamp-2 leading-relaxed"><?= htmlspecialchars($entry['content']) ?></p>
                    <!-- Footer -->
                    <div class="flex items-center justify-between mt-3">
                        <span class="text-xs text-on-surface/30 font-semibold"><?= date('d M Y · H:i', strtotime($entry['created_at'])) ?></span>
                        <span class="text-xs font-semibold text-primary flex items-center gap-1">
                            <?= $lang['read_more'] ?> <span class="material-symbols-outlined text-sm">arrow_forward</span>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>

</main>

<!-- Modal Overlay -->
<div id="modal-overlay" class="open:flex fixed inset-0 z-50 bg-black/40 backdrop-blur-sm items-end justify-center p-4"
     onclick="closeModal(event)">
    <div id="modal-card" class="w-full max-w-lg bg-white rounded-3xl p-7 shadow-2xl max-h-[80vh] overflow-y-auto">
        <div class="flex items-start gap-2 mb-4">
            <span class="material-symbols-outlined text-accent text-xl mt-0.5 flex-shrink-0">psychiatry</span>
            <p id="modal-prompt" class="text-sm font-semibold text-accent leading-snug"></p>
        </div>
        <p id="modal-content" class="text-on-surface leading-relaxed text-sm whitespace-pre-wrap mb-4"></p>
        <p id="modal-date" class="text-xs text-on-surface/30 font-semibold"></p>
        <button onclick="document.getElementById('modal-overlay').classList.remove('open')"
            class="mt-5 w-full py-3 rounded-2xl bg-primary text-white font-bold text-sm hover:bg-primary-hover transition-colors">
            Sluiten
        </button>
    </div>
</div>

<nav class="fixed bottom-0 left-0 w-full bg-white/90 backdrop-blur-xl border-t border-primary/5 z-40">
    <div class="flex justify-around items-center h-20 px-6 max-w-4xl mx-auto">
        <a class="flex flex-col items-center justify-center text-on-surface/40 hover:text-primary transition-colors" href="index.php">
            <span class="material-symbols-outlined text-3xl">home_mini</span>
            <span class="text-xs font-semibold mt-1"><?= $lang['nav_home'] ?></span>
        </a>
        <a class="flex flex-col items-center justify-center text-on-surface/40 hover:text-primary transition-colors" href="mood.php">
            <span class="material-symbols-outlined text-3xl">mood</span>
            <span class="text-xs font-semibold mt-1"><?= $lang['nav_mood'] ?></span>
        </a>
        <a class="flex flex-col items-center justify-center nav-active" href="journal_history.php">
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

<script>
    function openModal(entry) {
        document.getElementById('modal-prompt').textContent   = entry.prompt;
        document.getElementById('modal-content').textContent  = entry.content;
        document.getElementById('modal-date').textContent     = entry.created_at;
        document.getElementById('modal-overlay').classList.add('open');
    }
    function closeModal(e) {
        if (e.target === document.getElementById('modal-overlay')) {
            document.getElementById('modal-overlay').classList.remove('open');
        }
    }
</script>
</body>
</html>
