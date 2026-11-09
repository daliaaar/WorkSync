<?php

$pageTitle  = "Office details";
$activePage = "office";
require 'header.php';


$msg = '';
$msgOk = true;
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
            $msgOk = false;
            $msg = ($e->getCode() === 1062)
                ? 'The office is already assigned to an employee'
                : 'Could not update office';
        }  catch (Exception $e) {
            $conn->rollback();
            $msgOk = false;
            $msg = 'Could not update office';
        }
    }
    $s = $conn->prepare(
        "SELECT o.KontorId, o.Avdeling, o.Etasje, 
                o.Bygning, e.AnsattId, e.AnsattEpost
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

$plassering = [];
if ($kontor) {
    $plassering = array_filter([
        $kontor['Bygning'], 
        !empty($kontor['Etasje']) ? 'Floor ' . $kontor['Etasje'] : null,
        $kontor['Avdeling'],
    ]);
}
?>
<style>
    .detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        gap: 20px;
        margin: 0;
    }
    .detail-item dt {
        font-size: .75rem;
        text-transform: uppercase;
        letter-spacing: .06px;
        opacity: .65;
        margin-bottom: 4px;
    }    
    .detail-item dd {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 600;
    }
    .detail-item dd.empty {
        font-weight: 400;
        opacity: .5;
    }
    .edit-form { max-width: 480px;}
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
    <a href="office.php" class="back-button">&larr; Back</a>

    <?php if(!$kontor): ?>
        <h1 class="page-title">Office not found</h1>
    <?php else: ?>
        <h1 class="page-title">Office <?= (int) $kontor['KontorId'] ?></h1>
        <?php if ($plassering): ?>
            <p class="page-subtitle"><?= htmlspecialchars(implode(', ', $plassering)) ?></p>
        <?php endif; ?>

        <?php if ($msg): ?>
            <p class="badge <?= $msgOk ? 'ok' : 'busy' ?>"><?= htmlspecialchars($msg) ?></p>
        <?php endif; ?>

        <div class="panel">
            <?php if ($role === 'manager'): ?>
                <form method="post" class="edit-form" action="?id=<?= (int) $kontor['KontorId'] ?>">
                    <input type="hidden" name="save" value="1">
                    <input type="hidden" name="kontor_id" value="<?= (int) $kontor['KontorId'] ?>">
                    
                        <label>Department
                            <input type="text" name="avdeling" maxlength="100"
                                value="<?= htmlspecialchars($kontor['Avdeling'] ?? '') ?>">
                        </label>
                        <label>Floor
                            <input type="text" name="etasje" maxlength="20"
                                value="<?= htmlspecialchars($kontor['Etasje'] ?? '') ?>">
                        </label>
                        <label>Building
                            <input type="text" name="bygning" maxlength="100"
                                value="<?= htmlspecialchars($kontor['Bygning'] ?? '') ?>">
                        </label>
                        <label>Assigned to
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
                <dl class="detail-grid">
                <?php 
                $felt = [
                    'Building'     => $kontor['Bygning'], 
                    'Floor'        => $kontor['Etasje'],
                    'Department'   => $kontor['Avdeling'],
                ];
                foreach ($felt as $etikett => $verdi): ?>
                    <div class="detail-item">
                        <dt><?= $etikett ?></dt>
                        <dd class="<?= $verdi ? '' : 'empty' ?>">
                            <?= $verdi ? htmlspecialchars($verdi) : 'Not specified' ?>
                        </dd>
                    </div>
                <?php endforeach; ?>
                
                <div class="detail-item">
                    <dt>Assigned to</dt>
                    <dd>
                        <?php if ($kontor['AnsattEpost']): ?>
                            <?= htmlspecialchars($kontor['AnsattEpost']) ?>
                            <span class="badge busy">Occupied</span>
                        <?php else: ?>
                            <span class="badge ok">Available</span>
                        <?php endif; ?>
                    </dd>
                </div>
            </dl>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<?php require 'footer.php'; ?>