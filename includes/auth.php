<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

// Language handler
if (isset($_GET['lang'])) {
    $allowed_langs = ['nl', 'en', 'fr'];
    if (in_array($_GET['lang'], $allowed_langs)) {
        $_SESSION['lang'] = $_GET['lang'];
    }
}

// Determine current language
$current_lang = $_SESSION['lang'] ?? 'nl';
$lang_file = __DIR__ . '/../lang/' . $current_lang . '.php';

if (file_exists($lang_file)) {
    require_once $lang_file;
} else {
    require_once __DIR__ . '/../lang/nl.php'; // fallback
}

// Make $lang accessible globally
global $lang;
?>
