<?php
/* WorkSync – Reports
   Viser oppgaver og innmeldte avvik, og lar brukere legge til nye.
   - Alle innloggede kan melde avvik (DEVIATION).
   - Manager og admin kan i tillegg delegere oppgaver (TASKS).
   - Ansatte ser bare sine egne rader. */

ob_start(); 

$pageTitle  = "Reports";
$activePage = "reports";
require __DIR__ . '/header.php';

/** Formaterer en dato til f.eks. "5 Oct 2026", eller en strek hvis tom. */
function visDato($dato)
{
    return $dato ? date('j M Y', strtotime($dato)) : '–';
}

/** Verdi som skal fylles tilbake i skjemaet etter en feil (kun for riktig skjema). */
function gammelVerdi($skjemaType, $verdi)
{
    global $formType;
    return $formType === $skjemaType ? htmlspecialchars($verdi) : '';
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$canDelegate = in_array($role, ['admin', 'manager'], true);
$showForm    = (($_GET['action'] ?? '') === 'new');
$saved       = $_GET['saved'] ?? '';

$error       = "";
$formType    = "";
$beskrivelse = "";
$frist       = "";
$valgtAnsatt = "";

/* --- LAGRE NYTT AVVIK ELLER NY OPPGAVE ------------------------------ */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $showForm    = true;
    $formType    = $_POST['type'] ?? '';
    $beskrivelse = trim($_POST['beskrivelse'] ?? '');
    $frist       = trim($_POST['frist'] ?? '');
    $valgtAnsatt = $_POST['ansatt'] ?? '';

    /* Gyldig dato (YYYY-MM-DD) eller tom */
    $fristOk = true;
    if ($frist !== '') {
        $d = DateTime::createFromFormat('Y-m-d', $frist);
        $fristOk = $d && $d->format('Y-m-d') === $frist;
    }

    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $error = "Invalid form submission. Please try again.";
    } elseif (!in_array($formType, ['task', 'deviation'], true)) {
        $error = "Unknown report type";
    } elseif ($formType === 'task' && !$canDelegate) {
        $error = "You do not have permission to delegate tasks";
    } elseif ($beskrivelse === '') {
        $error = "Description is required";
    } elseif (strlen($beskrivelse) > 255) {
        $error = "Description can be at most 255 characters";
    } elseif (!$fristOk) {
        $error = "Invalid due date";
    } elseif (!$conn) {
        $error = "Database connection is not available";
    } else {
        $fristDb    = $frist !== '' ? $frist : null;
        $bedriftsId = ($role === 'manager') ? (int) $userId : null;

        if ($formType === 'deviation') {
            /* Ansatte melder på seg selv. Manager/admin har ingen AnsattId. */
            $tabell   = 'DEVIATION';
            $ansattId = ($role === 'employee') ? (int) $userId : null;
        } else {
            $tabell   = 'TASKS';
            $ansattId = ctype_digit((string) $valgtAnsatt) ? (int) $valgtAnsatt : null;
        }

        try {
            /* $tabell er satt ovenfor til en fast verdi, aldri fra brukeren */
            $stmt = $conn->prepare(
                "INSERT INTO $tabell (Beskrivelse, Frist, BedriftsId, AnsattId)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->bind_param("ssii", $beskrivelse, $fristDb, $bedriftsId, $ansattId);
            $stmt->execute();
            $stmt->close();

            header("Location: reports.php?saved=" . $formType);
            exit();

        } catch (mysqli_sql_exception $e) {
            error_log('Reports insert: ' . $e->getMessage());
            $error = "Could not save. Please try again later.";
        }
    }
}

/* Ansatte ser bare sine egne rader. Alle andre ser alt. */
$filterOppgaver = '';
$filterAvvik    = '';
$types          = '';
$params         = [];

if ($role === 'employee') {
    $filterOppgaver = 'WHERE t.AnsattId = ?';
    $filterAvvik    = 'WHERE d.AnsattId = ?';
    $types          = 'i';
    $params         = [$userId];
}

$oppgaver = fetchRows(
    $conn,
    "SELECT t.OppgaveId, t.Beskrivelse, t.Frist, e.AnsattEpost
       FROM TASKS t
       LEFT JOIN EMPLOYEE e ON e.AnsattId = t.AnsattId
       $filterOppgaver
      ORDER BY t.Frist ASC",
    $types,
    $params
);

