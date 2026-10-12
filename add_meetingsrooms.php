<?php
$pageTitle  = "Add meeting rooms";
$activePage = "Meetingrooms";
require __DIR__ . '/header.php';

if($role === 'employee') {
    http_response_code(403);
    echo '<div class="dashboard"><p class="badge busy">You do not have permission to access this page</p></div>';
    exit;
}

$error = "";
$name = "";
$capacity = "";
$saved = isset($_GET['saved']);

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $name = trim($_POST['name'] ?? '');
    $capacity = trim($_POST['capacity'] ?? '');
    
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $error = "Invalid form submission. Please try again.";
    } elseif ($name === '' || strlen($name) > 100){
        $error = "Name must be less than 100 characters";
    } elseif ($capacity === '' && (!ctype_digit($capacity) || (int) $capacity < 1)){
        $error = "Capacity must at least be 1";
    } elseif (!$conn){
        $error = "Error in database connection.";
    } else {
        try{
            $stmt = $conn->prepare("INSERT INTO MEETINGROOM (Navn, Kapasitet) VALUES (?, ?)");
            $stmt->bind_param("si", $name, $capacity);
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
    <h1 class="page-title">Add a meeting room</h1>
    <p class="page-subtitle">Add a meeting room that managers and employees can book</p>
    <?php if($saved): ?>
        <div class="msg ok"> Meeting room added successfully.</div>
    <?php endif; ?> 

    <?php if($error !== ''): ?>
        <div class="msg err"><?= htmlspecialchars($error)?></div>
    <?php endif; ?>

    <div class="panel">
        <h3>New meeting room<a href="offices.php">Back to offices</a>
    </h3>

    <form method="POST" action="add_meetingrooms.php" class="report-form">
        <input type="hidden" name="csrf" values=<?= 


<?php require __DIR__ . '/footer.php'; ?>
