<?php
$pageTitle  = "Create offices";
$activePage = "createOffice";
require __DIR__ . '/header.php';
date_default_timezone_set('Europe/Oslo');

if($role === 'employee') {
    http_response_code(403);
    echo '<div class="dashboard"><p class="badge busy">You do not have permission to access this page</p></div>';
    exit;
}

$backLink = 'office.php';
$errors = [];
$action = $_GET['action'] ?? '';

$avdeling = '';
$etasje = '';
$bygning = '';

$employeesWithoutOffice = [];
$result = $conn->prepare("SELECT AnsattId, AnsattEpost FROM EMPLOYEE WHERE KontorId IS NULL ORDER BY AnsattEpost");
$result->execute();
$resultEmployee = $result->get_result();
while ($row = $resultEmployee->fetch_assoc()) {
    $employeesWithoutOffice[] = $row;
}

$bygningForslag = fetchRows(
    $conn, "SELECT DISTINCT Bygning FROM OFFICE WHERE Bygning IS NOT NULL AND Bygning  <> '' ORDER BY Bygning ASC");
$avdelingsForslag = fetchRows(
    $conn, "SELECT DISTINCT Avdeling FROM OFFICE WHERE Avdeling IS NOT NULL AND Avdeling <> '' ORDER BY Avdeling ASC"
);

if($action === 'new' && $_SERVER['REQUEST_METHOD'] === 'POST'){

    $avdeling = trim($_POST['avdeling'] ?? '');
    $etasje = trim($_POST['etasje'] ?? '');
    $bygning = trim($_POST['bygning'] ?? '');

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
        
    $avdelingDb = $avdeling === '' ? null : $avdeling;
    $etasjeDb = $etasje === '' ? null : $etasje;
    $bygnigDb = $bygning === '' ? null : $bygning;
        $conn->begin_transaction();
        try {
            $createOfficestmt = $conn->prepare("INSERT INTO OFFICE (Avdeling, Etasje, Bygning) VALUES (?, ?, ?)");
            $createOfficestmt->bind_param("sss", $avdelingDb, $etasjeDb, $bygnigDb);
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
<style>
    .edit-form { max-width: 480px; }
    .edit-form label { 
        display: block; 
        font-size: 0.85rem;
        opacity: .85;
        margin-bottom: 12px; }
    .edit-form input,
    .edit-form select { 
        display: block;
        width: 100%;
        box-sizing: border-box;
        margin-top: 4px;
    }
</style>
<div class="dashboard">
    <h1 class="page-title">Create Office</h1>
    <p class="page-subtitle">Create an office. You can assign it to an employee or leave it unassigned</p>
    <?php foreach($errors as $error): ?>
        <p class="badge busy"><?= htmlspecialchars($error) ?></p>
    <?php endforeach; ?>

    <div class="panel">
        <h3>Create Office</h3>
        <form method="post" class="edit-form" action="?action=new">
        <label>Department
            <input type="text" name="avdeling" maxlength="100" list="avdeling-list" value="<?= htmlspecialchars($avdeling) ?>">
        </label>
        <datalist id="avdeling-list">
            <?php foreach ($avdelingForslag as $f): ?>
                <option value="<?= htmlspecialchars($f['Avdeling']) ?>">
            <?php endforeach; ?>
        </datalist>
        <label>Floor
            <input type="text" name="etasje" maxlength="20" 
            value="<?= htmlspecialchars($etasje) ?>">
        </label>
        <label>Building
            <input type="text" name="bygning" maxlength="100" list="bygning-list" 
            value="<?= htmlspecialchars($bygning) ?>">
        </label>
        <datalist id="bygning-list">
            <?php foreach ($bygningForslag as $f): ?>
                <option value="<?= htmlspecialchars($f['Bygning']) ?>">
            <?php endforeach; ?>
        </datalist>

             <?php if ($employeesWithoutOffice): ?>
                <label>Assign to Employee (optional)
                    <select name="employee">
                        <option value="">-- Select an employee (leave unassigned)--</option>
                        <?php foreach ($employeesWithoutOffice as $employee): ?>
                            <option value="<?= (int)$employee['AnsattId'] ?>">
                                <?= htmlspecialchars($employee['AnsattEpost']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

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


