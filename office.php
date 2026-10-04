<?php
/* WorkSync – Offices */

$pageTitle  = "Offices";
$activePage = "office";
require 'header.php';

$kontorer = fetchRows(
    $conn,
    "SELECT o.KontorId, e.AnsattEpost
       FROM OFFICE o
       LEFT JOIN EMPLOYEE e ON e.AnsattId = o.AnsattId
      ORDER BY o.KontorId ASC"
);

$moterom = fetchRows(
    $conn,
    "SELECT MoteromId, Tilgjengelighet
       FROM MEETINGROOM
      ORDER BY MoteromId ASC"
);
?>

<div class="dashboard">

    <h1 class="page-title">Offices</h1>
    <p class="page-subtitle">Offices and meeting rooms in the company</p>

    <div class="panel-grid">

        <div class="panel">
            <h3>
                Offices
                <?php if ($role !== 'employee'): ?>
                    <a href="office.php?action=new">+ Create office</a>
                <?php endif; ?>
            </h3>

            <?php if ($kontorer): ?>
                <table>
                    <tr>
                        <th>Office no.</th>
                        <th>Assigned to</th>
                        <th></th>
                    </tr>
                    <?php foreach ($kontorer as $k): ?>
                        <tr>
                            <td><?= (int) $k['KontorId'] ?></td>
                            <td><?= htmlspecialchars($k['AnsattEpost'] ?? 'Unassigned') ?></td>
                            <td style="text-align:right;">
                                <span class="badge <?= $k['AnsattEpost'] ? 'busy' : 'ok' ?>">
                                    <?= $k['AnsattEpost'] ? 'Occupied' : 'Available' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p class="empty">No offices registered.</p>
            <?php endif; ?>
        </div>

        <div class="panel">
            <h3>Meeting rooms</h3>

            <?php if ($moterom): ?>
                <table>
                    <?php foreach ($moterom as $m): ?>
                        <tr>
                            <td>Room <?= (int) $m['MoteromId'] ?></td>
                            <td style="text-align:right;">
                                <span class="badge <?= statusClass($m['Tilgjengelighet']) ?>">
                                    <?= htmlspecialchars($m['Tilgjengelighet'] ?: 'Unknown') ?>
                                </span>
                            </td>

                            <td style="text-align:right;">
                                <?php if($role === 'employee' && $m['Tilgjengelighet'] === 'Available'): ?>
                                    <a href="bookingroom.php?id=<?= (int) $m['MoreromId']?>">
                                        Book room 
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p class="empty">No meeting rooms registered.</p>
            <?php endif; ?>
        </div>

    </div>

</div>

<?php require 'footer.php'; ?>
