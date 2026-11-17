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
$firstname = "";
$lastname = "";
$email = "";
$phone = "";
$position = "";
$department = "";
$saved = isset($_GET['saved']);

/* --- LAGRE NY ANSATT -- */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $firstname       = trim($_POST['firstname'] ?? '');
    $lastname        = trim($_POST['lastname'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $phone           = trim($_POST['phone'] ?? '');
    $position        = trim($_POST['position'] ?? '');
    $department      = trim($_POST['department'] ?? '');
    $password        = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['confirmPassword'] ?? '';

    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $error = "Invalid form submission. Please try again.";
    } elseif ($minBedriftsId === 0) {
        $error = "No company found for your account";
    } elseif ($firstname === '') {
        $error = "First name is required";
    } elseif (strlen($firstname) > 100) {
        $error = "First name must be at most 100 characters";
    } elseif ($lastname === '') {
        $error = "Last name is required";
    } elseif (strlen($lastname) > 100) {
        $error = "Last name must be at most 100 characters";
    } elseif ($email === '') {
        $error = "Email is required";
    } elseif (strlen($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif ($phone !== '' && !preg_match('/^[0-9+() -]{5,20}$/', $phone)) {
        $error = "Invalid phone number";
    } elseif (strlen($position) > 100 || strlen($department) > 100){
        $error = "Position and department must be at most 100 characters";
    } elseif ($password === '') {
        $error = "Password is required";
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
                $hash         = password_hash($password, PASSWORD_DEFAULT);
                $phoneDb      = $phone !== '' ? $phone : null;
                $positionDb   = $position !== '' ? $position : null;
                $departmentDb = $department !== '' ? $department : null;

                /* BedriftsId kommer alltid fra innloggingen, aldri fra skjemaet */
                $stmt = $conn->prepare(
                    "INSERT INTO EMPLOYEE (Fornavn, Etternavn, AnsattEpost, AnsattPassord, AnsattTelefonnr, Stilling, Avdeling, BedriftsId)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->bind_param("sssssssi", $firstname, $lastname, $email, $hash, $phoneDb, $positionDb, $departmentDb, $minBedriftsId);
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
            <label for="firstname">First name</label>
            <input type="text" id="firstname" name="firstname" maxlength="100" required
                placeholder="First name"
                value="<?= htmlspecialchars($firstname) ?>">

            <label for="lastname">Last name</label>
            <input type="text" id="lastname" name="lastname" maxlength="100" required
                placeholder="Last name"
                value="<?= htmlspecialchars($lastname) ?>">

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