$avvik = fetchRows(
    $conn,
    "SELECT d.OppgaveId, d.Beskrivelse, d.Frist, e.AnsattEpost
       FROM DEVIATION d
       LEFT JOIN EMPLOYEE e ON e.AnsattId = d.AnsattId
       $filterAvvik
      ORDER BY d.OppgaveId DESC",
    $types,
    $params
);

/* Ansatte som kan velges ved delegering */
$ansatte = [];
if ($showForm && $canDelegate) {
    $ansatte = fetchRows($conn, "SELECT AnsattId, AnsattEpost FROM EMPLOYEE ORDER BY AnsattEpost");
}
?>

<div class="dashboard">

    <h1 class="page-title">Reports</h1>
    <p class="page-subtitle">Tasks and reported deviations</p>

    <!-- MELDINGER -->
    <?php if ($saved === 'task'): ?>
        <div class="msg ok">Task saved.</div>
    <?php elseif ($saved === 'deviation'): ?>
        <div class="msg ok">Deviation reported.</div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="msg err"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- SKJEMAER -->
    <?php if ($showForm): ?>

        <div class="panel" id="deviation">
            <h3>Report deviation</h3>
            <form method="POST" action="reports.php?action=new" class="report-form">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                <input type="hidden" name="type" value="deviation">

                <label for="dev_beskrivelse">Description</label>
                <input type="text" id="dev_beskrivelse" name="beskrivelse" maxlength="255" required
                       value="<?= gammelVerdi('deviation', $beskrivelse) ?>">

                <label for="dev_frist">Due date (optional)</label>
                <input type="date" id="dev_frist" name="frist"
                       value="<?= gammelVerdi('deviation', $frist) ?>">

                <button type="submit">Report deviation</button>
                <a class="cancel" href="reports.php">Cancel</a>
            </form>
        </div>

        <?php if ($canDelegate): ?>
            <div class="panel" id="task">
                <h3>Delegate task</h3>
                <form method="POST" action="reports.php?action=new" class="report-form">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                    <input type="hidden" name="type" value="task">

                    <label for="task_beskrivelse">Description</label>
                    <input type="text" id="task_beskrivelse" name="beskrivelse" maxlength="255" required
                           value="<?= gammelVerdi('task', $beskrivelse) ?>">

                    <label for="task_ansatt">Assign to</label>
                    <select id="task_ansatt" name="ansatt">
                        <option value="">Unassigned</option>
                        <?php foreach ($ansatte as $a): ?>
                            <option value="<?= (int) $a['AnsattId'] ?>"
                                <?= ($formType === 'task' && (string) $a['AnsattId'] === (string) $valgtAnsatt) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($a['AnsattEpost']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="task_frist">Due date (optional)</label>
                    <input type="date" id="task_frist" name="frist"
                           value="<?= gammelVerdi('task', $frist) ?>">

                    <button type="submit">Delegate task</button>
                    <a class="cancel" href="reports.php">Cancel</a>
                </form>
            </div>
        <?php endif; ?>

    <?php endif; ?>

    <!-- OPPGAVER -->
    <div class="panel">
        <h3>
            <?= $role === 'employee' ? 'My tasks' : 'All tasks' ?>
            <?php if ($canDelegate): ?>
                <a href="reports.php?action=new#task">+ Delegate task</a>
            <?php endif; ?>
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
                            <span class="badge<?= erForfalt($o['Frist']) ? ' late' : '' ?>">
                                <?= $o['Frist'] ? htmlspecialchars(visDato($o['Frist'])) : 'No due date' ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p class="empty">No tasks registered.</p>
        <?php endif; ?>
    </div>

    <!-- AVVIK -->
    <div class="panel">
        <h3>
            Deviations
            <a href="reports.php?action=new#deviation">+ Report deviation</a>
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
                        <td><?= htmlspecialchars(visDato($a['Frist'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p class="empty">No deviations reported.</p>
        <?php endif; ?>
    </div>

</div>

<?php require __DIR__ . '/footer.php'; ?>