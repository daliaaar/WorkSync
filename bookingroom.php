<?php
$pageTitle  = "Book a meeting room";
$activePage = "bookingroom";
require __DIR__ . '/header.php';
date_default_timezone_set('Europe/Oslo');

$error = [];
$ok = false;

$moterom = [];
$cancelOk = false;
$epost = $_SESSION['email'] ?? '';

$res = $conn->query("SELECT MoteromId, Navn FROM MEETINGROOM ORDER BY MoteromId");
while($row = $res->fetch_assoc()) {
    $moterom[] = $row;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel'])){
    $bookingId = (int) ($_POST['booking_id'] ?? 0);

    if($epost === ''){
        $error[] = "You must be logged in to cancel a booking";
    } else {
        $cancel  = $conn->prepare("DELETE FROM BOOKING WHERE BookingId = ? AND AnsattEpost = ?");
        $cancel->bind_param("is", $bookingId, $epost);
        $cancel->execute();

        if($cancel->affected_rows > 0){
            $cancelOk = true;
        } else {
            $error[] = "Could not cancel the booking";
        }
    }
}

$choosenRoom = (int) ($_POST['room'] ?? $_GET['room'] ?? 0);
$valgtRom = null;
foreach ($moterom as $m) {
    if ((int) $m['MoteromId'] === $choosenRoom){
        $valgtRom = $m;
        break;
    }
}
if (!$valgtRom){
    header('Location: offices.php');
    exit;
}

$dato = $_POST['dato'] ?? $_GET['dato'] ?? date('Y-m-d');
$start = $_POST['start'] ?? '';
$slutt = $_POST['slutt'] ?? '';


