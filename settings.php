<?php
/* WorkSync – Settings */

$pageTitle  = "Settings";
$activePage = "settings";
require 'header.php';
?>

<!-- SETTINGS CONTENT -->
<div class="settings-container">
    <h2 class="settings-title">System &amp; Profile Settings</h2>

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
                    <option>Available</option>
                    <option>Busy</option>
                    <option>Do not disturb</option>
                    <option>Away</option>
                    <option>Offline</option>
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
s