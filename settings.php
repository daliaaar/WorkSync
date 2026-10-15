<?php
/* WorkSync – Settings */

$pageTitle  = "Settings";
$activePage = "settings";
require 'header.php';

$tillatteStatuser = ['Available', 'Busy', 'Do not disturb', 'Away', 'Offline'];
$epost = $_SESSION['email'] ?? '';
$melding = '';

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['availability'])){
    $nyStatus = $_POST['availability'];

    if($epost === ''){
        $melding = 'You must be logged in';
    } elseif (!in_array($nyStatus, $tillatteStatuser, true)){
        $melding = 'Invalid availability';
    } else {
        $lagreStatus = $conn->prepare("UPDATE EMPLOYEE SET Tilgjengelighet = ? WHERE AnsattEpost = ?");
        $lagreStatus->bind_param("ss", $nyStatus, $epost);
        $lagreStatus->execute();
        $melding = 'Settings saved';
    }
}

$gjeldendeStatus = 'Available';
$hentStatus = $conn->prepare("SELECT Tilgjengelighet FROM EMPLOYEE WHERE AnsattEpost = ?");
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
        </div>

        <button type="submit" class="btn-primary">Save Changes</button>
    </form>
</div>

<?php require 'footer.php'; ?>
