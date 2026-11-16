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

function statusKlasse($status)
{
    if ($status === 'Done') {
        return 'ok';
    }
    if ($status === 'In progress') {
        return 'busy';
    }
    return '';
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$statuser    = ['Open', 'In progress', 'Done'];
$isEmployee  = ($role === 'employee');
$canDelegate = in_array($role, ['admin', 'manager'], true);
$saved       = $_GET['saved'] ?? '';
$viewTask    = (int) ($_GET['view_task'] ?? 0);

$showForm = (($_GET['action'] ?? '') === 'new');
$editTask = $canDelegate ? (int) ($_GET['edit_task'] ?? 0) : 0;
$editDev  = $canDelegate ? (int) ($_GET['edit_dev'] ?? 0) : 0;
if ($editTask > 0) {
    $editDev = 0;
}
if ($editTask > 0 || $editDev > 0) {
    $showForm = true;
}

$error       = "";
$formType    = "";
$beskrivelse = "";
$detaljer    = "";
$frist       = "";
$valgtAnsatt = "";
$statusValgt = "Open";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $formType    = $_POST['type'] ?? '';
    $handling    = $_POST['handling'] ?? 'save';
    $id          = (int) ($_POST['id'] ?? 0);
    $beskrivelse = trim($_POST['beskrivelse'] ?? '');
    $detaljer    = trim($_POST['detaljer'] ?? '');
    $frist       = trim($_POST['frist'] ?? '');
    $valgtAnsatt = $_POST['ansatt'] ?? '';
    $statusValgt = $_POST['status'] ?? 'Open';
    $fra         = $_POST['from'] ?? '';
    $mine        = (int) $userId;

    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $error = "Invalid form submission. Please try again.";
    } elseif (!in_array($formType, ['task', 'deviation'], true)) {
        $error = "Unknown report type";
    } elseif ($minBedriftsId === 0) {
        $error = "No company found for your account";
    } elseif ($handling === 'status') {
        if ($formType !== 'task' || $id <= 0 || !in_array($statusValgt, $statuser, true)) {
            $error = "Invalid status update";
        } elseif (!$conn) {
            $error = "Database connection is not available";
        } else {
            try {
                if ($isEmployee) {
                    $stmt = $conn->prepare("UPDATE TASKS SET Status = ? WHERE OppgaveId = ? AND AnsattId = ? AND BedriftsId = ?");
                    $stmt->bind_param("siii", $statusValgt, $id, $mine, $minBedriftsId);
                } else {
                    $stmt = $conn->prepare("UPDATE TASKS SET Status = ? WHERE OppgaveId = ? AND BedriftsId = ?");
                    $stmt->bind_param("sii", $statusValgt, $id, $minBedriftsId);
                }
                $stmt->execute();
                $stmt->close();

                header("Location: reports.php?" . ($fra === 'view' ? 'view_task=' . $id . '&' : '') . "saved=status");
                exit();
            } catch (mysqli_sql_exception $e) {
                error_log('Reports status: ' . $e->getMessage());
                $error = "Could not update status. Please try again later.";
            }
        }
    } elseif ($formType === 'task' && !$canDelegate) {
        $error = "You do not have permission to manage tasks";
    } elseif (($handling === 'delete' || $id > 0) && !$canDelegate) {
        $error = "You do not have permission to edit or delete reports";
    } elseif (!$conn) {
        $error = "Database connection is not available";
    } elseif ($handling === 'delete') {
        try {
            $tabell = $formType === 'task' ? 'TASKS' : 'DEVIATION';
            $stmt = $conn->prepare("DELETE FROM $tabell WHERE OppgaveId = ? AND BedriftsId = ?");
            $stmt->bind_param("ii", $id, $minBedriftsId);
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
        } elseif (strlen($detaljer) > 2000) {
            $error = "Details can be at most 2000 characters";
        } elseif ($formType === 'task' && !in_array($statusValgt, $statuser, true)) {
            $error = "Invalid status";
        } elseif (!gyldigDato($frist)) {
            $error = "Invalid due date";
        } else {
            $fristDb    = $frist !== '' ? $frist : null;
            $detaljerDb = $detaljer !== '' ? $detaljer : null;
            /* Bedriften kommer alltid fra innloggingen */
            $bedriftsId = $minBedriftsId;
            $ansattTask = ctype_digit((string) $valgtAnsatt) ? (int) $valgtAnsatt : null;
            $ansattDev  = $isEmployee ? $mine : null;

            /* Valgt ansatt må tilhøre samme bedrift */
            if ($formType === 'task' && $ansattTask !== null
                && !fetchRows($conn, "SELECT 1 FROM EMPLOYEE WHERE AnsattId = ? AND BedriftsId = ?", 'ii', [$ansattTask, $minBedriftsId])) {
                $error = "Invalid employee";
            } else {
                try {
                    if ($id > 0 && $formType === 'task') {
                        $stmt = $conn->prepare("UPDATE TASKS SET Beskrivelse = ?, Detaljer = ?, Frist = ?, AnsattId = ?, Status = ? WHERE OppgaveId = ? AND BedriftsId = ?");
                        $stmt->bind_param("sssisii", $beskrivelse, $detaljerDb, $fristDb, $ansattTask, $statusValgt, $id, $minBedriftsId);
                    } elseif ($id > 0) {
                        $stmt = $conn->prepare("UPDATE DEVIATION SET Beskrivelse = ?, Frist = ? WHERE OppgaveId = ? AND BedriftsId = ?");
                        $stmt->bind_param("ssii", $beskrivelse, $fristDb, $id, $minBedriftsId);
                    } elseif ($formType === 'task') {
                        $stmt = $conn->prepare("INSERT INTO TASKS (Beskrivelse, Detaljer, Frist, BedriftsId, AnsattId, Status) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("sssiis", $beskrivelse, $detaljerDb, $fristDb, $bedriftsId, $ansattTask, $statusValgt);
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
    }

} elseif ($editTask > 0 || $editDev > 0) {

    if ($editTask > 0) {
        $formType = 'task';
        $rad = fetchRows($conn, "SELECT Beskrivelse, Detaljer, Frist, AnsattId, Status FROM TASKS WHERE OppgaveId = ? AND BedriftsId = ?", 'ii', [$editTask, $minBedriftsId]);
    } else {
        $formType = 'deviation';
        $rad = fetchRows($conn, "SELECT Beskrivelse, Frist, AnsattId FROM DEVIATION WHERE OppgaveId = ? AND BedriftsId = ?", 'ii', [$editDev, $minBedriftsId]);
    }

    if ($rad) {
        $beskrivelse = $rad[0]['Beskrivelse'] ?? '';
        $detaljer    = $rad[0]['Detaljer'] ?? '';
        $frist       = $rad[0]['Frist'] ?? '';
        $valgtAnsatt = $rad[0]['AnsattId'] ?? '';
        $statusValgt = $rad[0]['Status'] ?? 'Open';
    } else {
        $error    = "Report not found";
        $showForm = false;
        $editTask = 0;
        $editDev  = 0;
        $formType = '';
    }
}

$vis = [];
if ($viewTask > 0) {
    $visSql = "SELECT t.OppgaveId, t.Beskrivelse, t.Detaljer, t.Frist, t.Status, e.AnsattEpost
                 FROM TASKS t
                 LEFT JOIN EMPLOYEE e ON e.AnsattId = t.AnsattId
                WHERE t.OppgaveId = ? AND t.BedriftsId = ?";
    $vis = $isEmployee
        ? fetchRows($conn, $visSql . " AND t.AnsattId = ?", 'iii', [$viewTask, $minBedriftsId, $userId])
        : fetchRows($conn, $visSql, 'ii', [$viewTask, $minBedriftsId]);

    if (!$vis && $error === '') {
        $error = "Task not found";
    }
}

/* Alle lister filtreres på bedrift. Ansatte ser i tillegg bare sine egne. */
$filterOppgaver = 'WHERE t.BedriftsId = ?';
$filterAvvik    = 'WHERE d.BedriftsId = ?';
$types          = 'i';
$params         = [$minBedriftsId];

if ($isEmployee) {
    $filterOppgaver .= ' AND t.AnsattId = ?';
    $filterAvvik    .= ' AND d.AnsattId = ?';
    $types           = 'ii';
    $params          = [$minBedriftsId, $userId];
}

$oppgaver = fetchRows(
    $conn,
    "SELECT t.OppgaveId, t.Beskrivelse, t.Frist, t.Status, e.AnsattEpost
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
    $ansatte = fetchRows($conn, "SELECT AnsattId, AnsattEpost FROM EMPLOYEE WHERE BedriftsId = ? ORDER BY AnsattEpost", 'i', [$minBedriftsId]);
}

$meldinger = [
    'task'              => 'Task saved.',
    'deviation'         => 'Deviation reported.',
    'task_updated'      => 'Task updated.',
    'deviation_updated' => 'Deviation updated.',
    'deleted'           => 'Deleted.',
    'status'            => 'Status updated.',
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

    <?php if ($vis): $t = $vis[0]; ?>
        <div class="panel" id="view">
            <h3>
                <?= htmlspecialchars($t['Beskrivelse'] ?? '') ?>
                <a href="reports.php">Back to reports</a>
            </h3>
            <table>
                <tr>
                    <th>Assigned to</th>
                    <td><?= htmlspecialchars($t['AnsattEpost'] ?? 'Unassigned') ?></td>
                </tr>
                <tr>
                    <th>Due date</th>
                    <td>
                        <span class="badge<?= ($t['Status'] !== 'Done' && erForfalt($t['Frist'])) ? ' late' : '' ?>">
                            <?= $t['Frist'] ? htmlspecialchars(visDato($t['Frist'])) : 'No due date' ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        <span class="badge <?= statusKlasse($t['Status']) ?>">
                            <?= htmlspecialchars($t['Status']) ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>Details</th>
                    <td><?= nl2br(htmlspecialchars($t['Detaljer'] ?? '')) ?: 'No further details.' ?></td>
                </tr>
            </table>

            <form method="POST" action="reports.php?view_task=<?= (int) $t['OppgaveId'] ?>" class="report-form">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                <input type="hidden" name="type" value="task">
                <input type="hidden" name="handling" value="status">
                <input type="hidden" name="from" value="view">
                <input type="hidden" name="id" value="<?= (int) $t['OppgaveId'] ?>">

                <label for="view_status">Status</label>
                <select id="view_status" name="status">
                    <?php foreach ($statuser as $s): ?>
                        <option value="<?= htmlspecialchars($s) ?>" <?= $t['Status'] === $s ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit">Update status</button>
                <?php if ($canDelegate): ?>
                    <a class="cancel" href="reports.php?edit_task=<?= (int) $t['OppgaveId'] ?>">Edit task</a>
                <?php endif; ?>
            </form>
        </div>
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

                <label for="task_detaljer">Details (optional)</label>
                <textarea id="task_detaljer" name="detaljer" rows="5" maxlength="2000"><?= gammelVerdi('task', $detaljer) ?></textarea>

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

                <label for="task_status">Status</label>
                <select id="task_status" name="status">
                    <?php foreach ($statuser as $s): ?>
                        <option value="<?= htmlspecialchars($s) ?>" <?= $statusValgt === $s ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s) ?>
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
                    <th>Status</th>
                    <?php if ($canDelegate): ?>
                        <th></th>
                    <?php endif; ?>
                </tr>
                <?php foreach ($oppgaver as $o): ?>
                    <tr>
                        <td>
                            <a href="reports.php?view_task=<?= (int) $o['OppgaveId'] ?>">
                                <?= htmlspecialchars($o['Beskrivelse'] ?? '') ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($o['AnsattEpost'] ?? 'Unassigned') ?></td>
                        <td>
                            <span class="badge<?= ($o['Status'] !== 'Done' && erForfalt($o['Frist'])) ? ' late' : '' ?>">
                                <?= $o['Frist'] ? htmlspecialchars(visDato($o['Frist'])) : 'No due date' ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?= statusKlasse($o['Status']) ?>">
                                <?= htmlspecialchars($o['Status']) ?>
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
                    <?php if ($canDelegate): ?>
                        <th></th>
                    <?php endif; ?>
                </tr>
                <?php foreach ($avvik as $a): ?>
                    <tr>
                        <td><?= htmlspecialchars($a['Beskrivelse'] ?? '') ?></td>
                        <td><?= htmlspecialchars($a['AnsattEpost'] ?? 'Unknown') ?></td>
                        <td><?= htmlspecialchars(visDato($a['Frist'])) ?></td>
                        <?php if ($canDelegate): ?>
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
                        <?php endif; ?>
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