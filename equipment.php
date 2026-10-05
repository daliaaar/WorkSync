<?php
ob_start();

$pageTitle  = "Equipment";
$activePage = "equipment";
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

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$isEmployee = ($role === 'employee');
$canManage  = in_array($role, ['admin', 'manager'], true);
$saved      = $_GET['saved'] ?? '';

$showForm = $canManage && (($_GET['action'] ?? '') === 'new');
$editId   = $canManage ? (int) ($_GET['edit'] ?? 0) : 0;
if ($editId > 0) {
    $showForm = true;
}

$error   = "";
$navn    = "";
$type    = "";
$kjop    = "";
$garanti = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $handling = $_POST['handling'] ?? '';

    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $error = "Invalid form submission. Please try again.";
    } elseif (!$conn) {
        $error = "Database connection is not available";
    } else {
        try {
            if (in_array($handling, ['register', 'update'], true) && $canManage) {

                $showForm = true;
                $editId   = ($handling === 'update') ? (int) ($_POST['id'] ?? 0) : 0;
                $navn     = trim($_POST['navn'] ?? '');
                $type     = trim($_POST['type'] ?? '');
                $kjop     = trim($_POST['kjopsdato'] ?? '');
                $garanti  = trim($_POST['garanti'] ?? '');

                if ($navn === '') {
                    $error = "Name is required";
                } elseif (strlen($navn) > 100 || strlen($type) > 100) {
                    $error = "Name and type can be at most 100 characters";
                } elseif (!gyldigDato($kjop) || !gyldigDato($garanti)) {
                    $error = "Invalid date";
                } elseif ($kjop !== '' && $garanti !== '' && $garanti < $kjop) {
                    $error = "Warranty cannot expire before the purchase date";
                } elseif ($handling === 'update' && $editId <= 0) {
                    $error = "Equipment not found";
                } else {
                    $typeDb    = $type !== ''    ? $type    : null;
                    $kjopDb    = $kjop !== ''    ? $kjop    : null;
                    $garantiDb = $garanti !== '' ? $garanti : null;

                    if ($handling === 'update') {
                        $stmt = $conn->prepare(
                            "UPDATE EQUIPMENT
                                SET Navn = ?, Type = ?, Kjopsdato = ?, Garanti = ?
                              WHERE UtstyrId = ?"
                        );
                        $stmt->bind_param("ssssi", $navn, $typeDb, $kjopDb, $garantiDb, $editId);
                        $melding = 'updated';
                    } else {
                        $stmt = $conn->prepare(
                            "INSERT INTO EQUIPMENT (Navn, Type, Status, Kjopsdato, Garanti)
                             VALUES (?, ?, 'Available', ?, ?)"
                        );
                        $stmt->bind_param("ssss", $navn, $typeDb, $kjopDb, $garantiDb);
                        $melding = 'registered';
                    }
                    $stmt->execute();
                    $stmt->close();

                    header("Location: equipment.php?saved=" . $melding);
                    exit();
                }

            } elseif ($handling === 'delete' && $canManage) {

                $id   = (int) ($_POST['id'] ?? 0);
                $stmt = $conn->prepare(
                    "DELETE FROM EQUIPMENT WHERE UtstyrId = ? AND AnsattId IS NULL"
                );
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $slettet = $stmt->affected_rows === 1;
                $stmt->close();

                if ($slettet) {
                    header("Location: equipment.php?saved=deleted");
                    exit();
                }
                $error = "Release the equipment before deleting it";

            } elseif ($handling === 'reserve' && $isEmployee) {

                $id   = (int) ($_POST['id'] ?? 0);
                $mine = (int) $userId;
                $stmt = $conn->prepare(
                    "UPDATE EQUIPMENT SET Status = 'Reserved', AnsattId = ?
                      WHERE UtstyrId = ? AND AnsattId IS NULL"
                );
                $stmt->bind_param("ii", $mine, $id);
                $stmt->execute();
                $ok = $stmt->affected_rows === 1;
                $stmt->close();

                if ($ok) {
                    header("Location: equipment.php?saved=reserved");
                    exit();
                }
                $error = "That item is no longer available";

            } elseif ($handling === 'release') {

                $id = (int) ($_POST['id'] ?? 0);

                if ($isEmployee) {
                    $mine = (int) $userId;
                    $stmt = $conn->prepare(
                        "UPDATE EQUIPMENT SET Status = 'Available', AnsattId = NULL
                          WHERE UtstyrId = ? AND AnsattId = ?"
                    );
                    $stmt->bind_param("ii", $id, $mine);
                } else {
                    $stmt = $conn->prepare(
                        "UPDATE EQUIPMENT SET Status = 'Available', AnsattId = NULL
                          WHERE UtstyrId = ?"
                    );
                    $stmt->bind_param("i", $id);
                }
                $stmt->execute();
                $stmt->close();

                header("Location: equipment.php?saved=released");
                exit();

            } else {
                $error = "Unknown action";
            }
        } catch (mysqli_sql_exception $e) {
            error_log('Equipment: ' . $e->getMessage());
            $error = "Could not save. Please try again later.";
        
        }
    }

} elseif ($editId > 0) {

    $rad = fetchRows(
        $conn,
        "SELECT Navn, Type, Kjopsdato, Garanti FROM EQUIPMENT WHERE UtstyrId = ?",
        'i',
        [$editId]
    );

    if ($rad) {
        $navn    = $rad[0]['Navn'] ?? '';
        $type    = $rad[0]['Type'] ?? '';
        $kjop    = $rad[0]['Kjopsdato'] ?? '';
        $garanti = $rad[0]['Garanti'] ?? '';
    } else {
        $error    = "Equipment not found";
        $showForm = false;
        $editId   = 0;
    }
}

