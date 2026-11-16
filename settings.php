<?php
/* WorkSync – Settings */

$pageTitle  = "Settings";
$activePage = "settings";
require 'header.php';

$tillatteStatuser = ['Available', 'Busy', 'Do not disturb', 'Away', 'Offline'];
$epost = $_SESSION['email'] ?? '';
$melding = '';

$erManager = ($_SESSION['role'] ?? '' ) === 'manager';

if($erManager) {
    $sqlOppdater = "UPDATE COMPANYMANAGER SET Tilgjengelighet = ? WHERE Epost = ?";
    $sqlHent = "SELECT Tilgjengelighet FROM COMPANYMANAGER WHERE Epost = ?";
} else {
    $sqlOppdater = "UPDATE EMPLOYEE SET Tilgjengelighet = ? WHERE AnsattEpost = ?";
    $sqlHent = "SELECT Tilgjengelighet FROM EMPLOYEE WHERE AnsattEpost = ?";
}
/*ENDRE PASSORD*/
if($erManager) {
    $sqlPassordHent = "SELECT Passord FROM COMPANYMANAGER WHERE Epost = ?";
    $sqlPassordOppdater = "UPDATE COMPANYMANAGER SET Passord = ? WHERE Epost = ?";
} else {
    $sqlPassordHent = "SELECT AnsattPassord AS Passord FROM EMPLOYEE WHERE AnsattEpost = ?";
    $sqlPassordOppdater = "UPDATE EMPLOYEE SET AnsattPassord = ? WHERE AnsattEpost = ?";
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['availability'])){
    $nyStatus = $_POST['availability'];

    if($epost === ''){
        $melding = 'You must be logged in';
    } elseif (!in_array($nyStatus, $tillatteStatuser, true)){
        $melding = 'Invalid availability';
    } else {
        $lagreStatus = $conn->prepare($sqlOppdater);
        $lagreStatus->bind_param("ss", $nyStatus, $epost);
        $lagreStatus->execute();
        $melding = $lagreStatus->affected_rows > 0 ? 'Availibilty updated' : 'Nothing updated';
    }
}
/* ENDRE PASSORD */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {

    $currentPass = $_POST['current_pass'] ?? '';
    $newPass = $_POST['new_pass'] ?? '';
    $confirmPass = $_POST['confirm_pass'] ?? '';

    if ($epost === '') {
        $melding = 'You must be logged in';

    } elseif ($currentPass === '' || $newPass === '' || $confirmPass === '') {
        $melding = 'Please fill in all password fields';

    } elseif ($newPass !== $confirmPass) {
        $melding = 'New passwords do not match';

    } elseif (strlen($newPass) < 16) {
        $melding = 'Password must be at least 16 characters';

    } else {

        // Hent eksisterende passord
        $hentPassord = $conn->prepare($sqlPassordHent);
        $hentPassord->bind_param("s", $epost);
        $hentPassord->execute();

        $bruker = $hentPassord->get_result()->fetch_assoc();

        if (!$bruker) {
            $melding = 'User not found';

        } elseif (!password_verify($currentPass, $bruker['Passord'])) {
            $melding = 'Current password is incorrect';

        } else {

            // Hash det nye passordet
            $hashPassord = password_hash($newPass, PASSWORD_DEFAULT);

            // Oppdater passordet i databasen
            $oppdaterPassord = $conn->prepare($sqlPassordOppdater);
            $oppdaterPassord->bind_param("ss", $hashPassord, $epost);

            if ($oppdaterPassord->execute()) {
                $melding = 'Password successfully updated';
            } else {
                $melding = 'Could not update password';
            }
        }
    }
}

$gjeldendeStatus = 'Available';
$hentStatus = $conn->prepare($sqlHent);
$hentStatus->bind_param("s", $epost);
$hentStatus->execute();
$rad = $hentStatus->get_result()->fetch_assoc();
if($rad && $rad['Tilgjengelighet']){
    $gjeldendeStatus = $rad['Tilgjengelighet'];
}
?>
<!-- SETTINGS CONTENT -->
<div class="settings-container">
    <h2 class="settings-title">System &amp; Profile Settings</h2>
    <?php if ($melding): ?>
        <p class="badge ok"><?= htmlspecialchars($melding) ?></p>
    <?php endif; ?>

    <form action="settings.php" method="POST">

        <!-- PROFILINNSTILLINGER -->
        <div class="settings-card">
            <h3>User Profile</h3>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email"
                       value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>" disabled>
            </div>
            <div class="form-group">
                <label for="availability">Availability</label>
                <select id="availability" name="availability">
                    <?php foreach($tillatteStatuser as $status): ?>
                        <option value="<?= htmlspecialchars($status) ?>"
                            <?= $gjeldendeStatus === $status ? 'selected' : '' ?>>
                            <?= htmlspecialchars($status) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
       
        <button type="submit" class="btn-primary">
            Save Availability
        </button>
        </div>
        <!-- VARSLER -->
        <div class="settings-card">
            <h3>Notifications</h3>
            <div class="toggle-row">
                <div>
                    <div class="toggle-label">Email Notifications</div>
                    <div class="toggle-desc">Receive updates when equipment is assigned</div>
                </div>
                <input type="checkbox" name="email_notif" checked style="transform: scale(1.2);">
            </div>
            <div class="toggle-row">
                <div>
                    <div class="toggle-label">Office Alerts</div>
                    <div class="toggle-desc">Get notified about status changes in offices</div>
                </div>
                <input type="checkbox" name="office_notif" checked style="transform: scale(1.2);">
            </div>
        </div>

        <!-- SIKKERHET -->
        <div class="settings-card">
        <h3>Change Password</h3>

        <div class="form-group">
            <label for="current_pass">Current Password</label>
            <input type="password" id="current_pass" name="current_pass">
        </div>

        <div class="form-group">
            <label for="new_pass">New Password</label>
            <input type="password" id="new_pass" name="new_pass">
        </div>

        <div class="form-group">
            <label for="confirm_pass">Confirm New Password</label>
            <input type="password" id="confirm_pass" name="confirm_pass">
        </div>

        <button type="submit" name="change_password" value="1"
                class="btn-primary">
            Update Password
        </button>
        </div>
    </form>
</div>

<?php require 'footer.php'; ?>
