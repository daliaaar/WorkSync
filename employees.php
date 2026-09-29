<?php
/* WorkSync – Employees
   SRD kap. 2.1: oversikt med kontaktinformasjon og tilgjengelighet */

$pageTitle  = "Employees";
$activePage = "employees";
require 'header.php';

$ansatte = fetchRows(
    $conn,
    "SELECT e.AnsattId, e.AnsattEpost, e.AnsattTelefonnr, e.Tilgjengelighet, e.KontorId
       FROM EMPLOYEE e
      ORDER BY e.AnsattId ASC"
);
?>

<div class="dashboard">

    <h1 class="page-title">Employees</h1>
    <p class="page-subtitle"><?= count($ansatte) ?> employees registered</p>

    <div class="panel">
        <h3>
            All employees
            <?php if ($role !== 'employee'): ?>
                <a href="employees.php?action=new">+ Add employee</a>
            <?php endif; ?>
        </h3>

        <?php if ($ansatte): ?>
            <table>
                <tr>
                    <th>Employee no.</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Office</th>
                    <th>Availability</th>
                </tr>
                <?php foreach ($ansatte as $e): ?>
                    <tr>
                        <td><?= (int) $e['AnsattId'] ?></td>
                        <td><?= htmlspecialchars($e['AnsattEpost'] ?? '') ?></td>
                        <td><?= htmlspecialchars($e['AnsattTelefonnr'] ?: '–') ?></td>
                        <td><?= $e['KontorId'] ? (int) $e['KontorId'] : '–' ?></td>
                        <td>
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

</div>

<?php require 'footer.php'; ?>
