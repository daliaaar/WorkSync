<?php

$pageTitle  = "Office details";
$activePage = "office";
require 'header.php';


$msg = '';
$kontorId = (int) ($_GET['id'] ?? $_POST['kontor_id'] ?? 0);

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    if($role !== 'manager'){
        http_response_code(403);
        exit('Not allowed');
        }
        $avdeling = trim($_POST['avdeling'] ?? '');
        $etasje = trim($_POST['etasje'] ?? '');
        $bygning = trim($_POST['bygning'] ?? '');
        $ansattId = (int) ($_POST['ansatt_id'] ?? 0); // 0 = ingen

        $avdeling = $avdeling === '' ? null : $avdeling;
        $etasje = $etasje === '' ? null : $etasje;
        $bygning = $bygning === '' ? null : $bygning;

        $conn->begin_transaction();
        try {
            $o = $conn->prepare("UPDATE OFFICE SET Avdeling = ?, Etasje = ?, Bygning = ? WHERE KontorId = ?");
            $o->bind_param("sssi", $avdeling, $etasje, $bygning, $kontorId);
            $o->execute();

            $u = $conn->prepare("UPDATE EMPLOYEE SET KontorId = NULL WHERE KontorId = ?");
            $u->bind_param("i", $kontorId);
            $u->execute();

            if($ansattId > 0) {
                $a = $conn->prepare("UPDATE EMPLOYEE SET KontorId = ? WHERE AnsattId = ?");
                $a->bind_param("ii", $kontorId, $ansattId);
                $a->execute();
            }
            $conn->commit();
            $msg = 'Office updated';
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            $msg = ($e->getCode() === 1062)
                ? 'The office is already assigned to an employee'
                : 'Could not update office';
        }  catch (Exception $e) {
            $conn->rollback();
            $msg = 'Could not update office';
        }
    }
    $s = $conn->prepare(
        "SELECT o.KontorId, o.Avdeling, o.Etasje, o.Bygning, e.AnsattId, e.AnsattEpost
                FROM OFFICE o
                LEFT JOIN EMPLOYEE e ON e.KontorId = o.KontorId
                WHERE o.KontorId = ?"
    );
    $s->bind_param("i", $kontorId);
    $s->execute();
    $kontor = $s->get_result()->fetch_assoc();

    $ansatte = ($kontor && $role === 'manager')
        ? fetchRows($conn, "SELECT AnsattId, AnsattEpost, KontorId FROM EMPLOYEE
                            ORDER BY AnsattEpost ASC") : [];
?>
<div class="dashboard">
    <a href="office.php" class="back-button">&larr; Back</a>
    <?php if(!$kontor): ?>
        <h1 class="page-title">Office not found</h1>
    <?php else: ?>
        <h1 class="page-title">Office <?= (int) $kontor['KontorId'] ?></h1>
        <?php if ($msg): ?>
            <p class="badge ok"><?= htmlspecialchars($msg) ?></p>
        <?php endif; ?>

        <div class="panel">
            <?php if ($role === 'manager'): ?>
                <form method="post" action="?id=<?= (int) $kontor['KontorId'] ?>">
                    <input type="hidden" name="save" value="1">
                    <input type="hidden" name="kontor_id" value="<?= (int) $kontor['KontorId'] ?>">
                    
                    <p>
                        <label>Department<br>
                            <input type="text" name="avdeling" maxlength="100"
                                value="<?= htmlspecialchars($kontor['Avdeling'] ?? '') ?>">
                        </label>
                    </p>
                     <p>
                        <label>Floor<br>
                            <input type="text" name="etasje" maxlength="20"
                                value="<?= htmlspecialchars($kontor['Etasje'] ?? '') ?>">
                        </label>
                    </p>
                     <p>
                        <label>Building<br>
                            <input type="text" name="bygning" maxlength="100"
                                value="<?= htmlspecialchars($kontor['Bygning'] ?? '') ?>">
                        </label>
                    </p>
                    <p>
                        <label>Assigned to<br>
                            <select name="ansatt_id">
                                <option value="0">- Unassigned - </option>
                                <?php foreach ($ansatte as $a): ?>
                                    <option value="<?= (int) $a['AnsattId'] ?>"
                                        <?= (int) $a['KontorId'] === (int) $kontor['KontorId'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($a['AnsattEpost']) ?>
                                        <?= ($a['KontorId'] && (int) $a['KontorId'] !== (int) $kontor['KontorId'])
                                        ? '(office ' . (int) $a['KontorId'] . ')' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </p>
                    <button type="submit" class="btn-small">Save</button>
                    <a href="office.php">Cancel</a>
                </form>
            <?php else: ?>
                <table>
                <tr><th>Department</th><td><?= htmlspecialchars($kontor['Avdeling'] ?? '-')?></td></tr>
                <tr><th>Floor</th><td><?= htmlspecialchars($kontor['Etasje'] ?? '-')?></td></tr>
                <tr><th>Building</th><td><?= htmlspecialchars($kontor['Bygning'] ?? '-')?></td></tr>
                <tr><th>Assigned to</th><td><?= htmlspecialchars($kontor['AnsattEpost'] ?? 'Unassigned')?></td></tr>
                </table>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<?php require 'footer.php'; ?>