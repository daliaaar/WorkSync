<!DOCTYPE html>
<html lang="no">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>WorkSync Settings</title>
<style>

/* --- GLOBAL --- */
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background-color: #f7f7f7;
    color: #333;
}

/* --- SIDEBAR --- */
.sidebar {
    width: 220px;
    height: 100vh;
    background-color: #1e3a5f;
    color: white;
    position: fixed;
    left: 0;
    top: 0;
    padding: 20px;
    display: flex;
    flex-direction: column;
}

.sidebar-logo {
    width: 100%;
    text-align: center;
    margin-bottom: 30px;
}

.sidebar-logo img {
    width: 140px;
    height: auto;
}

.sidebar a {
    display: block;
    color: white;
    text-decoration: none;
    margin: 10px 0;
    font-size: 18px;
}

.sidebar a.active {
    background-color: #294a75;
    padding: 10px;
    border-radius: 6px;
}

.sidebar-footer {
    margin-top: auto;
    text-align: center;
    font-size: 12px;
    color: #a0b2c6;
    padding-top: 15px;
    border-top: 1px solid #294a75;
}

/* --- TOP HEADER --- */
.top-header {
    margin-left: 220px;
    height: 70px;
    background-color: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 25px;
    border-bottom: 1px solid #ddd;
}

.search-box input {
    padding: 8px 12px;
    width: 250px;
    border: 1px solid #ccc;
    border-radius: 6px;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.user-status {
    width: 12px;
    height: 12px;
    background-color: #4CAF50;
    border-radius: 50%;
}

/* --- SETTINGS SECTION --- */
.settings-container {
    margin-left: 220px;
    padding: 30px;
    max-width: 900px;
}

.settings-title {
    margin-top: 0;
    margin-bottom: 25px;
    color: #1e3a5f;
    font-size: 24px;
}

.settings-card {
    background-color: white;
    border-radius: 10px;
    padding: 25px;
    margin-bottom: 20px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.settings-card h3 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #1e3a5f;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    font-weight: bold;
    margin-bottom: 5px;
    font-size: 14px;
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="password"],
.form-group select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 14px;
}

.toggle-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
}

.toggle-row:last-child {
    border-bottom: none;
}

.toggle-label {
    font-size: 14px;
    font-weight: bold;
}

.toggle-desc {
    font-size: 12px;
    color: #666;
    margin-top: 2px;
}

.btn-primary {
    background-color: #1e3a5f;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 6px;
    font-size: 15px;
    cursor: pointer;
    font-weight: bold;
}

.btn-primary:hover {
    background-color: #294a75;
}

</style>
</head>

<body>

<div class="sidebar">
    <div class="sidebar-logo">
        <img src="WorkSync.png" alt="WorkSync Logo">
    </div>

    <a href="dashboard.php">Dashboard</a>
    <a href="office.php">Offices</a>
    <a href="employees.php">Employees</a>
    <a href="equipment.php">Equipment</a>
    <a href="reports.php">Reports</a>
    <a href="settings.php" class="active">Settings</a>

    <footer class="sidebar-footer">
        &copy; 2026 WorkSync.<br>All rights reserved.
    </footer>
</div>

<!-- TOP HEADER -->
<div class="top-header">
    <div class="search-box">
        <input type="text" placeholder="Search...">
    </div>

    <div class="user-info">
        <div class="user-status"></div>
        <span>Jens</span>
    </div>
</div>

<!-- SETTINGS CONTENT -->
<div class="settings-container">
    <h2 class="settings-title">System & Profile Settings</h2>

    <form action="settings.php" method="POST">
        
        <!-- PROFILINNS TILLINGER -->
        <div class="settings-card">
            <h3>User Profile</h3>
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" value="Jens Hansen" disabled>
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="jens@worksync.no" disabled>
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

</body>
</html>