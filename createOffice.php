<?php
$pageTitle  = "Create Office";
$activePage = "createOffice";
require __DIR__ . '/header.php';

if($role === 'employee') {
    http_response_code(403);
    echo '<div class="dashboard"><p class="badge busy">You do not have permission to access this page</p></div>';
    require 'footer.php';
    exit;
}

$backLink = 'office.php';
$errors = [];
$action = $_GET['action'] ?? '';


$employeesWithoutOffice = [];
$result = $conn->prepare("SELECT AnsattId, AnsattEpost FROM EMPLOYEE WHERE KontorId IS NULL ORDER BY AnsattEpost");
$result->execute();
$resultEmployee = $result->get_result();
while ($row = $resultEmployee->fetch_assoc()) {
    $employeesWithoutOffice[] = $row;
}

if($action === 'new' && $_SERVER['REQUEST_METHOD'] === 'POST'){
    $chosenEmployeeId = (int) ($_POST['employee'] ?? 0);

    $ifEmployeeExiasts = false;
    foreach ($employeesWithoutOffice as $employee) {
        if ($employee['AnsattId'] === $chosenEmployeeId) {
            $ifEmployeeExiasts = true;
            break;
        }
    }
    if (!$ifEmployeeExiasts) {
        $errors[] = "Choose an employee that does not have an office assigned.";
    }
    if(!$errors){
        $conn->begin_transaction();
        try {
            $createOfficestmt = $conn->prepare("INSERT INTO OFFICE () VALUES ()");
            $createOfficestmt->execute();
            $officeId = $conn->insert_id;

            $assignEmployeeStmt = $conn->prepare("UPDATE EMPLOYEE SET KontorId = ? WHERE AnsattId = ? AND KontorId IS NULL");
            $assignEmployeeStmt->bind_param("ii", $officeId, $chosenEmployeeId);
            $assignEmployeeStmt->execute();

            if ($assignEmployeeStmt->affected_rows !== 1) {
                $conn->rollback();
                $errors[] = "Employee has already been assigned an office. Please choose another employee.";
            } else {
                $conn->commit();
                header('Location: ' . $backLink . '?created=1');
                exit;
            }
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "An error occurred while creating the office. Please try again later.";
        }
    }
}
?>
<div class="dashboard">
    <h1 class="page-title">Create Office</h1>
    <p class="page-subtitle">Create an office and assign it to an employee.</p>
    <?php foreach($errors as $error): ?>
        <p class="badge busy"><?= htmlspecialchars($error) ?></p>
    <?php endforeach; ?>

    <div class="panel">
        <h3>Create Office</h3>
        <?php if ($employeesWithoutOffice): ?>
        <form method="post" action="?action=new">
            <P>
                <label>Assign to Employee<br>
                    <select name="employee" required>
                        <option value="">-- Select an employee --</option>
                        <?php foreach ($employeesWithoutOffice as $employee): ?>
                            <option value="<?= htmlspecialchars($employee['AnsattId']) ?>"><?= htmlspecialchars($employee['AnsattEpost']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </P>
            <P>
                <button type="submit">Create Office</button>
                <a class="cancel" href="<?= htmlspecialchars($backLink) ?>">Cancel</a>
            </P>
        </form>
        <?php  else: ?>
            <p class="empty">All employees have been assigned an office. Register a new employee to create an office.</p>
            <p><a href="employees.php">+ Add new employee</a><a href="<?= htmlspecialchars($backLink) ?>">Cancel</a></p>
        <?php endif; ?>
    </div>
</div>
<?php require 'footer.php'; ?>


