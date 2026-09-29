<?php
session_start();

/* ------------------------------------------------------------------
   WorkSync – Dashboard
   Rollebasert dashboard (admin / manager / employee) jf. SRD kap. 2.1
   ------------------------------------------------------------------ */

/* --- 1. TILGANGSKONTROLL --------------------------------------------
   Sender ikke-innloggede brukere tilbake til innloggingssiden.
   Kommenter ut blokken under hvis du vil teste siden uten innlogging. 
if (empty($_SESSION['logged_in'])) {
    header("Location: log_in.php");
    exit();
}*/

$role     = $_SESSION['role']     ?? 'employee';
$userId   = $_SESSION['userId']   ?? 0;
$userName = $_SESSION['userName'] ?? 'Unknown user';

/* Pen visning av rolle i toppmenyen */
$roleLabels = [
    'admin'    => 'Administrator',
    'manager'  => 'Manager',
    'employee' => 'Employee',
];
$roleLabel = $roleLabels[$role] ?? 'Employee';

/* --- 2. DATABASETILKOBLING ------------------------------------------
   config.php skal sette opp $conn (mysqli). Hvis den mangler, kjører
   siden videre med tomme tall slik at designet fortsatt kan vises. */
$conn = null;
if (file_exists(__DIR__ . '/config.php')) {
    require __DIR__ . '/config.php';
}

/**
 * Kjører en COUNT-spørring og returnerer tallet.
 * Returnerer null hvis databasen ikke er tilgjengelig.
 */
function countRows($conn, $sql, $types = '', $params = [])
{
    if (!$conn) {
        return null;
    }
    $stmt = @$conn->prepare($sql);
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
}

/**
 * Henter flere rader som assoc-array. Tom liste hvis databasen mangler.
 */
function fetchRows($conn, $sql, $types = '', $params = [])
{
    if (!$conn) {
        return [];
    }
    $stmt = @$conn->prepare($sql);
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
}

/** Viser et tall, eller en strek hvis databasen ikke svarte. */
function num($value)
{
    return $value === null ? '–' : number_format($value);
}

/* --- 3. NØKKELTALL ---------------------------------------------------
   SRD kap. 2.1: "Dashboardet viser overordnet informasjon som hvor
   mange møterom det er, antall ansatte, oppgaver som må utføres eller
   sist registrerte hendelser." */

$antallAnsatte  = countRows($conn, "SELECT COUNT(*) FROM EMPLOYEE");
$antallKontorer = countRows($conn, "SELECT COUNT(*) FROM OFFICE");
$antallMoterom  = countRows($conn, "SELECT COUNT(*) FROM MEETINGROOM");
$ledigeMoterom  = countRows($conn, "SELECT COUNT(*) FROM MEETINGROOM WHERE Tilgjengelighet = 'Ledig'");

if ($role === 'employee') {
    /* Ansatt ser bare sine egne oppgaver og avvik */
    $apneOppgaver = countRows($conn, "SELECT COUNT(*) FROM TASKS WHERE AnsattId = ?", 'i', [$userId]);
    $forfalt      = countRows($conn, "SELECT COUNT(*) FROM TASKS WHERE AnsattId = ? AND Frist < CURDATE()", 'i', [$userId]);
    $antallAvvik  = countRows($conn, "SELECT COUNT(*) FROM DEVIATION WHERE AnsattId = ?", 'i', [$userId]);
} else {
    /* Manager og admin ser hele bedriften */
    $apneOppgaver = countRows($conn, "SELECT COUNT(*) FROM TASKS");
    $forfalt      = countRows($conn, "SELECT COUNT(*) FROM TASKS WHERE Frist < CURDATE()");
    $antallAvvik  = countRows($conn, "SELECT COUNT(*) FROM DEVIATION");
}

/* Belegg på møterom i prosent – brukes i fremdriftsindikatoren */
$belegg = 0;
if ($antallMoterom) {
    $belegg = (int) round((($antallMoterom - (int) $ledigeMoterom) / $antallMoterom) * 100);
}

/* --- 4. LISTER -------------------------------------------------------- */

