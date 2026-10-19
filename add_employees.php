<!DOCTYPE html>
<style>
    .employee-card {
    background: #ffffff;
    border-radius: 20px;
    padding: 32px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06);
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32px;
}

.panel-header h2 {
    margin: 0;
    font-size: 1.6rem;
    font-weight: 700;
    color: #111827;
}

.panel-header p {
    margin-top: 6px;
    margin-bottom: 0;
    color: #6b7280;
    font-size: 0.95rem;
}

.back-btn {
    text-decoration: none;
    color: #374151;
    background: #f3f4f6;
    padding: 10px 18px;
    border-radius: 10px;
    font-weight: 600;
    transition: .2s ease;
}

.back-btn:hover {
    background: #e5e7eb;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 22px;
}

.field {
    display: flex;
    flex-direction: column;
}

.field label {
    margin-bottom: 8px;
    font-size: 14px;
    font-weight: 600;
    color: #374151;
}

.field input {
    height: 48px;
    padding: 0 14px;
    border: 1px solid #d1d5db;
    border-radius: 12px;
    font-size: 15px;
    transition: all .2s ease;
    background: #fff;
}

.field input:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
}

.field input::placeholder {
    color: #9ca3af;
}

.form-actions {
    margin-top: 30px;
    display: flex;
    gap: 12px;
}

.save-btn {
    background: #2563eb;
    color: white;
    border: none;
    border-radius: 12px;
    padding: 12px 26px;
    font-weight: 600;
    cursor: pointer;
    transition: .2s ease;
}

.save-btn:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
}

.cancel-btn {
    text-decoration: none;
    background: #f3f4f6;
    color: #374151;
    padding: 12px 26px;
    border-radius: 12px;
    font-weight: 600;
    transition: .2s ease;
}

.cancel-btn:hover {
    background: #e5e7eb;
}

.msg {
    margin-bottom: 20px;
    border-radius: 12px;
    padding: 14px 18px;
}

.msg.ok {
    background: #ecfdf5;
    border: 1px solid #10b981;
    color: #065f46;
}

.msg.err {
    background: #fef2f2;
    border: 1px solid #ef4444;
    color: #991b1b;
}

@media (max-width: 768px) {

    .panel-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .form-actions {
        flex-direction: column;
    }

    .save-btn,
    .cancel-btn {
        width: 100%;
        text-align: center;
    }
}
</style>



<?php
ob_start(); 

$pageTitle  = "Add employee";
$activePage = "employees";
require __DIR__ . '/header.php';

/* Bare manager og admin får opprette ansatte */
requireRole(['admin', 'manager']);

/* Minste tillatte passordlengde (samme krav som i register.php) */
$minPassordLengde = 16;

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$error = "";
$email = "";
$phone = "";
$position = "";
$department = "";
$saved = isset($_GET['saved']);

