<?php
/* ==================================================================
   WorkSync – felles topp for alle innloggede sider

   Slik bruker du den: sett $pageTitle og $activePage øverst i siden,
   og inkluder denne filen. Eksempel:

       <?php
       $pageTitle  = "Employees";
       $activePage = "employees";
       require __DIR__ . '/header.php';
       ?>

   Filen tar seg av: session, tilgangskontroll, <head>, sidemenyen
   og topplinjen. Sidens eget innhold skrives etter require-linjen.
   ================================================================== */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* --- TILGANGSKONTROLL -----------------------------------------------
   Sett til false for å teste sidene uten innlogging mens databasen
   ikke er satt opp ennå. HUSK å sette den til true igjen før andre
   får bruke systemet. */
$krevInnlogging = true;

if ($krevInnlogging && empty($_SESSION['logged_in'])) {
    header("Location: log_in.php");
    exit();
}

$role     = $_SESSION['role']     ?? 'employee';
$userId   = $_SESSION['userId']   ?? 0;
$userName = $_SESSION['userName'] ?? 'Unknown user';

if (isset($allowedRoles) && !in_array($role, $allowedRoles, true)){
    http_response_code(403);
    exit('You do not have access to this page');
}

/* Pen visning av rolle i toppmenyen */
$roleLabels = [
    'admin'    => 'Administrator',
    'manager'  => 'Manager',
    'employee' => 'Employee',
];
$roleLabel = $roleLabels[$role] ?? 'Employee';

/**
 * Kall denne øverst på sider som bare enkelte roller skal se,
 * f.eks. requireRole(['admin', 'manager']); i manager_dashboard.php.
 * (Menyen skjuler bare lenken – selve siden må også beskyttes.)
 */
function requireRole(array $allowed)
{
    global $role;
    if (!in_array($role, $allowed, true)) {
        http_response_code(403);
        exit('Du har ikke tilgang til denne siden.');
    }
}

/* --- DATABASETILKOBLING ---------------------------------------------
   config.php skal sette opp $conn (mysqli). Hvis filen mangler, kjører
   sidene videre med tomme tall slik at designet fortsatt kan vises. */
$conn = null;
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

/**
 * Kjører en COUNT-spørring og returnerer tallet.
 * Returnerer null hvis databasen ikke er tilgjengelig eller spørringen feiler.
 */
function countRows($conn, $sql, $types = '', $params = [])
{
    if (!$conn) {
        return null;
    }
    try {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return null;
        }
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_row() : null;
        $stmt->close();
        return $row ? (int) $row[0] : 0;
    } catch (mysqli_sql_exception $e) {
        error_log('countRows: ' . $e->getMessage());
        return null;
    }
}

/**
 * Henter flere rader som assoc-array. Tom liste hvis databasen mangler
 * eller spørringen feiler.
 */
function fetchRows($conn, $sql, $types = '', $params = [])
{
    if (!$conn) {
        return [];
    }
    try {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res  = $stmt->get_result();
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $rows;
    } catch (mysqli_sql_exception $e) {
        error_log('fetchRows: ' . $e->getMessage());
        return [];
    }
}

/** Viser et tall, eller en strek hvis databasen ikke svarte. */
function num($value)
{
    return $value === null ? '–' : number_format($value);
}

/** Oversetter en tilgjengelighetstekst til en fargekode. */
function statusClass($status)
{
    $s = strtolower(trim((string) $status));

    /* "unavailable" inneholder "available", så den må sjekkes først */
    if (str_contains($s, 'unavailable') || str_contains($s, 'utilgjengelig')) return 'off';

    if (str_contains($s, 'available') || str_contains($s, 'tilgjengelig')) return 'ok';
    if (str_contains($s, 'busy')      || str_contains($s, 'disturb'))      return 'busy';
    if (str_contains($s, 'away')      || str_contains($s, 'borte'))        return 'away';
    return 'off';
}

/** Er fristen passert? */
function erForfalt($frist)
{
    return !empty($frist) && strtotime($frist) < strtotime('today');
}

/* Standardverdier hvis siden glemmer å sette dem */
$pageTitle  = $pageTitle  ?? 'WorkSync';
$activePage = $activePage ?? '';

/* Menyvalgene. 'roles' styrer hvem som ser lenken. */
$menu = [
    ['key' => 'dashboard',   'label' => 'Dashboard',   'url' => 'dashboard.php'],
    ['key' => 'office',      'label' => 'Offices',     'url' => 'office.php'],
    ['key' => 'employees',   'label' => 'Employees',   'url' => 'employees.php'],
    ['key' => 'equipment',   'label' => 'Equipment',   'url' => 'equipment.php'],
    ['key' => 'reports',     'label' => 'Reports',     'url' => 'reports.php'],
    ['key' => 'settings',    'label' => 'Settings',    'url' => 'settings.php'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>WorkSync – <?= htmlspecialchars($pageTitle) ?></title>
<link rel="stylesheet" href="style.css">
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-logo">
        <img src="WorkSyncLogoNavy.png" alt="WorkSync Logo">
    </div>

    <?php foreach ($menu as $item): ?>
        <?php
        /* Hopper over lenker rollen ikke har tilgang til */
        if (isset($item['roles']) && !in_array($role, $item['roles'], true)) {
            continue;
        }
        $isActive = ($activePage === $item['key']) ? ' class="active"' : '';
        ?>
        <a href="<?= htmlspecialchars($item['url']) ?>"<?= $isActive ?>><?= htmlspecialchars($item['label']) ?></a>
    <?php endforeach; ?>

    <a href="log_in.php?logout=1" class="logout">Log out</a>

    <footer class="sidebar-footer">
        &copy; 2026 WorkSync.<br>All rights reserved.
    </footer>
</div>

<!-- TOP HEADER -->
<div class="top-header">
    <div class="search-box">
        <form action="search.php" method="get">
            <input type="text" name="q" placeholder="Search employees, offices, equipment...">
        </form>
    </div>

    <div class="user-info">
        <div class="user-status"></div>
        <span><?= htmlspecialchars($userName) ?></span>
        <span class="role-tag"><?= htmlspecialchars($roleLabel) ?></span>
    </div>
</div>