$utstyr = fetchRows(
    $conn,
    "SELECT u.UtstyrId, u.Navn, u.Type, u.Status, u.Kjopsdato, u.Garanti,
            u.AnsattId, e.AnsattEpost
       FROM EQUIPMENT u
       LEFT JOIN EMPLOYEE e ON e.AnsattId = u.AnsattId
      ORDER BY u.UtstyrId ASC"
);

$meldinger = [
    'registered' => 'Equipment registered.',
    'updated'    => 'Equipment updated.',
    'deleted'    => 'Equipment deleted.',
    'reserved'   => 'Equipment reserved.',
    'released'   => 'Equipment released.',
];
?>

<div class="dashboard">

    <h1 class="page-title">Equipment</h1>
    <p class="page-subtitle">Equipment available to employees</p>

    <?php if (isset($meldinger[$saved])): ?>
        <div class="msg ok"><?= htmlspecialchars($meldinger[$saved]) ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="msg err"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($showForm): ?>
        <div class="panel">
            <h3><?= $editId > 0 ? 'Edit equipment' : 'Register equipment' ?></h3>
            <form method="POST"
                  action="equipment.php?<?= $editId > 0 ? 'edit=' . $editId : 'action=new' ?>"
                  class="report-form">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                <input type="hidden" name="handling" value="<?= $editId > 0 ? 'update' : 'register' ?>">
                <?php if ($editId > 0): ?>
                    <input type="hidden" name="id" value="<?= $editId ?>">
                <?php endif; ?>

                <label for="navn">Name</label>
                <input type="text" id="navn" name="navn" maxlength="100" required
                       value="<?= htmlspecialchars($navn) ?>">

                <label for="type">Type (optional)</label>
                <input type="text" id="type" name="type" maxlength="100"
                       placeholder="e.g. Laptop, Projector"
                       value="<?= htmlspecialchars($type) ?>">

                <label for="kjopsdato">Purchase date (optional)</label>
                <input type="text" class="datepicker" id="kjopsdato" name="kjopsdato"
                       placeholder="Select date" autocomplete="off"
                       title="Format: YYYY-MM-DD"
                       value="<?= htmlspecialchars($kjop) ?>">

                <label for="garanti">Warranty expires (optional)</label>
                <input type="text" class="datepicker" id="garanti" name="garanti"
                       placeholder="Select date" autocomplete="off"
                       title="Format: YYYY-MM-DD"
                       value="<?= htmlspecialchars($garanti) ?>">

                <button type="submit"><?= $editId > 0 ? 'Save changes' : 'Register equipment' ?></button>
                <a class="cancel" href="equipment.php">Cancel</a>
            </form>
        </div>
    <?php endif; ?>

    <div class="panel">
        <h3>
            All equipment
            <?php if ($canManage): ?>
                <a href="equipment.php?action=new">+ Register equipment</a>
            <?php endif; ?>
        </h3>

        <?php if ($utstyr): ?>
            <table>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <?php if ($canManage): ?>
                        <th>Purchased</th>
                        <th>Warranty</th>
                        <th>Reserved by</th>
                    <?php endif; ?>
                    <th>Status</th>
                    <th></th>
                </tr>
                <?php foreach ($utstyr as $u): ?>
                    <?php
                    $reservert = !empty($u['AnsattId']);
                    $erMitt    = $reservert && (int) $u['AnsattId'] === (int) $userId;
                    $uId       = (int) $u['UtstyrId'];

                    if ($reservert) {
                        $statusTekst = 'Reserved';
                        $statusCss   = 'busy';
                    } else {
                        $statusTekst = ($u['Status'] ?? '') === 'Reserved'
                            ? 'Available'
                            : ($u['Status'] ?: 'Unknown');
                        $statusCss   = statusClass($statusTekst);
                    }
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($u['Navn'] ?? '') ?></td>
                        <td><?= htmlspecialchars($u['Type'] ?? '') ?></td>
                        <?php if ($canManage): ?>
                            <td><?= htmlspecialchars(visDato($u['Kjopsdato'])) ?></td>
                            <td>
                                <span class="badge<?= erForfalt($u['Garanti']) ? ' late' : '' ?>">
                                    <?= htmlspecialchars(visDato($u['Garanti'])) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($u['AnsattEpost'] ?? '–') ?></td>
                        <?php endif; ?>
                        <td>
                            <span class="badge <?= $statusCss ?>">
                                <?= htmlspecialchars($statusTekst) ?>
                            </span>
                        </td>
                        <td style="white-space:nowrap">
                            <?php if ($isEmployee && !$reservert): ?>
                                <form method="POST" action="equipment.php" class="inline-form">
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                                    <input type="hidden" name="handling" value="reserve">
                                    <input type="hidden" name="id" value="<?= $uId ?>">
                                    <button type="submit" class="btn-small">Reserve</button>
                                </form>
                            <?php endif; ?>

                            <?php if (($isEmployee && $erMitt) || ($canManage && $reservert)): ?>
                                <form method="POST" action="equipment.php" class="inline-form">
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                                    <input type="hidden" name="handling" value="release">
                                    <input type="hidden" name="id" value="<?= $uId ?>">
                                    <button type="submit" class="btn-small">Release</button>
                                </form>
                            <?php endif; ?>

                            <?php if ($canManage): ?>
                                <a class="btn-small" href="equipment.php?edit=<?= $uId ?>">Edit</a>
                                <?php if (!$reservert): ?>
                                    <form method="POST" action="equipment.php" class="inline-form"
                                          onsubmit="return confirm('Delete this equipment? This cannot be undone.');">
                                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                                        <input type="hidden" name="handling" value="delete">
                                        <input type="hidden" name="id" value="<?= $uId ?>">
                                        <button type="submit" class="btn-small danger">Delete</button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p class="empty">No equipment registered.</p>
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