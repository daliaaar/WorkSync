<?php
$pageTitle  = "Edit employee";
$activePage = "edit employee";
require 'header.php';

if ($role !== 'manager'){
    http_response_code(403);
    exit('Not allowed');
}

$tillateStatuser = ['Available', 'Busy', 'Do not disturb', 'Away', 'Offline'];

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$msg = '';

$s = $conn->prepare(
    "SELECT AnsattId, AnsattEpost, AnsattTelefonnr, Stilling, 
    Avdeling, Tilgjengelighet, KontorId
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

$gyldigeStatuser = $tillateStatuser;
if (!empty($emp['Tilgjengelighet']) && !in_array($emp['Tilgjengelighet'], $gyldigeStatuser, true)) {
    $gyldigeStatuser[] = $emp['Tilgjengelighet'];
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $epost      = trim($_POST['epost'] ?? '');
    $tlf        = trim($_POST['telefon'] ?? '');
    $stilling   = trim($_POST['stilling'] ?? '');
    $avdeling   = trim($_POST['avdeling'] ?? '');
    $tilgj      = trim($_POST['tilgjengelighet'] ?? '');
    $kontor     = ($_POST['kontor'] ?? '') === '' ? null : (int) $_POST['kontor'];
    $nyttPassord = $_POST['nytt_passord'] ?? '';
    $bekreftPassord = $_POST['bekreft_passord'] ?? '';
    
    if($id <= 0) {
        $msg = 'Invalid employee';
    } elseif (!filter_var($epost, FILTER_VALIDATE_EMAIL)) {
        $msg = 'Invalid email';
    } elseif ($tilgj !== '' && !in_array($tilgj, $gyldigeStatuser, true)) {
        $msg = 'Invalid availibility';
    } elseif ($nyttPassord !== '' && strlen($nyttPassord) < 16) {
        $msg = 'Password must be at least 16 characters';
    } elseif ($nyttPassord !== $bekreftPassord) {
        $msg = 'Passwords do not match';
    } else {
        $conn->begin_transaction();
        try {
            $u =  $conn->prepare("UPDATE EMPLOYEE 
                    SET AnsattEpost = ?, 
                        AnsattTelefonnr = ?, 
                        Stilling = ?, 
                        Avdeling = ?, 
                        Tilgjengelighet = ?, 
                        KontorId = ?
                    WHERE AnsattId = ?"
            );
            $u->bind_param("sssssii", $epost, $tlf, $stilling, $avdeling, $tilgj, $kontor, $id);
            $u->execute();
            
            if($nyttPassord !== '') {
                $hash = password_hash($nyttPassord, PASSWORD_DEFAULT);
                $p = $conn->prepare("UPDATE EMPLOYEE SET AnsattPassord = ? WHERE AnsattId = ?");
                $p->bind_param("si", $hash, $id);
                $p->execute();
            }

            $conn->commit();
            header('Location: employees.php?saved=1');
            exit;
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            if ($e->getCode() === 1062 && strpos($e->getMessage(), 'uq_employee_kontor') 
                !== false) {
            $msg = 'This office is already assigned to another employee';
            } elseif ($e->getCode() === 1062) {
                $msg = 'This email is already in use';
            } else {
                $msg = 'Could not save changes';
            }
        } catch (Exception $e) {
            $conn->rollback();
            $msg = 'Could not save changes';
        }     
    }
    
    if($_SERVER['REQUEST_METHOD'] === 'POST' && $msg !== '') {
        $emp['AnsattEpost']         = $epost;
        $emp['AnsattTelefonnr']     = $tlf;
        $emp['Stilling']            = $stilling;
        $emp['Avdeling']            = $avdeling;
        $emp['Tilgjengelighet']     = $tilgj;
        $emp['KontorId']            = $kontor;
    }
}

$kontorer = fetchRows($conn, "SELECT o.KontorId, 
                                e.AnsattId AS EierId, e.AnsattEpost AS EierEpost FROM OFFICE o
                                LEFT JOIN EMPLOYEE e ON e.KontorId = o.KontorId ORDER BY o.KontorId ASC");

?>
<style>
    .edit-form { max-width: 480px; }
    .edit-form label {
        display: block;
        font-size: .85rem; 
        opacity: .85;
        margin-bottom: 12px;
    }
    .edit-form input, 
    .edit-form select {
        display: block;
        width: 100%;
        box-sizing: border-box;
        margin-top: 4px;
    }
</style>

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
                <label>Email
                    <input type="email" name="epost" required
                            value="<?= htmlspecialchars($emp['AnsattEpost'] ?? '') ?>">
                </label>
           
                <label>Phone
                    <input type="text" name="telefon"
                            value="<?= htmlspecialchars($emp['AnsattTelefonnr'] ?? '') ?>">
                </label>
          
                <label>Position
                    <input type="text" name="stilling" 
                            value="<?= htmlspecialchars($emp['Stilling'] ?? '') ?>">
                </label>
           
                <label>Department
                    <input type="text" name="avdeling"
                            value="<?= htmlspecialchars($emp['Avdeling'] ?? '') ?>">
                </label>
        
                <label>Availability
                    <select name="tilgjengelighet">
                        <option value="">Not set</option>
                        <?php foreach($gyldigeStatuser as $status): ?>
                            <option value="<?= htmlspecialchars($status) ?>"
                                <?= ($emp['Tilgjengelighet'] ?? '') === $status ? 'selected' : '' ?>>
                                <?= htmlspecialchars($status) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    </label>

                <label>Office
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

        <label>New password (leave blank to keep the current one)
                <input type="password" name="nytt_passord" minlength="16" autocomplete="new-password">
        </label>
        <label>Confirm new password
                <input type="password" name="bekreft_passord" minlength="16" autocomplete="new-password">
        </label>
        <button type="submit">Save changes</button>
        <a href="employees.php">Cancel</a>
    </form>
    </div>
</div>
<?php require 'footer.php'; ?>



