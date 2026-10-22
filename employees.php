<?php
/* WorkSync – Employees
   SRD kap. 2.1: oversikt med kontaktinformasjon og tilgjengelighet */

$pageTitle  = "Employees";
$activePage = "employees";
require 'header.php';

$msg = '';
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_employee'])){
    if($role !== 'manager') {
        http_response_code(403);
        exit('Not allowed');
    }
    $id = (int) ($_POST['id'] ?? 0);
    $conn->begin_transaction();
    try {
        $d = $conn->prepare("DELETE FROM EMPLOYEE WHERE AnsattId = ?");
        $d->bind_param("i", $id);
        $d->execute();
        $conn->commit();
        $msg = 'Employee deleted';
    } catch (Exception $e) {
        $conn->rollback();
        $msg = 'Could not delete employee';
    }
}

$alleAnsatte = fetchRows(
    $conn,
    "SELECT e.AnsattId, e.AnsattEpost, e.AnsattTelefonnr, e.Stilling, e.Avdeling, e.Tilgjengelighet, e.KontorId
       FROM EMPLOYEE e
      ORDER BY e.AnsattId ASC"
);

$statuser = [];
foreach($alleAnsatte as $a){
    $statuser[] = $a['Tilgjengelighet'] ?: 'Unknown';
}
$statuser = array_values(array_unique($statuser));
sort($statuser);

$valgt = $_GET['status'] ?? '';
if(!in_array($valgt, $statuser, true)){
    $valgt = '';
}

$ansatte = $valgt === ''
    ? $alleAnsatte : array_filter($alleAnsatte, function($a) use ($valgt){
        return($a['Tilgjengelighet'] ?: 'Unknown') === $valgt;
    });
?>

<div class="dashboard">

    <h1 class="page-title">Employees</h1>
    <?php if ($msg): ?>
        <p class="badge ok"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>
    <p class="page-subtitle">
        <?= count($ansatte) ?> of <?= count($alleAnsatte) ?> employees
        <?= $valgt !== '' ? 'with status "' . htmlspecialchars($valgt) . '"' : 'registered' ?>
    </p>
    <form method="get" style="margin-bottom: 1rem;">
        <label for="status">Filter by availability:</label>
        <select name="status" id="status" onchange="this.form.submit()">
            <?php foreach ($statuser as $s): ?>
            <option value="<?= htmlspecialchars($s) ?>"<?= $s === $valgt ? 'selected' : ''?>>
                <?= htmlspecialchars($s)?>
            </option>
        <?php endforeach; ?>
    </select>
    <noscript><button type="submit">Filter</button></noscript>
    <?php if ($valgt !== ''): ?>
        <a href="?">Clear</a>
        <?php endif; ?>
    </form>
    <div class="panel">
        <h3>
            All employees
            <?php if ($role !== 'employee'): ?>
                <a href="add_employees.php?action=new">+ Add employee</a>
            <?php endif; ?>
        </h3>

        <?php if ($ansatte): ?>
            <table>
                <tr>
                    <th>Employee no.</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Position</th>
                    <th>Department</th>
                    <th>Office</th>
                    <th>Availability</th>
                    <th></th>
                </tr>
                <?php foreach ($ansatte as $e): ?>
                    <tr>
                        <td><?= (int) $e['AnsattId'] ?></td>
                        <td><?= htmlspecialchars($e['AnsattEpost'] ?? '') ?></td>
                        <td><?= htmlspecialchars($e['AnsattTelefonnr'] ?: '–') ?></td>
                        <td><?= htmlspecialchars($e['Stilling'] ?: '-')?></td>
                        <td><?= htmlspecialchars($e['Avdeling'] ?: '-')?></td>
                        <td><?= $e['KontorId'] ? (int) $e['KontorId'] : '–' ?></td>
                        <td>
                            <span class="badge <?= statusClass($e['Tilgjengelighet']) ?>">
                                <?= htmlspecialchars($e['Tilgjengelighet'] ?: 'Unknown') ?>
                            </span>
                        </td>
                        <td style="text-align:right;">
                            <?php if($role === 'manager'): ?>
                                    <a  href="edit_employee.php?id=<?= (int) $e['AnsattId'] ?>" class="btn-small">Edit</a>
                                    <form method="post" style="display:inline;"
                                        onsubmit="return confirm('Delete employee <?= (int) $e['AnsattId'] ?>? This cannot be undone.');">
                                        <input type="hidden" name="delete_employee" value="1">
                                        <input type="hidden" name="id" value="<?= (int) $e['AnsattId'] ?>">
                                        <button type="submit" class="btn-small danger">Delete</button>
                                    </form>
                                <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p class="empty">No employees registered.</p>
        <?php endif; ?>
    </div>

</div>

<?php require 'footer.php'; ?>
