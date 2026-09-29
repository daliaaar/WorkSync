<?php
/* WorkSync – Equipment
   SRD kap. 2.1: ansatte ser og reserverer utstyr, manager registrerer
   nytt utstyr og ser kjøpsdato og garanti.

   MERK: EQUIPMENT-tabellen finnes ikke i database.sql ennå. Spørringen
   under returnerer derfor tom liste inntil tabellen er opprettet. */

$pageTitle  = "Equipment";
$activePage = "equipment";
require 'header.php';

$utstyr = fetchRows(
    $conn,
    "SELECT UtstyrId, Navn, Type, Status, Kjopsdato, Garanti, AnsattId
       FROM EQUIPMENT
      ORDER BY UtstyrId ASC"
);
?>

<div class="dashboard">

    <h1 class="page-title">Equipment</h1>
    <p class="page-subtitle">Equipment available to employees</p>

    <div class="panel">
        <h3>
            All equipment
            <?php if ($role !== 'employee'): ?>
                <a href="equipment.php?action=new">+ Register equipment</a>
            <?php endif; ?>
        </h3>

        <?php if ($utstyr): ?>
            <table>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <?php if ($role !== 'employee'): ?>
                        <th>Purchased</th>
                        <th>Warranty</th>
                    <?php endif; ?>
                    <th>Status</th>
                </tr>
                <?php foreach ($utstyr as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['Navn'] ?? '') ?></td>
                        <td><?= htmlspecialchars($u['Type'] ?? '') ?></td>
                        <?php if ($role !== 'employee'): ?>
                            <td><?= $u['Kjopsdato'] ? htmlspecialchars(date('j M Y', strtotime($u['Kjopsdato']))) : '–' ?></td>
                            <td><?= $u['Garanti'] ? htmlspecialchars(date('j M Y', strtotime($u['Garanti']))) : '–' ?></td>
                        <?php endif; ?>
                        <td>
                            <span class="badge <?= statusClass($u['Status']) ?>">
                                <?= htmlspecialchars($u['Status'] ?: 'Unknown') ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p class="empty">No equipment registered.</p>
        <?php endif; ?>
    </div>

</div>

<?php require 'footer.php'; ?>