/* --- LAGRE NY ANSATT -- */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email           = trim($_POST['email'] ?? '');
    $phone           = trim($_POST['phone'] ?? '');
    $position        = trim($_POST['position'] ?? '');
    $department      = trim($_POST['department'] ?? '');
    $password        = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['confirmPassword'] ?? '';


    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $error = "Invalid form submission. Please try again.";
    } elseif ($email === '' || $password === '') {
        $error = "Email and password are required";
    } elseif (strlen($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif ($phone !== '' && !preg_match('/^[0-9+() -]{5,20}$/', $phone)) {
        $error = "Invalid phone number";
    } elseif (strlen($position) > 100 || strlen($department) > 100){
        $error = "Position and department must be at most 100 characters";
    } elseif (strlen($password) < $minPassordLengde) {
        $error = "Password must be at least $minPassordLengde characters";
    } elseif ($password !== $passwordConfirm) {
        $error = "Passwords do not match";
    } elseif (!$conn) {
        $error = "Database connection is not available";
    } else {
        try {
            /* Er e-posten allerede i bruk (admin, manager eller ansatt)? */
            $stmt = $conn->prepare(
                "SELECT AdminId FROM ADMINISTRATOR WHERE Epost = ?
                 UNION
                 SELECT BedriftsId FROM COMPANYMANAGER WHERE Epost = ?
                 UNION
                 SELECT AnsattId FROM EMPLOYEE WHERE AnsattEpost = ?"
            );
            $stmt->bind_param("sss", $email, $email, $email);
            $stmt->execute();
            $finnes = $stmt->get_result()->num_rows > 0;
            $stmt->close();

            if ($finnes) {
                $error = "Email already exists";
            } else {
                $hash       = password_hash($password, PASSWORD_DEFAULT);
                $phoneDb    = $phone !== '' ? $phone : null;

                $stmt = $conn->prepare(
                    "INSERT INTO EMPLOYEE (AnsattEpost, AnsattPassord, AnsattTelefonnr)
                     VALUES (?, ?, ?)"
                );
                $stmt->bind_param("sss", $email, $hash, $phoneDb);
                $stmt->execute();
                $stmt->close();

                header("Location: add_employees.php?saved=1");
                exit();
            }
        } catch (mysqli_sql_exception $e) {
            error_log('Add employee: ' . $e->getMessage());
            $error = "Could not save. Please try again later.";
        }
    }
}
?>

<div class="dashboard">

    <h1 class="page-title">Add employee</h1>
    <p class="page-subtitle">Create a login for a new employee</p>

    <?php if ($saved): ?>
        <div class="msg ok">Employee added. They can now log in with the email and password you set.</div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="msg err"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="panel">
      <?php
ob_start(); 

$pageTitle  = "Add employee";
$activePage = "employees";
require __DIR__ . '/header.php';

/* Bare manager og admin får opprette ansatte */
requireRole(['admin', 'manager']);

/* Minste tillatte passordlengde (samme krav som i register.php) */
$minPassordLengde = 16;

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$error = "";
$email = "";
$phone = "";
$position = "";
$department = "";
$saved = isset($_GET['saved']);

/* --- LAGRE NY ANSATT -- */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email           = trim($_POST['email'] ?? '');
    $phone           = trim($_POST['phone'] ?? '');
    $position        = trim($_POST['position'] ?? '');
    $department      = trim($_POST['department'] ?? '');
    $password        = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['confirmPassword'] ?? '';


    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $error = "Invalid form submission. Please try again.";
    } elseif ($email === '' || $password === '') {
        $error = "Email and password are required";
    } elseif (strlen($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif ($phone !== '' && !preg_match('/^[0-9+() -]{5,20}$/', $phone)) {
        $error = "Invalid phone number";
    } elseif (strlen($position) > 100 || strlen($department) > 100){
        $error = "Position and department must be at most 100 characters";
    } elseif (strlen($password) < $minPassordLengde) {
        $error = "Password must be at least $minPassordLengde characters";
    } elseif ($password !== $passwordConfirm) {
        $error = "Passwords do not match";
    } elseif (!$conn) {
        $error = "Database connection is not available";
    } else {
        try {
            /* Er e-posten allerede i bruk (admin, manager eller ansatt)? */
            $stmt = $conn->prepare(
                "SELECT AdminId FROM ADMINISTRATOR WHERE Epost = ?
                 UNION
                 SELECT BedriftsId FROM COMPANYMANAGER WHERE Epost = ?
                 UNION
                 SELECT AnsattId FROM EMPLOYEE WHERE AnsattEpost = ?"
            );
            $stmt->bind_param("sss", $email, $email, $email);
            $stmt->execute();
            $finnes = $stmt->get_result()->num_rows > 0;
            $stmt->close();

            if ($finnes) {
                $error = "Email already exists";
            } else {
                $hash       = password_hash($password, PASSWORD_DEFAULT);
                $phoneDb    = $phone !== '' ? $phone : null;

                $stmt = $conn->prepare(
                    "INSERT INTO EMPLOYEE (AnsattEpost, AnsattPassord, AnsattTelefonnr)
                     VALUES (?, ?, ?)"
                );
                $stmt->bind_param("sss", $email, $hash, $phoneDb);
                $stmt->execute();
                $stmt->close();

                header("Location: add_employees.php?saved=1");
                exit();
            }
        } catch (mysqli_sql_exception $e) {
            error_log('Add employee: ' . $e->getMessage());
            $error = "Could not save. Please try again later.";
        }
    }
}
?>

<div class="dashboard">

    <h1 class="page-title">Add employee</h1>
    <p class="page-subtitle">Create a login for a new employee</p>

    <?php if ($saved): ?>
        <div class="msg ok">Employee added. They can now log in with the email and password you set.</div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="msg err"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="panel">
        <h3>
            New employee
            <a href="employees.php">Back to employees</a>
        </h3>

        <form method="POST" action="add_employees.php" class="report-form">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">

            <label for="email">Email</label>
            <input type="email" id="email" name="email" maxlength="255" required
                   placeholder="name@company.com"
                   value="<?= htmlspecialchars($email) ?>">

            <label for="phone">Phone number (optional)</label>
            <input type="tel" id="phone" name="phone" maxlength="20"
                   placeholder="+47 123 45 678"
                   value="<?= htmlspecialchars($phone) ?>">

            <label for="position">Position </label>
            <input type="text" id="position" name="position" maxlength="100"
                    placeholder="Developer"
                    value="<?= htmlspecialchars($position) ?>">
            
            <label for="department">Department</label>
            <input type="text" id="department" name="department" maxlength="100"
                    placeholder="IT"
                    value="<?= htmlspecialchars($department) ?>">

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required
                   minlength="<?= (int) $minPassordLengde ?>"
                   placeholder="At least <?= (int) $minPassordLengde ?> characters"
                   autocomplete="new-password">

            <label for="confirmPassword">Confirm password</label>
            <input type="password" id="confirmPassword" name="confirmPassword" required
                   autocomplete="new-password">

            <button type="submit">Add employee</button>
            <a class="cancel" href="employees.php">Cancel</a>
        </form>
    </div>

</div>

<?php require __DIR__ . '/footer.php'; ?>
    </div>

</div>

<?php require __DIR__ . '/footer.php'; ?>