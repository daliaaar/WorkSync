<?php
$pageTitle  = "Search";
$activePage = "";
require __DIR__ . '/header.php';

$q = trim($_GET['q'] ?? '');
$employees = $managers = $offices = $equipment = [];

if ($q !== '') {
    $like = '%' . $q . '%';

    $employees = fetchRows($conn,
        "SELECT AnsattId, AnsattEpost, Stilling, Avdeling, KontorId FROM EMPLOYEE
         WHERE AnsattEpost LIKE ? OR Stilling LIKE ? OR Avdeling LIKE ?",
        'sss', [$like, $like, $like]);

    $managerSql = "SELECT Navn, Epost, Bedriftsnavn FROM COMPANYMANAGER";
    if (in_array(strtolower($q), ['manager', 'managers'], true)) {
        $managers = fetchRows($conn, $managerSql);
    } else {
        $managers = fetchRows($conn,
            $managerSql . " WHERE Navn LIKE ? OR Epost LIKE ? OR Bedriftsnavn LIKE ?",
            'sss', [$like, $like, $like]);
    }

    $offices = fetchRows($conn,
        "SELECT KontorId FROM OFFICE WHERE KontorId LIKE ?",
        's', [$like]);

    $equipment = fetchRows($conn,
        "SELECT e.UtstyrId, e.Navn, e.Type, e.Status, a.AnsattEpost, a.KontorId
         FROM EQUIPMENT e LEFT JOIN EMPLOYEE a ON e.AnsattId = a.AnsattId
         WHERE e.Navn LIKE ? OR e.Type LIKE ? OR a.AnsattEpost LIKE ? OR a.KontorId LIKE ?",
        'ssss', [$like, $like, $like, $like]);
}
?>

<style>
.search-results { margin-left: 275px; padding: 0 30px 30px; }
</style>

<div class="search-results">
    <h2>Search results for "<?= htmlspecialchars($q) ?>"</h2>

    <?php if ($employees): ?>
        <h3>Employees</h3>
        <?php foreach ($employees as $r): ?>
            <p><?= htmlspecialchars($r['AnsattEpost']) ?> – <?= htmlspecialchars($r['Stilling'] ?? '') ?>, <?= htmlspecialchars($r['Avdeling'] ?? '') ?> (office <?= htmlspecialchars($r['KontorId'] ?? '–') ?>)</p>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($managers): ?>
        <h3>Managers</h3>
        <?php foreach ($managers as $r): ?>
            <p><?= htmlspecialchars($r['Navn']) ?> – <?= htmlspecialchars($r['Bedriftsnavn'] ?? '') ?> (<?= htmlspecialchars($r['Epost'] ?? '') ?>)</p>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($offices): ?>
        <h3>Offices</h3>
        <?php foreach ($offices as $r): ?>
            <p>Office <?= htmlspecialchars($r['KontorId']) ?></p>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($equipment): ?>
        <h3>Equipment</h3>
        <?php foreach ($equipment as $r): ?>
            <p><?= htmlspecialchars($r['Navn']) ?> (<?= htmlspecialchars($r['Type'] ?? '') ?>, <?= htmlspecialchars($r['Status']) ?>) – <?= htmlspecialchars($r['AnsattEpost'] ?? 'unassigned') ?>, office <?= htmlspecialchars($r['KontorId'] ?? '–') ?></p>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($q !== '' && !$employees && !$managers && !$offices && !$equipment): ?>
        <p>No results</p>
    <?php endif; ?>
</div>