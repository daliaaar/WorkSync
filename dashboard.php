<!DOCTYPE html>
<html lang="no">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>WorkSync Dashboard</title>

<style>

/* --- GLOBAL --- */
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background-color: #f7f7f7;
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
    /* Flexbox for å dytte footeren nederst */
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

/* Plasserer footeren helt i bunn av menyen */
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

/* --- DASHBOARD --- */
.dashboard {
    margin-left: 220px;
    padding: 20px;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

.info-box {
    background-color: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    height: 120px;
}

</style>
</head>

<body>

<div class="sidebar">
    <div class="sidebar-logo">
        <img src="Images/WorkSyncLogo.png" alt="WorkSync Logo">
    </div>

    <!-- PÅ DASHBOARD-SIDEN ER DASHBOARD ACTIVE -->
    <a href="dashboard.php" class="active">Dashboard</a>
    <a href="office.php">Offices</a>
    <a href="employees.php">Employees</a>
    <a href="equipment.php">Equipment</a>
    <a href="reports.php">Reports</a>
    <a href="settings.php">Settings</a>

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
        <span>Jens (Admin)</span>
    </div>
</div>

<!-- DASHBOARD BOXES -->
<div class="dashboard">
    <div class="info-box">Info boks 1</div>
    <div class="info-box">Info boks 2</div>
    <div class="info-box">Info boks 3</div>
    <div class="info-box">Info boks 4</div>
    <div class="info-box">Info boks 5</div>
    <div class="info-box">Info boks 6</div>
</div>

<script>
    const links = document.querySelectorAll('.sidebar a');

    links.forEach(link => {
        link.addEventListener('click', () => {
            links.forEach(l => l.classList.remove('active'));
            link.classList.add('active');
        });
    });
</script>

</body>
</html>