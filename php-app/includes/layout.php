<?php
/**
 * Shared layout helpers for the Top Navbar structure.
 */

function layout_head(string $title): void {
    $root = defined('ROOT') ? ROOT : __DIR__ . '/..';
    // Build relative path to CSS from DOCUMENT_ROOT
    $docRoot  = rtrim(str_replace('\\','/',$_SERVER['DOCUMENT_ROOT']), '/');
    $appRoot  = rtrim(str_replace('\\','/',realpath($root)), '/');
    $rel      = ltrim(str_replace($docRoot, '', $appRoot), '/');
    $cssUrl   = '/' . $rel . '/assets/css/styles.css';
    
    echo '<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>' . htmlspecialchars($title) . ' — TSG Alert &amp; Repair Network</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Akshar:wght@500&family=DM+Sans:wght@400;500;600;700&family=Montserrat:wght@500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="' . $cssUrl . '">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js" defer></script>
</head><body><div class="app-shell">';
}

function layout_sidebar(array $user, string $activePage): void {
    // Note: Kept function name as layout_sidebar to avoid changing every page file, 
    // but this now renders the top navbar.
    $isTsg     = $user['role'] === 'TSG Personnel';
    
    // Fallback initials
    $initials  = strtoupper(substr($user['firstName'],0,1) . substr($user['lastName'],0,1));

    if ($isTsg) {
        $links = [
            ['href' => '/tsg/dashboard.php',    'key' => 'dashboard', 'label' => 'MAIN PAGE'],
            ['href' => '/tsg/all_reports.php',  'key' => 'reports',   'label' => 'REPORT'], // Or whatever TSG uses, keeping it similar
        ];
        $profileHref = '/tsg/profile.php';
        $rolePill = 'tsgStatus.png';
    } else {
        $links = [
            ['href' => '/reporter/dashboard.php',    'key' => 'dashboard', 'label' => 'MAIN PAGE'],
            ['href' => '/reporter/report_defect.php','key' => 'report',    'label' => 'REPORT'],
            ['href' => '/reporter/logs.php',         'key' => 'logs',      'label' => 'LOGS'],
        ];
        $profileHref = '/reporter/profile.php';
        $rolePill = 'StudentStatus.png';
    }

    $docRoot = rtrim(str_replace('\\','/',$_SERVER['DOCUMENT_ROOT']), '/');
    $appRoot = rtrim(str_replace('\\','/',realpath(__DIR__ . '/..')), '/');
    $base    = '/' . ltrim(str_replace($docRoot, '', $appRoot), '/');

    echo '<header class="top-nav">';
    
    // Left side: Logos
    echo '<div class="nav-logos">';
    echo '<img src="' . $base . '/assets/images/cit-logo.png" alt="CIT" class="nav-cit-logo">';
    echo '<img src="' . $base . '/assets/images/techops_logo.png" alt="Tech-Ops" class="nav-techops-logo">';
    echo '</div>';

    // Center: Nav links
    echo '<nav class="nav-links">';
    foreach ($links as $l) {
        $href   = $base . $l['href'];
        $active = ($l['key'] === $activePage) ? ' active' : '';
        echo '<a href="' . $href . '" class="nav-link' . $active . '">' . htmlspecialchars($l['label']) . '</a>';
    }
    echo '</nav>';

    // Right side: Profile info
    echo '<a href="' . $base . $profileHref . '" class="nav-profile">';
    echo '<img src="' . $base . '/assets/images/' . $rolePill . '" alt="Role" class="nav-role-pill">';
    echo '<div class="nav-avatar">' . $initials . '</div>';
    echo '</a>';

    echo '</header>';
    echo '<main class="main-content">';
}

function layout_foot(): void {
    echo '</main></div></body></html>';
}
