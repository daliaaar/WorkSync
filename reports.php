<?php
/* WorkSync – Reports
   Viser oppgaver og innmeldte avvik. Ansatte ser bare sine egne. */

ob_start();

$pageTitle  = "Reports";
$activePage = "reports";
require __DIR__ . '/header.php';

function visDato($dato)
{
    return $dato ? date('j M Y', strtotime($dato)) : '–';
}

function gyldigDato($dato)
{
    if ($dato === '') {
        return true;
    }
    $d = DateTime::createFromFormat('Y-m-d', $dato);
    return $d && $d->format('Y-m-d') === $dato;
}

function gammelVerdi($skjemaType, $verdi)
{
    global $formType;
    return $formType === $skjemaType ? htmlspecialchars($verdi) : '';
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$isEmployee  = ($role === 'employee');
$canDelegate = in_array($role, ['admin', 'manager'], true);
$saved       = $_GET['saved'] ?? '';

$showForm = (($_GET['action'] ?? '') === 'new');
$editTask = $canDelegate ? (int) ($_GET['edit_task'] ?? 0) : 0;
$editDev  = (int) ($_GET['edit_dev'] ?? 0);
if ($editTask > 0) {
    $editDev = 0;
}
if ($editTask > 0 || $editDev > 0) {
    $showForm = true;
}

$error       = "";
$formType    = "";
$beskrivelse = "";
$frist       = "";
$valgtAnsatt = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $formType    = $_POST['type'] ?? '';
    $handling    = $_POST['handling'] ?? 'save';
    $id          = (int) ($_POST['id'] ?? 0);
    $beskrivelse = trim($_POST['beskrivelse'] ?? '');
    $frist       = trim($_POST['frist'] ?? '');
    $valgtAnsatt = $_POST['ansatt'] ?? '';
    $mine        = (int) $userId;

    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $error = "Invalid form submission. Please try again.";
    } elseif (!in_array($formType, ['task', 'deviation'], true)) {
        $error = "Unknown report type";
    } elseif ($formType === 'task' && !$canDelegate) {
        $error = "You do not have permission to manage tasks";
    } elseif (!$conn) {
        $error = "Database connection is not available";
    } elseif ($handling === 'delete') {
        try {
            if ($formType === 'task') {
                $stmt = $conn->prepare("DELETE FROM TASKS WHERE OppgaveId = ?");
                $stmt->bind_param("i", $id);
            } elseif ($isEmployee) {
                $stmt = $conn->prepare("DELETE FROM DEVIATION WHERE OppgaveId = ? AND AnsattId = ?");
                $stmt->bind_param("ii", $id, $mine);
            } else {
                $stmt = $conn->prepare("DELETE FROM DEVIATION WHERE OppgaveId = ?");
                $stmt->bind_param("i", $id);
            }
            $stmt->execute();
            $stmt->close();

            header("Location: reports.php?saved=deleted");
            exit();
        } catch (mysqli_sql_exception $e) {
            error_log('Reports delete: ' . $e->getMessage());
            $error = "Could not delete. Please try again later.";
        }
    } else {
        $showForm = true;
        if ($id > 0) {
            if ($formType === 'task') {
                $editTask = $id;
            } else {
                $editDev = $id;
            }
        }

        if ($beskrivelse === '') {
            $error = "Description is required";
        } elseif (strlen($beskrivelse) > 255) {
            $error = "Description can be at most 255 characters";
        } elseif (!gyldigDato($frist)) {
            $error = "Invalid due date";
        } else {
            $fristDb    = $frist !== '' ? $frist : null;
            $bedriftsId = ($role === 'manager') ? $mine : null;
            $ansattTask = ctype_digit((string) $valgtAnsatt) ? (int) $valgtAnsatt : null;
            $ansattDev  = $isEmployee ? $mine : null;

            try {
                if ($id > 0 && $formType === 'task') {
                    $stmt = $conn->prepare("UPDATE TASKS SET Beskrivelse = ?, Frist = ?, AnsattId = ? WHERE OppgaveId = ?");
                    $stmt->bind_param("ssii", $beskrivelse, $fristDb, $ansattTask, $id);
                } elseif ($id > 0 && $isEmployee) {
                    $stmt = $conn->prepare("UPDATE DEVIATION SET Beskrivelse = ?, Frist = ? WHERE OppgaveId = ? AND AnsattId = ?");
                    $stmt->bind_param("ssii", $beskrivelse, $fristDb, $id, $mine);
                } elseif ($id > 0) {
                    $stmt = $conn->prepare("UPDATE DEVIATION SET Beskrivelse = ?, Frist = ? WHERE OppgaveId = ?");
                    $stmt->bind_param("ssi", $beskrivelse, $fristDb, $id);
                } elseif ($formType === 'task') {
                    $stmt = $conn->prepare("INSERT INTO TASKS (Beskrivelse, Frist, BedriftsId, AnsattId) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("ssii", $beskrivelse, $fristDb, $bedriftsId, $ansattTask);
                } else {
                    $stmt = $conn->prepare("INSERT INTO DEVIATION (Beskrivelse, Frist, BedriftsId, AnsattId) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("ssii", $beskrivelse, $fristDb, $bedriftsId, $ansattDev);
                }
                $stmt->execute();
                $stmt->close();

                header("Location: reports.php?saved=" . $formType . ($id > 0 ? '_updated' : ''));
                exit();
            } catch (mysqli_sql_exception $e) {
                error_log('Reports save: ' . $e->getMessage());
                $error = "Could not save. Please try again later.";
            }
        }
    }

} elseif ($editTask > 0 || $editDev > 0) {

    if ($editTask > 0) {
        $formType = 'task';
        $rad = fetchRows($conn, "SELECT Beskrivelse, Frist, AnsattId FROM TASKS WHERE OppgaveId = ?", 'i', [$editTask]);
    } else {
        $formType = 'deviation';
        $rad = $isEmployee
            ? fetchRows($conn, "SELECT Beskrivelse, Frist, AnsattId FROM DEVIATION WHERE OppgaveId = ? AND AnsattId = ?", 'ii', [$editDev, $userId])
            : fetchRows($conn, "SELECT Beskrivelse, Frist, AnsattId FROM DEVIATION WHERE OppgaveId = ?", 'i', [$editDev]);
    }

    if ($rad) {
        $beskrivelse = $rad[0]['Beskrivelse'] ?? '';
        $frist       = $rad[0]['Frist'] ?? '';
        $valgtAnsatt = $rad[0]['AnsattId'] ?? '';
    } else {
        $error    = "Report not found";
        $showForm = false;
        $editTask = 0;
        $editDev  = 0;
        $formType = '';
    }
}

$filterOppgaver = '';
$filterAvvik    = '';
$types          = '';
$params         = [];

if ($isEmployee) {
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

$ansatte = [];
if ($showForm && $canDelegate) {
    $ansatte = fetchRows($conn, "SELECT AnsattId, AnsattEpost FROM EMPLOYEE ORDER BY AnsattEpost");
}

$meldinger = [
    'task'              => 'Task saved.',
    'deviation'         => 'Deviation reported.',
    'task_updated'      => 'Task updated.',
    'deviation_updated' => 'Deviation updated.',
    'deleted'           => 'Deleted.',
];
?>

<div class="dashboard">

    <h1 class="page-title">Reports</h1>
    <p class="page-subtitle">Tasks and reported deviations</p>

    <?php if (isset($meldinger[$saved])): ?>
        <div class="msg ok"><?= htmlspecialchars($meldinger[$saved]) ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="msg err"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($showForm && ($editDev > 0 || $editTask === 0)): ?>
        <div class="panel" id="deviation">
            <h3><?= $editDev > 0 ? 'Edit deviation' : 'Report deviation' ?></h3>
            <form method="POST"
                  action="reports.php?<?= $editDev > 0 ? 'edit_dev=' . $editDev : 'action=new' ?>"
                  class="report-form">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                <input type="hidden" name="type" value="deviation">
                <input type="hidden" name="handling" value="save">
                <input type="hidden" name="id" value="<?= $editDev ?>">

                <label for="dev_beskrivelse">Description</label>
                <input type="text" id="dev_beskrivelse" name="beskrivelse" maxlength="255" required
                       value="<?= gammelVerdi('deviation', $beskrivelse) ?>">

                <label for="dev_frist">Due date (optional)</label>
                <input type="text" class="datepicker" id="dev_frist" name="frist"
                       placeholder="Select date" autocomplete="off" title="Format: YYYY-MM-DD"
                       value="<?= gammelVerdi('deviation', $frist) ?>">

                <button type="submit"><?= $editDev > 0 ? 'Save changes' : 'Report deviation' ?></button>
                <a class="cancel" href="reports.php">Cancel</a>
            </form>
        </div>
    <?php endif; ?>

    <?php if ($showForm && $canDelegate && ($editTask > 0 || $editDev === 0)): ?>
        <div class="panel" id="task">
            <h3><?= $editTask > 0 ? 'Edit task' : 'Delegate task' ?></h3>
            <form method="POST"
                  action="reports.php?<?= $editTask > 0 ? 'edit_task=' . $editTask : 'action=new' ?>"
                  class="report-form">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                <input type="hidden" name="type" value="task">
                <input type="hidden" name="handling" value="save">
                <input type="hidden" name="id" value="<?= $editTask ?>">

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
                <input type="text" class="datepicker" id="task_frist" name="frist"
                       placeholder="Select date" autocomplete="off" title="Format: YYYY-MM-DD"
                       value="<?= gammelVerdi('task', $frist) ?>">

                <button type="submit"><?= $editTask > 0 ? 'Save changes' : 'Delegate task' ?></button>
                <a class="cancel" href="reports.php">Cancel</a>
            </form>
        </div>
    <?php endif; ?>

    <div class="panel">
        <h3>
            <?= $isEmployee ? 'My tasks' : 'All tasks' ?>
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
                    <?php if ($canDelegate): ?>
                        <th></th>
                    <?php endif; ?>
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
                        <?php if ($canDelegate): ?>
                            <td style="white-space:nowrap">
                                <a class="btn-small" href="reports.php?edit_task=<?= (int) $o['OppgaveId'] ?>">Edit</a>
                                <form method="POST" action="reports.php" class="inline-form"
                                      onsubmit="return confirm('Delete this task? This cannot be undone.');">
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                                    <input type="hidden" name="type" value="task">
                                    <input type="hidden" name="handling" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $o['OppgaveId'] ?>">
                                    <button type="submit" class="btn-small danger">Delete</button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p class="empty">No tasks registered.</p>
        <?php endif; ?>
    </div>

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
                    <th></th>
                </tr>
                <?php foreach ($avvik as $a): ?>
                    <tr>
                        <td><?= htmlspecialchars($a['Beskrivelse'] ?? '') ?></td>
                        <td><?= htmlspecialchars($a['AnsattEpost'] ?? 'Unknown') ?></td>
                        <td><?= htmlspecialchars(visDato($a['Frist'])) ?></td>
                        <td style="white-space:nowrap">
                            <a class="btn-small" href="reports.php?edit_dev=<?= (int) $a['OppgaveId'] ?>">Edit</a>
                            <form method="POST" action="reports.php" class="inline-form"
                                  onsubmit="return confirm('Delete this deviation? This cannot be undone.');">
                                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                                <input type="hidden" name="type" value="deviation">
                                <input type="hidden" name="handling" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $a['OppgaveId'] ?>">
                                <button type="submit" class="btn-small danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p class="empty">No deviations reported.</p>
        <?php endif; ?>
    </div>

</div>

<?php if ($showForm): ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.js"></script>
    <script>
        if (window.flatpickr) {
            flatpickr(".datepicker", {
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "j M Y",
                allowInput: false
            });
        }
    </script>
<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>