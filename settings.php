<?php
require_once 'includes/auth.php';
require_login();
require_once 'db.php';

$user_id = $_SESSION['user_id'];
$success = '';
$error   = '';

// ── Handle: Save language & theme ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'settings') {
    $new_lang  = $_POST['language'] ?? $current_lang;
    $new_theme = $_POST['theme']    ?? 'sage';

    $allowed_langs  = ['nl','en','fr'];
    $allowed_themes = ['sage','lavender','sunset'];

    if (!in_array($new_lang,  $allowed_langs))  $new_lang  = 'nl';
    if (!in_array($new_theme, $allowed_themes)) $new_theme = 'sage';

    $pdo->prepare("UPDATE users SET language = ?, theme = ? WHERE id = ?")
        ->execute([$new_lang, $new_theme, $user_id]);

    $_SESSION['lang']  = $new_lang;
    $_SESSION['theme'] = $new_theme;

    // Reload language immediately
    require_once __DIR__ . '/lang/' . $new_lang . '.php';
    $current_lang  = $new_lang;
    $current_theme = $new_theme;

    $success = $lang['settings_saved'];
}

// ── Handle: Change password ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'password') {
    $new_pass     = $_POST['new_password']     ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    if ($new_pass && $new_pass === $confirm_pass) {
        $hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $user_id]);
        $success = $lang['password_updated'];
    } else {
        $error = $lang['password_mismatch'];
    }
}

// Theme CSS values
$themes = [
    'sage'     => ['primary' => '#6b9080', 'primary_hover' => '#5a7c6f', 'accent' => '#f6bd60', 'swatch' => 'bg-[#6b9080]'],
    'lavender' => ['primary' => '#7c6b9f', 'primary_hover' => '#6a5a8a', 'accent' => '#c084fc', 'swatch' => 'bg-[#7c6b9f]'],
    'sunset'   => ['primary' => '#b05e38', 'primary_hover' => '#93492a', 'accent' => '#f97316', 'swatch' => 'bg-[#b05e38]'],
];
$theme = $themes[$current_theme] ?? $themes['sage'];
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?= $lang['app_name'] ?> – <?= $lang['settings_title'] ?></title>
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
        .glass-card { background: rgba(255,255,255,0.9); backdrop-filter: blur(10px); border: 1px solid rgba(107,144,128,0.12); }
        .nav-active { color: <?= $theme['primary'] ?>; font-weight: bold; }
        .theme-radio:checked + .theme-swatch { ring: 3px; outline: 3px solid <?= $theme['primary'] ?>; outline-offset: 2px; }
        .section-title { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: rgba(43,45,66,0.45); margin-bottom: 0.75rem; }
    </style>
</head>
<body class="bg-background text-on-surface font-sans min-h-screen pb-32">

<header class="pt-12 px-6 max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold tracking-tight"><?= $lang['settings_title'] ?></h1>
</header>