if($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['cancel'])) {
    $romFinnes = false;
    foreach ($moterom as $m) {
        if ((int) $m['MoteromId'] === $choosenRoom)  {
            $romFinnes = true; break; 
        }
    }
    if (!$romFinnes){
            $error[] = "Choose a valid meeting room";
    }

    $d = DateTime::createFromFormat('Y-m-d', $dato);
    if(!$d || $d->format('Y-m-d') !== $dato) {
        $error[] = "Invalid date";
    } elseif ($dato < date('Y-m-d')) {
        $error[] = "You cannot book a room for a past date";
    }

    if(!preg_match('/^\d{2}:\d{2}$/', $start) || !preg_match('/^\d{2}:\d{2}$/', $slutt)) {
        $error[] = "Choose valid start and end times";
    } elseif($start >= $slutt) {
        $error[] = "Endtime must be after starttime";
    } elseif ($dato == date('Y-m-d') && $start < date('H:i')) {
        $error[] = "You cannot book a room for a time that has already passed";
    }

    if(!$error){
        $conn->begin_transaction();
        try{
            $romlaas = $conn->prepare("SELECT MoteromId FROM MEETINGROOM WHERE MoteromId = ? FOR UPDATE");
            $romlaas->bind_param("i", $choosenRoom);
            $romlaas->execute();
            $romlaas->get_result();

            $check = $conn->prepare("SELECT COUNT(*) AS amount FROM BOOKING WHERE MoteromId = ? AND Dato = ? AND Starttid < ? AND Sluttid > ?");
            $check->bind_param("isss", $choosenRoom, $dato, $slutt, $start);
            $check->execute();
            $amount  = (int) $check->get_result()->fetch_assoc()['amount'];

            if($amount > 0){
                $conn->rollback();
                $error[] = "The room is already booked for the selected time";
            } else {
                $ins = $conn->prepare("INSERT INTO BOOKING (MoteromId, Dato, Starttid, Sluttid, AnsattEpost) VALUES (?, ?, ?, ?, ?)");
                $ins->bind_param("issss", $choosenRoom, $dato, $start, $slutt, $epost);
                $ins->execute();
                $conn->commit();
                $ok = true;
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error[] = "An error occurred while booking the room. Please try again.";
        }

    }
}

$opptatt = [];
if ($choosenRoom){
    $opptattTider = $conn->prepare("SELECT BookingId, Starttid, Sluttid, AnsattEpost FROM BOOKING WHERE MoteromId = ? AND Dato = ? ORDER BY Starttid");
    $opptattTider->bind_param("is", $choosenRoom, $dato);
    $opptattTider->execute();
    $result = $opptattTider->get_result();
    while($row = $result->fetch_assoc()) {
        $opptatt[] = $row;
    }
}
?>
<div class="dashboard">
    <a href="office.php" class="back-button">&larr; Back to offices</a>
    <h1 class="page-title">Book a meeting room</h1>
    <p class="page-subtitle">Choose a meeting room, date and time</p>
    <?php if($ok): ?>
        <p class="badge ok">The room has been successfully booked</p>
    <?php endif; ?>
    <?php if($cancelOk): ?>
        <p class="badge ok">The booking has been cancelled</p>
    <?php endif; ?>

    <?php foreach($error as $err): ?>
        <p class="badge busy"><?= htmlspecialchars($err) ?></p>
    <?php endforeach; ?>

    <div class="panel-grid">
        <div class="panel">
            <h3>New booking</h3>
            <form method="post" action="bookingroom.php">
                <p>
                    <strong>Room <?= $choosenRoom ?><?=($valgtRom['Navn'] ? ' - ' . htmlspecialchars($valgtRom['Navn']) : '') ?></strong>
                    <input type="hidden" name="room" value="<?= $choosenRoom ?>">
                </p>
                <p>
                    <label>Date<br>
                        <input type="date" name="dato" required
                                min="<?= date('Y-m-d') ?>"
                                value="<?= htmlspecialchars($dato) ?>">
                    </label>
                </p>
                <p>
                    <label>Start time<br>
                        <input type="time" name="start" required
                                value="<?= htmlspecialchars($start) ?>">
                    </label>
                </p>
                <p>
                    <label>End time<br>
                        <input type="time" name="slutt" required
                                value="<?= htmlspecialchars($slutt) ?>">
                    </label>
                </p>
                <p>
                    <input type="submit" value="Book room">
                </p>
            </form>
        </div>
        <div class="panel">
            <h3>Booked<?= $choosenRoom ? ' (Room ' . $choosenRoom . ', ' . htmlspecialchars($dato) . ')' : '' ?></h3>
            <?php if($choosenRoom): ?>
                <form method="get" action="bookingroom.php">
                    <input type="hidden" name="room" value="<?= $choosenRoom ?>">
                    <input type="date" name="dato"
                            min="<?= date('Y-m-d') ?>"
                            value="<?= htmlspecialchars($dato) ?>"
                            onchange="this.form.submit()">
                </form>
            <?php endif; ?>

            <?php if(!$choosenRoom): ?>
                <p class="empty">Choose a room to see booked times</p>
            <?php elseif ($opptatt): ?>
                <table>
                    <tr><th>From</th><th>To</th><th>Booked by</th><th></th></tr>
                        <?php foreach ($opptatt as $booking): ?>
                            <tr>
                                <td><?= htmlspecialchars(substr($booking['Starttid'], 0, 5)) ?></td>
                                <td><?= htmlspecialchars(substr($booking['Sluttid'], 0, 5)) ?></td>
                                <td><?= htmlspecialchars($booking['AnsattEpost']) ?></td>
                                <td style="text-align:right;">
                                    <?php if($epost !== '' && $booking['AnsattEpost'] === $epost): ?>
                                        <form method="post" action="bookingroom.php" style="display:inline;"
                                              onsubmit="return confirm('Cancel this booking?');">
                                              <input type="hidden" name="cancel" value="1">
                                              <input type="hidden" name="booking_id" value="<?= (int) $booking['BookingId'] ?>">
                                              <input type="hidden" name="room" value="<?= $choosenRoom ?>">
                                              <input type="hidden" name="dato" value="<?= htmlspecialchars($dato) ?>">
                                              <button type="submit">Cancel</button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>   
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <p class="empty">No bookings for this room on this date</p>
                <?php endif; ?> 
            </div>
        </div>
    </div>
<?php require 'footer.php'; ?>