/* Oppgaver med nærmeste frist */
if ($role === 'employee') {
    $oppgaver = fetchRows(
        $conn,
        "SELECT t.OppgaveId, t.Beskrivelse, t.Frist, e.AnsattEpost
           FROM TASKS t
           LEFT JOIN EMPLOYEE e ON e.AnsattId = t.AnsattId
          WHERE t.AnsattId = ?
          ORDER BY t.Frist ASC
          LIMIT 6",
        'i',
        [$userId]
    );
} else {
    $oppgaver = fetchRows(
        $conn,
        "SELECT t.OppgaveId, t.Beskrivelse, t.Frist, e.AnsattEpost
           FROM TASKS t
           LEFT JOIN EMPLOYEE e ON e.AnsattId = t.AnsattId
          ORDER BY t.Frist ASC
          LIMIT 6"
    );
}

/* Sist registrerte avvik */
$avvik = fetchRows(
    $conn,
    "SELECT d.OppgaveId, d.Beskrivelse, d.Frist, e.AnsattEpost
       FROM DEVIATION d
       LEFT JOIN EMPLOYEE e ON e.AnsattId = d.AnsattId
      ORDER BY d.OppgaveId DESC
      LIMIT 5"
);

/* Ansatte og tilgjengelighetsstatus (SRD: tilgjengelig, opptatt, borte ...) */
$ansatte = fetchRows(
    $conn,
    "SELECT AnsattId, AnsattEpost, Tilgjengelighet
       FROM EMPLOYEE
      ORDER BY AnsattId DESC
      LIMIT 6"
);

/** Oversetter en tilgjengelighetstekst til en fargekode. */
function statusClass($status)
{
    $s = strtolower(trim((string) $status));
    if (str_contains($s, 'tilgjengelig') || str_contains($s, 'available')) return 'ok';
    if (str_contains($s, 'opptatt')      || str_contains($s, 'forstyrr'))  return 'busy';
    if (str_contains($s, 'borte')        || str_contains($s, 'away'))      return 'away';
    return 'off';
}

/** Er fristen passert? */
function erForfalt($frist)
{
    return $frist && strtotime($frist) < strtotime('today');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>WorkSync Dashboard</title>

<style>

/* --- GLOBAL --- */
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background-color: #f7f7f7;
    color: #22303f;
}

/* --- SIDEBAR --- */
.sidebar {
    width: 220px;
    height: 100vh;
    background-color: #1e3a5f;
    color: white;
    position: fixed;
    left: 0;
    top: 0;
    padding: 20px;
    /* Flexbox for å dytte footeren nederst */
    display: flex;
    flex-direction: column;
}

.sidebar-logo {
    width: 100%;
    text-align: center;
    margin-bottom: 30px;
}

.sidebar-logo img {
    width: 140px;
    height: auto;
}

.sidebar a {
    display: block;
    color: white;
    text-decoration: none;
    margin: 10px 0;
    font-size: 18px;
    padding: 10px;
    border-radius: 6px;
}

.sidebar a:hover {
    background-color: #24456f;
}

.sidebar a.active {
    background-color: #294a75;
}

.sidebar .logout {
    font-size: 15px;
    color: #cdd9e5;
}

/* Plasserer footeren helt i bunn av menyen */
.sidebar-footer {
    margin-top: auto;
    text-align: center;
    font-size: 12px;
    color: #a0b2c6;
    padding-top: 15px;
    border-top: 1px solid #294a75;
}

/* --- TOP HEADER --- */
.top-header {
    margin-left: 220px;
    height: 70px;
    background-color: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 25px;
    border-bottom: 1px solid #ddd;
}

.search-box input {
    padding: 8px 12px;
    width: 250px;
    border: 1px solid #ccc;
    border-radius: 6px;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.user-status {
    width: 12px;
    height: 12px;
    background-color: #4CAF50;
    border-radius: 50%;
}

.role-tag {
    background-color: #eaf0f7;
    color: #1e3a5f;
    font-size: 12px;
    font-weight: bold;
    padding: 4px 10px;
    border-radius: 20px;
}

/* --- DASHBOARD --- */
.dashboard {
    margin-left: 220px;
    padding: 20px 25px 40px;
}

.page-title {
    margin: 10px 0 4px;
    font-size: 24px;
}

.page-subtitle {
    margin: 0 0 20px;
    color: #6b7c8f;
    font-size: 14px;
}

/* Nøkkeltall-kort */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 25px;
}

.info-box {
    background-color: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    border-left: 5px solid #1e3a5f;
}