<main class="mt-8 px-6 max-w-4xl mx-auto space-y-6">

    <!-- Feedback -->
    <?php if ($success): ?>
        <div class="bg-green-100 text-green-800 p-4 rounded-2xl text-center text-sm font-semibold flex items-center justify-center gap-2">
            <span class="material-symbols-outlined">check_circle</span> <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 p-4 rounded-2xl text-center text-sm font-semibold flex items-center justify-center gap-2">
            <span class="material-symbols-outlined">error</span> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Profile + Language + Theme -->
    <form method="POST" action="settings.php" class="glass-card rounded-3xl p-6 shadow-sm space-y-6">
        <input type="hidden" name="action" value="settings">

        <!-- Language -->
        <div>
            <p class="section-title"><?= $lang['profile_settings'] ?> — Taal / Language / Langue</p>
            <div class="flex gap-3">
                <?php foreach (['nl' => '🇧🇪 NL', 'en' => '🇬🇧 EN', 'fr' => '🇫🇷 FR'] as $code => $label): ?>
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" name="language" value="<?= $code ?>" class="sr-only peer" <?= $current_lang === $code ? 'checked' : '' ?>>
                        <div class="py-3 rounded-2xl border-2 text-center text-sm font-bold transition-all
                            peer-checked:border-primary peer-checked:bg-primary/10 peer-checked:text-primary
                            border-on-surface/10 text-on-surface/50 hover:border-primary/40">
                            <?= $label ?>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Theme -->
        <div>
            <p class="section-title"><?= $lang['theme_settings'] ?></p>
            <div class="flex gap-4">
                <?php
                $theme_options = [
                    'sage'     => ['color' => '#6b9080', 'label' => $lang['theme_sage']],
                    'lavender' => ['color' => '#7c6b9f', 'label' => $lang['theme_lavender']],
                    'sunset'   => ['color' => '#b05e38', 'label' => $lang['theme_sunset']],
                ];
                foreach ($theme_options as $t_key => $t_val):
                ?>
                    <label class="flex flex-col items-center gap-2 cursor-pointer">
                        <input type="radio" name="theme" value="<?= $t_key ?>" class="sr-only peer" <?= $current_theme === $t_key ? 'checked' : '' ?>>
                        <div class="w-12 h-12 rounded-full border-4 transition-all
                            peer-checked:border-on-surface peer-checked:scale-110
                            border-transparent hover:scale-105"
                            style="background: <?= $t_val['color'] ?>"></div>
                        <span class="text-xs font-semibold text-on-surface/60"><?= $t_val['label'] ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <button type="submit" class="w-full bg-primary hover:bg-primary-hover text-white font-bold py-4 rounded-2xl transition-colors shadow-lg flex justify-center items-center gap-2">
            <span class="material-symbols-outlined">save</span>
            <?= $lang['save_settings'] ?>
        </button>
    </form>

    <!-- Change Password -->
    <div class="glass-card rounded-3xl p-6 shadow-sm">
        <p class="section-title"><?= $lang['change_password'] ?></p>
        <form method="POST" action="settings.php" class="space-y-4">
            <input type="hidden" name="action" value="password">
            <div>
                <label class="block text-sm font-semibold mb-1.5 text-on-surface/60"><?= $lang['new_password'] ?></label>
                <input type="password" name="new_password" required
                    class="w-full px-4 py-3.5 rounded-2xl border border-primary/20 bg-background/50 focus:outline-none focus:ring-2 focus:ring-primary transition-all text-sm">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1.5 text-on-surface/60"><?= $lang['confirm_password'] ?></label>
                <input type="password" name="confirm_password" required
                    class="w-full px-4 py-3.5 rounded-2xl border border-primary/20 bg-background/50 focus:outline-none focus:ring-2 focus:ring-primary transition-all text-sm">
            </div>
            <button type="submit" class="w-full bg-on-surface/80 hover:bg-on-surface text-white font-bold py-4 rounded-2xl transition-colors flex justify-center items-center gap-2">
                <span class="material-symbols-outlined">lock_reset</span>
                <?= $lang['change_password'] ?>
            </button>
        </form>
    </div>

    <!-- Logout -->
    <a href="logout.php" class="glass-card rounded-3xl p-5 shadow-sm flex items-center gap-4 hover:shadow-md transition-all active:scale-95 group">
        <div class="w-12 h-12 rounded-2xl bg-red-50 flex items-center justify-center text-red-500 group-hover:bg-red-500 group-hover:text-white transition-colors">
            <span class="material-symbols-outlined">logout</span>
        </div>
        <span class="font-bold text-on-surface/80"><?= $lang['logout'] ?></span>
        <span class="material-symbols-outlined text-on-surface/30 ml-auto">arrow_forward_ios</span>
    </a>

</main>

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
        <a class="flex flex-col items-center justify-center text-on-surface/40 hover:text-primary transition-colors" href="habits.php">
            <span class="material-symbols-outlined text-3xl">checklist</span>
            <span class="text-xs font-semibold mt-1"><?= $lang['nav_habits'] ?></span>
        </a>
        <a class="flex flex-col items-center justify-center nav-active" href="settings.php">
            <span class="material-symbols-outlined text-3xl">settings</span>
            <span class="text-xs font-semibold mt-1"><?= $lang['nav_settings'] ?></span>
        </a>
    </div>
</nav>
</body>
</html>
