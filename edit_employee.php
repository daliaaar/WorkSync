<?php
$pageTitle  = "Edit employee";
$activePage = "edit employee";
require 'header.php';

if ($role !== 'manager'){
    http_response_code(403);
    exit('Not allowed');
}

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$msg = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $epost      = trim($_POST['epost'] ?? '');
    $tlf        = trim($_POST['telefon'] ?? '');
    $stilling   = trim($_POST['stilling'] ?? '');
    $avdeling   = trim($_POST['avdeling'] ?? '');
    $tilgj      = trim($_POST['tilgjengelighet'] ?? '');
    $kontor     = ($_POST['kontor'] ?? '') === '' ? null : (int) $_POST['kontor'];

    if($id <= 0) {
        $msg = 'Invalid employee';
    } elseif (!filter_var($epost, FILTER_VALIDATE_EMAIL)) {
        $msg = 'Invalid email';
    } else {
        try {
            $u = $conn->prepare(
                "UPDATE EMPLOYEE
                    SET AnsattEpost = ?, 
                        AnsattTelefonnr = ?, 
                        Stilling = ?, 
                        Avdeling = ?,
                        Tilgjengelighet = ?,
                        KontorId = ?
                        Where AnsattId = ?"
            ); 
            $u->bind_param("sssssii", $epost, $tlf, $stilling, $avdeling, $tilgj, $kontor, $id);
            $u->execute();
            header('Location: employees.php?saved=1');
            exit;
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() === 1062 && strpos($e->getMessage(). 'uq_employee_kontor') !== false){
                $msg = 'This office is already assigned to another employee';
            } elseif ($e->getCode() === 1062) {
                $msg = 'This email is already in use';
            } else {
                $msg = 'Could not save changes';
            }
        } catch (Exception $e) {
            $msg = 'Could not save changes';
        }
    }
}
$s = $conn->prepare(
    "SELECT AnsattId, AnsattEpost, AnsattTelefonnr, Stilling, Avdeling, Tilgjengelighet, KontorId
        FROM EMPLOYEE
        WHERE AnsattId = ?"
);
$s->bind_param("i", $id);
$s->execute();
$emp = $s->get_result()->fetch_assoc();

if (!$emp) {
    http_response_code(404);
    exit('Employee not found');
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && $msg !== '') {
    $emp['AnsattEpost']         = $epost;
    $emp['AnsattTelefonnr']     = $tlf;
    $emp['Stilling']            = $stilling;
    $emp['Avdeling']            = $avdeling;
    $emp['Tilgjengelighet']     = $tilgj;
    $emp['KontorId']            = $kontor;
}

$kontorer = fetchRows($conn, "SELECT KontorId FROM OFFICE ORDER BY KontorId ASC");

$statusRader = fetchRows(
    $conn,
    "SELECT DISTINCT Tilgjengelighet
    FROM EMPLOYEE
    WHERE Tilgjengelighet IS NOT NULL AND Tilgjengelighet <> ''
    ORDER BY Tilgjengelighet ASC"
);
?>

<div class="dashboard">
    <a href="employees.php" class="back-button">&larr; Back</a>

    <h1 class="page-title">Edit employee - ID <?= (int) $emp['AnsattId'] ?></h1>
    <p class="page-subtitle">Update the employee's details </p>

    <?php if ($msg): ?>
        <p class="badge busy"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

    <div class="panel">
        <form method="post">
            <input type="hidden" name="id" value="<?= (int) $emp['AnsattId'] ?>">
            <p>
                <label>Email<br>
                    <input type="email" name="epost" required
                            value="<?= htmlspecialchars($emp['AnsattEpost'] ?? '') ?>">
                </label>
            </p>
            <p>
                <label>Phone<br>
                    <input type="text" name="telefon"
                            value="<?= htmlspecialchars($emp['AnsattTelefonnr'] ?? '') ?>">
                </label>
            </p>
             <p>
                <label>Position<br>
                    <input type="text" name="stilling" 
                            value="<?= htmlspecialchars($emp['Stilling'] ?? '') ?>">
                </label>
            </p>
            <p>
                <label>Department<br>
                    <input type="text" name="avdeling"
                            value="<?= htmlspecialchars($emp['Avdeling'] ?? '') ?>">
                </label>
            </p>
            <p>
                <label>Availability<br>
                    <input type="text" name="tilgjengelighet" list="status-forslag"
                        value="<?= htmlspecialchars($emp['Tilgjengelighet'] ?? '') ?>">
                </label>
                <datalist id="status-forslag">
                    <?php foreach ($statusRader as $sr): ?>
                        <option value="<?= htmlspecialchars($sr['Tilgjengelighet']) ?>">
                    <?php endforeach; ?>
                </datalist>
            </p>

            <p>
                <label>Office<br>
                    <select name="kontor">
                        <option value="">None</option>
                        <?php foreach ($kontorer as $k): ?>
                            <option value="<?= (int) $k['KontorId'] ?>"
                                <?= (int) $emp['KontorId'] === (int) $k['KontorId'] ? 'selected' : '' ?>>

                                office <?= (int) $k['KontorId'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </p>
        <button type="submit">Save changes</button>
        <a href="employees.php">Cancel</a>
    </form>
    </div>
</div>
<?php require 'footer.php'; ?>



