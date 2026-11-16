<?php
/* WorkSync – Offices */

$pageTitle  = "Offices";
$activePage = "office";
require 'header.php';
date_default_timezone_set('Europe/Oslo');

$msg = '';

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$minEpost = $_SESSION['email'] ?? '';

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])){
    if(!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('Invalid form submission');
    }
    $bookingId = (int) ($_POST['booking_id'] ?? 0);
    $erManager = ($role === 'manager') ? 1 : 0;
    $naa= date('Y-m-d H:i:s');

    /* Bare bookinger på møterom i egen bedrift */
    $stmt = $conn->prepare(
        "DELETE b FROM BOOKING b
           JOIN MEETINGROOM m ON m.MoteromId = b.MoteromId
          WHERE b.BookingId = ?
            AND m.BedriftsId = ?
            AND (? = 1 OR b.AnsattEpost = ?)
            AND TIMESTAMP(b.Dato, b.Sluttid) > ?"
    );
    $stmt->bind_param("iiiss", $bookingId, $minBedriftsId, $erManager, $minEpost, $naa);
    $stmt->execute();
    $msg = $stmt->affected_rows > 0 ? 'Booking cancelled' : 'Could not cancel booking';
    $stmt->close();
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])){
    if($role !== 'manager'){
        http_response_code(403);
        exit('Not allowed');
    }
    if(!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('Invalid form submission');
    }
    $type = $_POST['delete'];
    $id = (int) ($_POST['id'] ?? 0);

    $conn->begin_transaction();
    try{
        if ($type === 'office'){
            $u = $conn->prepare("UPDATE EMPLOYEE SET KontorId = NULL WHERE KontorId = ? AND BedriftsId = ?");
            $u->bind_param("ii", $id, $minBedriftsId);
            $u->execute();

            $d = $conn->prepare("DELETE FROM OFFICE WHERE KontorId = ? AND BedriftsId = ?");
            $d->bind_param("ii", $id, $minBedriftsId);
            $d->execute();
        } elseif ($type === 'room'){
            $b = $conn->prepare(
                "DELETE b FROM BOOKING b
                   JOIN MEETINGROOM m ON m.MoteromId = b.MoteromId
                  WHERE b.MoteromId = ? AND m.BedriftsId = ?"
            );
            $b->bind_param("ii", $id, $minBedriftsId);
            $b->execute();

            $d = $conn->prepare("DELETE FROM MEETINGROOM WHERE MoteromId = ? AND BedriftsId = ?");
            $d->bind_param("ii", $id, $minBedriftsId);
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
    "SELECT o.KontorId, o.Avdeling, o.Etasje, o.Bygning, e.AnsattEpost
       FROM OFFICE o
       LEFT JOIN EMPLOYEE e ON e.KontorId = o.KontorId
      WHERE o.BedriftsId = ?
      ORDER BY o.Bygning IS NULL, o.Bygning ASC, o.Etasje IS NULL, LENGTH(o.Etasje) ASC, o.Etasje ASC,
      o.KontorId ASC",
    'i',
    [$minBedriftsId]
);

$grupper = [];
foreach($kontorer as $k){
    $bygg = !empty($k['Bygning']) ? $k['Bygning'] : 'No building';
    $etasje = ($k['Etasje'] !== null && $k['Etasje'] !== '') ? 'Floor ' . $k['Etasje'] : 'No floor';
    $grupper[$bygg][$etasje][] = $k;
}
$moterom = fetchRows(
    $conn,
    "SELECT MoteromId, Navn, Kapasitet, Bygning, Etasje
       FROM MEETINGROOM
      WHERE BedriftsId = ?
      ORDER BY Bygning IS NULL, Bygning ASC, Etasje IS NULL, LENGTH(Etasje) ASC, MoteromId ASC",
    'i',
    [$minBedriftsId]
);

$idag = date('Y-m-d');
$visDato = $_GET['dato'] ?? $idag;

$gyldigDato = DateTime::createFromFormat('Y-m-d', $visDato);
if(!$gyldigDato || $gyldigDato ->format('Y-m-d') !== $visDato){
    $visDato = $idag;
}
$erIdag = ($visDato === $idag);

$stmt = $conn->prepare(
    "SELECT b.BookingId, b.MoteromId, b.Starttid, b.Sluttid, b.AnsattEpost
       FROM BOOKING b
       JOIN MEETINGROOM m ON m.MoteromId = b.MoteromId
      WHERE b.Dato = ? AND m.BedriftsId = ?
      ORDER BY b.Starttid ASC"
);
$stmt->bind_param("si", $visDato, $minBedriftsId);
$stmt->execute();
$dagensBookinger = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

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

    <style>
        .panel-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr); 
            gap: 16px; 
            
        }
    </style>
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
                <style>
                    .bygg-title {
                        font-size: 1rem;
                        font-weight: 500;
                        margin: 1.25rem 0 .75rem;
                    }
                    .bygg-title:first-of-type {
                        margin-top: 0;
                    }
                    .etg-title {
                        font-size: .8rem;
                        opacity: .65;
                        margin: 0 0 8px;
                    }
                    .kontor-rad {
                        display: grid;
                        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; margin-bottom: 1rem; 
                    }
                    .kontor-boks {
                        border: 1px solid rgba(128, 128, 128, 0.3);
                        border-radius: 8px;
                        padding: 10px 12px;
                    }
                    .kontor-boks.opptatt {
                        border-left: 4px solid #b45309;
                    }
                    .kontor-boks.ledig {
                        border-left: 4px solid #15803d;
                    }
                    .kontor-boks .nr {
                        font-weight: 500;
                    }
                    .kontor-boks .sub {
                        font-size: .8rem;
                        margin-top: 2px;
                    }
                    .kontor-boks .avd {
                        font-size: .75rem;
                        opacity: .65;
                    }
                    .kontor-boks form {
                        margin-top: 8px;
                    }
                </style>
                <?php foreach($grupper as $bygg => $etasjer): ?>
                    <p class="bygg-title"><?= htmlspecialchars($bygg) ?></p>
                    <?php foreach($etasjer as $etg => $liste): ?>
                        <p class="etg-title"><?= htmlspecialchars($etg) ?></p>
                        <div class="kontor-rad">
                            <?php foreach($liste as $k): ?>
                                <div class="kontor-boks <?= $k['AnsattEpost'] ? 'opptatt' : 'ledig' ?>">
                                    <div class="nr">
                                        <a href="office_details.php?id=<?= (int) $k['KontorId'] ?>" title="View office details">
                                            Office <?= (int) $k['KontorId'] ?>
                                        </a>
                                    </div>
                                    <div class="sub"><?= $k['AnsattEpost'] ? htmlspecialchars($k['AnsattEpost']) : 'Available' ?></div>
                                    <?php if(!empty($k['Avdeling'])): ?>
                                        <div class="avd"><?= htmlspecialchars($k['Avdeling']) ?></div>
                                    <?php endif; ?>
                                      <?php if($role === 'manager'): ?>
                                    <form method="post" 
                                        onsubmit="return confirm('Delete office <?= (int) $k['KontorId'] ?>?');">
                                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                                        <input type="hidden" name="delete" value="office">
                                        <input type="hidden" name="id" value="<?= (int) $k['KontorId'] ?>">
                                        <button type="submit" class="btn-small danger">Delete</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
            <?php endforeach; ?>
            <?php else: ?>
                <p class="empty">No offices registered.</p>
            <?php endif; ?>
        </div>

        <!-- MEETING ROOMS -->
        <div class="panel">
            <h3>Meeting rooms (<?= $erIdag ? 'today' : htmlspecialchars($visDato) ?>)
                <?php if($role !== 'employee'): ?>
                    <a href="add_meetingsrooms.php">+ Create meeting room</a>
                <?php endif;?>
            </h3>
            <form method="get" style="margin-bottom:12px;">
                <input type="date" name="dato" 
                        value="<?= htmlspecialchars($visDato) ?>"
                        onchange="this.form.submit()">
                <?php if(!$erIdag): ?>
                    <a href="?">Back to today</a>
                <?php endif; ?>
            </form>
            <?php if ($moterom): ?>
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Capacity</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Bookings<?= $erIdag ? ' today' : '' ?></th>
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
                        $lokasjon = [];
                        if(!empty($m['Bygning'])) $lokasjon[] = $m['Bygning'];
                        if($m['Etasje'] !== null && $m['Etasje'] !== '') $lokasjon[] ='Floor ' . $m['Etasje'];
                        ?>
                        <tr>
                            <td><?= $romId ?></td>
                            <td>
                                <a href="room_details.php?id=<?= $romId ?>" title="View room details">
                                    <?= htmlspecialchars($m['Navn'] ?: 'Room ' . $romId) ?>
                                </a>
                            </td>
                            <td><?= $m['Kapasitet'] ? (int) $m['Kapasitet'] : '-'?></td>
                            <td><?= $lokasjon ? htmlspecialchars(implode(', ', $lokasjon)) : '-' ?></td>
                            <td>
                                <?php if($erIdag): ?>
                                <span class="badge <?= $opptattNaa ? 'busy' : 'ok' ?>">
                                    <?= $opptattNaa ? 'Booked now' : 'Available now'?>
                                </span>
                                <?php else: ?>
                                <span class="badge <?= $bookinger ? 'busy' : 'ok' ?>">
                                    <?= $bookinger ? 'Has bookings' : 'Free all day' ?>
                                </span>
                                <?php endif; ?>
                            </td>
                            <td>
                            <?php if($bookinger): ?>
                                <?php foreach($bookinger as $b): ?>
                                    <?php
                                    $erMin = ($b['AnsattEpost'] === $minEpost);
                                    $erFerdig = ($visDato < $idag) || ($erIdag && $b['Sluttid'] <= $naaTidspunkt);
                                    $kanKansellere = ($role === 'manager' || $erMin) && !$erFerdig;
                                    $bekreftelse = $erMin ? 'Cancel your booking?' : 'Cancel this booking made by ' . $b['AnsattEpost'] . '?';
                                 ?>
                                <span <?= $role === 'manager' ? 'title="' . htmlspecialchars($b['AnsattEpost']) . '"' : '' ?>>
                                    <?= htmlspecialchars(substr($b['Starttid'], 0, 5)) ?>-<?=htmlspecialchars(substr($b['Sluttid'], 0, 5))?>
                                </span>
                                <?php if($kanKansellere): ?>
                                    <form method="post" style="display:inline;"
                                            onsubmit="return confirm(<?= htmlspecialchars(json_encode($bekreftelse), ENT_QUOTES) ?>);">
                                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                                        <input type="hidden" name="cancel_booking" value="1">
                                        <input type="hidden" name="booking_id" value="<?= (int) $b['BookingId'] ?>">
                                        <button type="submit" class="btn-small danger">Cancel</button>
                                    </form>
                                <?php endif; ?>
                                <br>
                                <?php endforeach;?>
                            <?php else: ?>
                                No bookings <?= $erIdag ? 'today' : 'on this date' ?>
                            <?php endif;?>
                            </td>
                            <td style="text-align:right;">
                                <a href="bookingroom.php?room=<?=$romId ?>" class="btn-small">Book room</a>
                                
                                <?php if ($role === 'manager'): ?>
                                    <form method="post" style="display:inline;"
                                        onsubmit="return confirm('Delete room <?= $romId ?> and all its bookings?');">
                                         <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                                        <input type="hidden" name="delete" value="room">
                                        <input type="hidden" name="id" value="<?= $romId ?>">
                                        <button type="submit" class="btn-small danger">Delete</button>
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