<?php
/* WorkSync – Offices */

$pageTitle  = "Offices";
$activePage = "office";
require 'header.php';

$msg = '';
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])){
    if($role !== 'manager'){
        http_response_code(403);
        exit('Not allowed');
    }
    $type = $_POST['delete'];
    $id = (int) ($_POST['id'] ?? 0);

    $conn->begin_transaction();
    try{
        if ($type === 'office'){
            $u = $conn->prepare("UPDATE EMPLOYEE SET KontorId = NULL WHERE KontorId = ?");
            $u->bind_param("i", $id);
            $u->execute();

            $d = $conn->prepare("DELETE FROM OFFICE WHERE KontorId = ?");
            $d->bind_param("i", $id);
            $d->execute();
        } elseif ($type === 'room'){
            $b = $conn->prepare("DELETE FROM BOOKING WHERE MoteromId = ?");
            $b->bind_param("i", $id);
            $b->execute();

            $d = $conn->prepare("DELETE FROM MEETINGROOM WHERE MoteromId = ?");
            $d->bind_param("i", $id);
            $d->execute();
        }
        $conn->commit();
        $msg = 'Deleted';
    } catch (Exception $e){
        $conn->rollback();
        $msg = 'Could not delete';
    }
}

$kontorer = fetchRows(
    $conn,
    "SELECT o.KontorId, e.AnsattEpost
       FROM OFFICE o
       LEFT JOIN EMPLOYEE e ON e.KontorId = o.KontorId
      ORDER BY o.KontorId ASC"
);

$moterom = fetchRows(
    $conn,
    "SELECT MoteromId, Navn, Kapasitet
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

    <?php if($msg): ?>
        <p class="badge ok"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

    <div class="panel-grid">

    <!-- OFFICES -->
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
                                <?php if($role === 'manager'): ?>
                                    <form method="post" style="display:inline;"
                                        onsubmit="return confirm('Delete office <?= (int) $k['KontorId'] ?>?');">
                                        <input type="hidden" name="delete" value="office">
                                        <input type="hidden" name="id" value="<?= (int) $k['KontorId'] ?>">
                                        <button type="submit">Delete</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p class="empty">No offices registered.</p>
            <?php endif; ?>
        </div>

        <div class="panel">
            <h3>Meeting rooms (today)
                <?php if($role !== 'employee'): ?>
                    <a href="add_meetingsrooms.php">+ add meeting room</a>
                <?php endif;?>
            </h3>

            <?php if ($moterom): ?>
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Capacity</th>
                        <th>Status</th>
                        <th>Bookings today</th>
                        <th></th>
                    </tr>
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
                            <td><?= $romId ?></td>
                            <td><?= htmlspecialchars($m['Navn'] ?: '-')?></td>
                            <td><?= $m['Kapasitet'] ? (int) $m['Kapasitet'] : '-'?></td>
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
                                <a href="bookingroom.php?room=<?=$romId ?>">Book room</a>
                                
                                <?php if ($role === 'manager'): ?>
                                    <form method="post" style="display:inline;"
                                        onsubmit="return confirm('Delete room <?= $romId ?> and all its bookings?');">
                                        <input type="hidden" name="delete" value="room">
                                        <input type="hidden" name="id" value="<?= $romId ?>">
                                        <button type="submit">Delete</button>
                                    </form>
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
