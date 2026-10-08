<?php
/* WorkSync – Offices */

$pageTitle  = "Offices";
$activePage = "office";
require 'header.php';

$kontorer = fetchRows(
    $conn,
    "SELECT o.KontorId, e.AnsattEpost
       FROM OFFICE o
       LEFT JOIN EMPLOYEE e ON e.KontorId = o.KontorId
      ORDER BY o.KontorId ASC"
);

$moterom = fetchRows(
    $conn,
    "SELECT MoteromId, Tilgjengelighet
       FROM MEETINGROOM
      ORDER BY MoteromId ASC"
);

$dagensBookinger = fetchRows(
    $conn,
    "SELECT MoteromId, Starttid, Sluttid
    FROM BOOKING
    WHERE Dato = '" . date('Y-m-d') . "'
    ORDER BY Starttid ASC"
);

$bookingerPerRom = [];
foreach($dagensBookinger as $booking){
    $bookingerPerRom[(int) $booking['MoteromId']][] = $booking;
}
$naaTidspunkt = date('H:i:s');
?>

<div class="dashboard">

    <h1 class="page-title">Offices</h1>
    <p class="page-subtitle">Offices and meeting rooms in the company</p>

    <div class="panel-grid">

        <div class="panel">
            <h3>
                Offices
                <?php if ($role !== 'employee'): ?>
                    <a href="add_offices.php?action=new">+ Create office</a>
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
            <h3>Meeting rooms (today)</h3>

            <?php if ($moterom): ?>
                <table>
                    <?php foreach ($moterom as $m): ?>
                        <?php
                            $romId = (int) $m['MoteromId'];
                            $bookinger = $bookingerPerRom[$romId] ?? [];
                            $opptattNaa = false;
                            foreach($bookinger as $b){
                                if($b['Starttid'] <= $naaTidspunkt && $b['Sluttid'] > $naaTidspunkt){
                                $opptattNaa = true;
                                break;
                            }
                        }
                        ?>
                        <tr>
                            <td> Room <?= $romId ?></td>
                            <td>
                                <span class="badge <?= $opptattNaa ? 'busy' : 'ok' ?>">
                                    <?= $opptattNaa ? 'Booked now' : 'Available now'?>
                                </span>
                            </td>
                            <td>
                            <?php if($bookinger): ?>
                                <?php foreach($bookinger as $b): ?>
                                    <?= htmlspecialchars(substr($b['Starttid'], 0, 5)) ?>-<?=htmlspecialchars(substr($b['Sluttid'], 0, 5))?><br>
                                <?php endforeach;?>
                            <?php else: ?>
                                No bookings today
                            <?php endif;?>
                            </td>
                            <td style="text-align:right;">
                                <a href="bookingroom.php?room=<?=$romId ?>">Book room </a>
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
