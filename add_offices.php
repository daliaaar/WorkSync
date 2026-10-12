<?php
$pageTitle  = "Add offices";
$activePage = "createOffice";
require __DIR__ . '/header.php';

if($role === 'employee') {
    http_response_code(403);
    echo '<div class="dashboard"><p class="badge busy">You do not have permission to access this page</p></div>';
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

    // setter 0 som betyr at manager ikke har valgt noen ansatte. 
    $chosenEmployeeId = (int) ($_POST['employee'] ?? 0);
    $assignEmployee = $chosenEmployeeId > 0;

if($assignEmployee){
    $ifEmployeeExiasts = false;
    foreach ($employeesWithoutOffice as $employee) {
        if ((int) $employee['AnsattId'] === $chosenEmployeeId) {
            $ifEmployeeExiasts = true;
            break;
        }
    }
    if (!$ifEmployeeExiasts) {
        $errors[] = "Choose an employee that does not have an office assigned.";
    }
}
    if(!$errors){
        $conn->begin_transaction();
        try {
            $createOfficestmt = $conn->prepare("INSERT INTO OFFICE () VALUES ()");
            $createOfficestmt->execute();
            $officeId = $conn->insert_id;

            $officeSaved = true;

            if($assignEmployee){
            $assignEmployeeStmt = $conn->prepare("UPDATE EMPLOYEE SET KontorId = ? WHERE AnsattId = ? AND KontorId IS NULL");
            $assignEmployeeStmt->bind_param("ii", $officeId, $chosenEmployeeId);
            $assignEmployeeStmt->execute();

            if ($assignEmployeeStmt->affected_rows !== 1) {
                $officeSaved = false;
            }
        }
            if($officeSaved){
                $conn->commit();
                header('Location: '. $backLink . '?created=1');
                exit;
            } else{
                $conn->rollback();
                $errors[] = "This employee has already been assigned an office, please select another employee.";
            }
        } catch(Exception $e){
            $conn->rollback();
            $errors[] = "An error occured, please try again later.";
        }
    }
}
?>
<div class="dashboard">
    <h1 class="page-title">Create Office</h1>
    <p class="page-subtitle">Create an office. You can assign it to an employee.</p>
    <?php foreach($errors as $error): ?>
        <p class="badge busy"><?= htmlspecialchars($error) ?></p>
    <?php endforeach; ?>

    <div class="panel">
        <h3>Create Office</h3>
        <form method="post" action="?action=new">
             <?php if ($employeesWithoutOffice): ?>
            <p>
                <label>Assign to Employee (optional)<br>
                    <select name="employee">
                        <option value="">-- Select an employee (leave unassigned)--</option>
                        <?php foreach ($employeesWithoutOffice as $employee): ?>
                            <option value="<?= (int)$employee['AnsattId'] ?>">
                                <?= htmlspecialchars($employee['AnsattEpost']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
             </p>

            <?php else: ?>
                <p class="empty">
                    All employees have been assigned an office.
                    <a href="add_employees.php">Add new employee</a>
            </p>
            <?php endif; ?>
            <p>
                <button type="submit">Create Office</button>
                <a class="cancel" href="<?= htmlspecialchars($backLink) ?>">Cancel</a>
            </p>
        </form>
    </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>


