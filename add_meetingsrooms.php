<?php
$pageTitle  = "Create meeting rooms";
$activePage = "Meetingrooms";
require __DIR__ . '/header.php';

if($role === 'employee') {
    http_response_code(403);
    echo '<div class="dashboard"><p class="badge busy">You do not have permission to access this page</p></div>';
    exit;
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$error = "";
$name = "";
$capacity = "";
$building  = "";
$floor = "";
$department = "";
$saved = isset($_GET['saved']);

$buildings = [];
$departments = [];

if ($conn) {
    $rows = fetchRows($conn,
            "SELECT Bygning FROM OFFICE WHERE Bygning IS NOT NULL AND
            Bygning <> '' UNION
            SELECT Bygning FROM MEETINGROOM WHERE Bygning IS NOT NULL AND Bygning <> ''
            ORDER BY Bygning ASC");
    $buildings = array_column($rows,'Bygning');
    $rows = fetchRows($conn,
            "SELECT Avdeling FROM OFFICE WHERE Avdeling IS NOT NULL AND Avdeling <> ''
            UNION
            SELECT Avdeling FROM MEETINGROOM WHERE Avdeling IS NOT NULL AND Avdeling <> '' 
            ORDER BY Avdeling ASC");
    $departments = array_column($rows, 'Avdeling');
}

function matchExisting(string $value, array $existing): string {
    foreach ($existing as $e) {
        if (mb_strtolower($e) === mb_strtolower($value)) {
            return $e;
        }
    }
    return $value; 
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $name = trim($_POST['name'] ?? '');
    $capacity = trim($_POST['capacity'] ?? '');
    $building = matchExisting(trim($_POST['building'] ?? ''), $buildings);
    $floor = trim($_POST['floor'] ?? '');
    $department = matchExisting(trim($_POST['department'] ?? ''), $departments);
    
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $error = "Invalid form submission. Please try again.";
    } elseif ($name === '' || strlen($name) > 100){
        $error = "Name must be less than 100 characters";
    } elseif ($capacity === '' && (!ctype_digit($capacity) || (int) $capacity < 1)){
        $error = "Capacity must at least be 1";
    } elseif ($building === '' || strlen($building) > 50){
        $error  = "Building is required and must be less than 50 characters";
    } elseif ($floor === '' || strlen($floor) > 10){
        $error = "Floor is required and must be less than 10 characters";
    } elseif ($department === '' || strlen($department) > 100){
        $error = "Department is required and must be less than 100 characters";
    } elseif (!$conn){
        $error = "Error in database connection.";
    } else {
        $capacityVal = $capacity === '' ? null : (int) $capacity;
      
        try{
            $stmt = $conn->prepare("INSERT INTO MEETINGROOM (Navn, Kapasitet, Bygning, Etasje, Avdeling) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sisss", $name, $capacityVal, $building, $floor, $department);
            $stmt->execute();
            $stmt->close();

            header("Location: add_meetingsrooms.php?saved=1");
            exit();
        } catch (mysqli_sql_exception $e) {
            error_log('Add meeting room: ' . $e->getMessage());
            $error = "Could not save. Please try again later.";
        }

    }
}
?>
<div class="dashboard">
    <h1 class="page-title">Create a meeting room</h1>
    <p class="page-subtitle">Create a meeting room that managers and employees can book</p>
    <?php if($saved): ?>
        <div class="msg ok"> Meeting room added successfully.</div>
    <?php endif; ?> 

    <?php if($error !== ''): ?>
        <div class="msg err"><?= htmlspecialchars($error)?></div>
    <?php endif; ?>

    <div class="panel">
        <h3>New meeting room<a href="office.php">Back to offices</a>
    </h3>

    <form method="POST" action="add_meetingsrooms.php" class="report-form">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'])?>">

        <label for="name">Room name</label>
        <input type="text" id="name" name="name" maxlength=100 required 
                placeholder="Boardroom" value="<?= htmlspecialchars($name)?>">

        <label for="capacity">Capacity (Optional)</label>
        <input type="number" id="capacity" name="capacity" 
                placeholder="20" value="<?= htmlspecialchars($capacity)?>">

        <label for="building">Building</label>
        <input type="text" id="building" name="building" maxlength="50" required
                list="building-list" placeholder="Building A"
                value="<?= htmlspecialchars($building) ?>">
        <datalist id="building-list">
                <?php foreach($buildings as $b): ?>
                    <option value="<?= htmlspecialchars($b) ?>">
                <?php endforeach; ?>
        </datalist>

        <label for="floor">Floor</label>
        <input type="text" id="floor" name="floor" maxlength="10" required
                    placeholder="2" value="<?= htmlspecialchars($floor)?>">

        <label for="department">Department</label>
        <input type="text" id="department" name="department" maxlength="100" required
                    list="department-list" placeholder="Finance"
                    value="<?= htmlspecialchars($department) ?>">
        <datalist id="department-list">
                    <?php foreach($departments as $d): ?>
                        <option value="<?= htmlspecialchars($d) ?>">
                    <?php endforeach; ?>
        </datalist>
        <button type="submit">Create meeting room </button>
        <a class="cancel" href="office.php">Cancel</a>

    </form>
    </div>
</div>
    


<?php require __DIR__ . '/footer.php'; ?>