.info-box.green  { border-left-color: #4CAF50; }
.info-box.yellow { border-left-color: #e0a800; }
.info-box.red    { border-left-color: #d9534f; }

.info-box .label {
    font-size: 13px;
    color: #6b7c8f;
    text-transform: uppercase;
    letter-spacing: .5px;
}

.info-box .value {
    font-size: 34px;
    font-weight: bold;
    margin: 8px 0 2px;
}

.info-box .note {
    font-size: 12px;
    color: #8a99aa;
}

/* To kolonner med lister */
.panel-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
}

.panel {
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    padding: 20px;
    margin-bottom: 20px;
}

.panel h3 {
    margin: 0 0 15px;
    font-size: 17px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.panel h3 a {
    font-size: 13px;
    font-weight: normal;
    color: #1e3a5f;
    text-decoration: none;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

th {
    text-align: left;
    color: #6b7c8f;
    font-size: 12px;
    text-transform: uppercase;
    padding-bottom: 8px;
    border-bottom: 1px solid #e6e6e6;
}

td {
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
}

tr:last-child td {
    border-bottom: none;
}

.badge {
    font-size: 12px;
    padding: 3px 9px;
    border-radius: 20px;
    background-color: #eef1f4;
    color: #55636f;
}

.badge.ok    { background-color: #e5f5e8; color: #2e7d32; }
.badge.busy  { background-color: #fdeaea; color: #c62828; }
.badge.away  { background-color: #fff5e0; color: #a76f00; }
.badge.late  { background-color: #fdeaea; color: #c62828; }

/* Enkel fremdriftsindikator for møteromsbelegg */
.progress {
    background-color: #eef1f4;
    border-radius: 20px;
    height: 12px;
    overflow: hidden;
    margin: 12px 0 6px;
}

.progress span {
    display: block;
    height: 100%;
    background-color: #1e3a5f;
}

.quick-links a {
    display: block;
    padding: 11px 14px;
    margin-bottom: 8px;
    background-color: #f2f5f8;
    border-radius: 6px;
    color: #1e3a5f;
    text-decoration: none;
    font-size: 14px;
}

.quick-links a:hover {
    background-color: #e4ebf2;
}

.empty {
    color: #8a99aa;
    font-size: 14px;
    padding: 10px 0;
}

/* Mindre skjermer: kortene legger seg under hverandre */
@media (max-width: 1100px) {
    .kpi-grid   { grid-template-columns: repeat(2, 1fr); }
    .panel-grid { grid-template-columns: 1fr; }
}

</style>
</head>

<body>

<div class="sidebar">
    <div class="sidebar-logo">
        <img src="WorkSync.png" alt="WorkSync Logo">
    </div>

    <!-- PÅ DASHBOARD-SIDEN ER DASHBOARD ACTIVE -->
    <a href="dashboard.php" class="active">Dashboard</a>
    <a href="office.php">Offices</a>
    <a href="employees.php">Employees</a>
    <a href="equipment.php">Equipment</a>
    <a href="reports.php">Reports</a>
    <?php if ($role !== 'employee'): ?>
        <!-- Kun manager/admin har vedlikeholdssystemet, jf. SRD kap. 2.1 -->
        <a href="manager_dashboard.php">Maintenance</a>
    <?php endif; ?>
    <a href="settings.php">Settings</a>
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

<div class="dashboard">

    <h1 class="page-title">Hello, <?= htmlspecialchars($userName) ?></h1>
    <p class="page-subtitle">
        Overview for <?= htmlspecialchars(strtolower($roleLabel)) ?> &middot;
        <?= date('j M Y') ?>
    </p>

    <!-- NØKKELTALL -->
    <div class="kpi-grid">

        <div class="info-box">
            <div class="label">Employees</div>
            <div class="value"><?= num($antallAnsatte) ?></div>
            <div class="note">Registered in the system</div>
        </div>

        <div class="info-box green">
            <div class="label">Offices</div>
            <div class="value"><?= num($antallKontorer) ?></div>
            <div class="note"><?= num($ledigeMoterom) ?> meeting rooms available</div>
        </div>

        <div class="info-box yellow">
            <div class="label"><?= $role === 'employee' ? 'My tasks' : 'Open tasks' ?></div>
            <div class="value"><?= num($apneOppgaver) ?></div>
            <div class="note"><?= num($forfalt) ?> overdue</div>
        </div>

        <div class="info-box red">
            <div class="label">Deviations</div>
            <div class="value"><?= num($antallAvvik) ?></div>
            <div class="note">Reported issues</div>
        </div>

    </div>

    <div class="panel-grid">

        <!-- VENSTRE KOLONNE -->
        <div>

            <div class="panel">
                <h3>
                    <?= $role === 'employee' ? 'My tasks' : 'Upcoming deadlines' ?>
                    <a href="reports.php">View all</a>
                </h3>

                <?php if ($oppgaver): ?>
                    <table>
                        <tr>
                            <th>Description</th>
                            <th>Assigned to</th>
                            <th>Due date</th>
                        </tr>
                        <?php foreach ($oppgaver as $o): ?>
                            <tr>
                                <td><?= htmlspecialchars($o['Beskrivelse'] ?? '') ?></td>
                                <td><?= htmlspecialchars($o['AnsattEpost'] ?? 'Unassigned') ?></td>
                                <td>
                                    <?php if (erForfalt($o['Frist'])): ?>
                                        <span class="badge late">
                                            <?= htmlspecialchars(date('j M Y', strtotime($o['Frist']))) ?>
                                        </span>
                                    <?php elseif ($o['Frist']): ?>
                                        <span class="badge">
                                            <?= htmlspecialchars(date('j M Y', strtotime($o['Frist']))) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge">No due date</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <p class="empty">No tasks registered.</p>
                <?php endif; ?>
            </div>

            <div class="panel">
                <h3>
                    Latest deviations
                    <a href="reports.php">View all</a>
                </h3>

                <?php if ($avvik): ?>
                    <table>
                        <tr>
                            <th>Description</th>
                            <th>Reported by</th>
                            <th>Due date</th>
                        </tr>
                        <?php foreach ($avvik as $a): ?>
                            <tr>
                                <td><?= htmlspecialchars($a['Beskrivelse'] ?? '') ?></td>
                                <td><?= htmlspecialchars($a['AnsattEpost'] ?? 'Unknown') ?></td>
                                <td>
                                    <?= $a['Frist']
                                        ? htmlspecialchars(date('j M Y', strtotime($a['Frist'])))
                                        : '–' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <p class="empty">No deviations reported.</p>
                <?php endif; ?>
            </div>

        </div>

        <!-- HØYRE KOLONNE -->
        <div>

            <div class="panel">
                <h3>Meeting room occupancy</h3>
                <div class="progress">
                    <span style="width: <?= (int) $belegg ?>%"></span>
                </div>
                <p class="note" style="font-size:13px;color:#6b7c8f;margin:0;">
                    <?= (int) $belegg ?>% occupied &middot;
                    <?= num($ledigeMoterom) ?> of <?= num($antallMoterom) ?> available
                </p>
            </div>

            <div class="panel">
                <h3>
                    Employees
                    <a href="employees.php">View all</a>
                </h3>

                <?php if ($ansatte): ?>
                    <table>
                        <?php foreach ($ansatte as $e): ?>
                            <tr>
                                <td><?= htmlspecialchars($e['AnsattEpost'] ?? '') ?></td>
                                <td style="text-align:right;">
                                    <span class="badge <?= statusClass($e['Tilgjengelighet']) ?>">
                                        <?= htmlspecialchars($e['Tilgjengelighet'] ?: 'Unknown') ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <p class="empty">No employees registered.</p>
                <?php endif; ?>
            </div>

            <div class="panel quick-links">
                <h3>Shortcuts</h3>
                <?php if ($role !== 'employee'): ?>
                    <a href="employees.php?action=new">+ Add employee</a>
                    <a href="equipment.php?action=new">+ Register equipment</a>
                    <a href="office.php?action=new">+ Create office</a>
                    <a href="manager_dashboard.php">Delegate task</a>
                <?php else: ?>
                    <a href="office.php">Book meeting room</a>
                    <a href="equipment.php">Reserve equipment</a>
                    <a href="reports.php?action=new">Report deviation</a>
                    <a href="settings.php">Change availability</a>
                <?php endif; ?>
            </div>

        </div>

    </div>

</div>

<script>
    // Markerer menyvalget brukeren trykker på
    const links = document.querySelectorAll('.sidebar a');

    links.forEach(link => {
        link.addEventListener('click', () => {
            links.forEach(l => l.classList.remove('active'));
            link.classList.add('active');
        });
    });
</script>

</body>
</html>
