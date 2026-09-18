<?php
session_start();
require 'config.php'; // Sett inn tilkobling ($conn = mysqli connection)

$error = "";
$email = "";
$companyName = "";
$phone = "";
$managerName = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email        = trim($_POST['email'] ?? '');
    $companyName  = trim($_POST['companyName'] ?? '');
    $phone        = trim($_POST['phone'] ?? '');
    $managerName  = trim($_POST['managerName'] ?? '');
    $password        = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['confirmPassword'] ?? '';

    // Validering
    if (empty($email) || empty($password) || empty($companyName) || empty($phone) || empty($managerName)) {
        $error = "All fields are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif (strlen($password) < 16) {
        $error = "Password must be at least 16 characters";
    } elseif ($password !== $passwordConfirm) {
        $error = "Passwords do not match";
    } else {
        // Sjekker om e-posten allerede finnes
        $stmt = $conn->prepare(
            "SELECT AdminId AS userId, 'admin' AS role
             FROM Administrator
             WHERE Epost = ?

             UNION

             SELECT BedriftsId AS userId, 'manager' AS role
             FROM Bedriftsleder
             WHERE Epost = ?

             UNION

             SELECT AnsattId AS userId, 'employee' AS role
             FROM Ansatt
             WHERE AnsattEpost = ?"
        );
        $stmt->bind_param("sss", $email, $email, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Email already exists";
        } else {
            // Registrer ny bedriftsleder
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $insertStmt = $conn->prepare(
                "INSERT INTO Bedriftsleder (Epost, Passord, Telefonnr, BedriftsNavn, Navn)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $insertStmt->bind_param("sssss", $email, $hash, $phone, $companyName, $managerName);

            if ($insertStmt->execute()) {
                $success_message = "Company registered!";
            } else {
                $error = "Registration failed";
            }
            $insertStmt->close();
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="no">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create company account</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }
    body {
        font-family: 'Inter', 'times new roman', serif;
        background-color: #f4f6fb;
        min-height: 100vh;
    }

    .register-container {
        display: flex;
        min-height: 100vh;
    }
    /* Venstre side*/
    .info-section {
        width: 45%;
        background: linear-gradient(160deg, #3b7bff 0%, #2f6df6 45%, #1e4fd1 100%);
        color: white;
        padding: 60px 50px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }
    .info-section::after {
        content: "";
        position: absolute;
        width: 420px;
        height: 420px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.06);
        top: -140px;
        right: -140px;
        pointer-events: none;
    }
    .info-section h1 {
        font-family: 'Poppins', sans-serif;
        font-size: 46px;
        margin-bottom: 10px;
        font-weight: 700;
        letter-spacing: 0.5px;
        position: relative;
    }
    .info-section h2 {
        font-size: 16px;
        font-weight: 400;
        margin-bottom: 55px;
        letter-spacing: 2px;
        color: rgba(255, 255, 255, 0.85);
        position: relative;
    }
    .benefit {
        display: flex;
        align-items: center;
        gap: 22px;
        margin-bottom: 38px;
        position: relative;
        transition: transform 0.2s ease;
    }
    .benefit:hover {
        transform: translateX(4px);
    }
    .icon {
        width: 56px;
        height: 56px;
        min-width: 56px;
        border: 1px solid rgba(255, 255, 255, 0.35);
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        background-color: rgba(255, 255, 255, 0.12);
        backdrop-filter: blur(4px);
    }
    .benefit h3 {
        font-family: 'Poppins', sans-serif;
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 5px;
    }
    .benefit p {
        font-size: 14.5px;
        color: rgba(255, 255, 255, 0.85);
    }
    /* Høyre side */
    .form-section {
        width: 55%;
        padding: 55px 80px;
        background-color: #ffffff;
    }
    .form-section h1 {
        font-family: 'Poppins', sans-serif;
        font-size: 30px;
        font-weight: 700;
        margin-bottom: 8px;
        color: #1a1a2e;
    }
    .form-section > p.subtitle {
        color: #7a7f8c;
        font-size: 15px;
        margin-bottom: 30px;
    }
    .form-section h2 {
        font-family: 'Poppins', sans-serif;
        font-size: 15px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #2f6df6;
        margin-top: 28px;
        margin-bottom: 16px;
        padding-bottom: 8px;
        border-bottom: 2px solid #eef1f8;
    }
    /*Labels*/
    form label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        letter-spacing: 0.3px;
        color: #4a4f5c;
        margin-bottom: 6px;
    }
    /*Input*/
    input[type="text"],
    input[type="email"],
    input[type="tel"],
    input[type="password"] {
        width: 100%;
        height: 46px;
        border: 1.5px solid #e1e4ec;
        background-color: #f9fafc;
        border-radius: 10px;
        padding: 0 15px;
        font-size: 15px;
        font-family: 'Inter', sans-serif;
        margin-bottom: 18px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
    }
    input:focus {
        outline: none;
        border-color: #2f6df6;
        background-color: #ffffff;
        box-shadow: 0 0 0 4px rgba(47, 109, 246, 0.12);
    }
    /*Terms*/
    .terms {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        margin-top: 5px;
        margin-bottom: 28px;
    }
    .terms input {
        width: 18px;
        height: 18px;
        margin-top: 2px;
        accent-color: #2f6df6;
    }
    .terms label {
        font-size: 14px;
        font-weight: 400;
        color: #6b7080;
        line-height: 1.4;
    }
    /*button*/
    button {
        width: 100%;
        background: linear-gradient(135deg, #3b7bff, #2f6df6);
        border: none;
        color: white;
        padding: 14px 25px;
        font-size: 15px;
        font-weight: 600;
        letter-spacing: 0.5px;
        border-radius: 10px;
        cursor: pointer;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
        box-shadow: 0 6px 16px rgba(47, 109, 246, 0.28);
    }
    button:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 20px rgba(47, 109, 246, 0.35);
    }
    button:active {
        transform: translateY(0);
    }

    .error-message {
        background-color: #fff0f0;
        border: 1px solid #f3c2c2;
        color: #c0392b;
        padding: 12px 14px;
        margin-bottom: 20px;
        border-radius: 8px;
        font-size: 14px;
    }
    @media (max-width: 900px){
        .register-container {
            flex-direction: column;
        }
        .info-section,
        .form-section {
            width: 100%;
        }
        .info-section {
            padding: 30px;
        }
        .form-section {
            padding: 30px;
        }
    }
    </style>
</head>
<body>
<div class="register-container">
    <!-- Venstre side -->
    <div class="info-section">
        <h1>WORKSYNC</h1>
        <h2>A SMARTER DAY AT THE OFFICE</h2>
        <div class="benefit">
            <div class="icon">🏢</div>
            <div>
                <h3>Effective office administration</h3>
                <p>Gain full control over resources</p>
            </div>
        </div>
        <div class="benefit">
            <div class="icon">💻</div>
            <div>
                <h3>Optimal use of resources</h3>
                <p>Keep control of equipment and booking</p>
            </div>
        </div>
        <div class="benefit">
            <div class="icon">📊</div>
            <div>
                <h3>Data-driven desicions</h3>
                <p>Reports and insights in seconds</p>
            </div>
        </div>
        <div class="benefit">
            <div class="icon">👥</div>
            <div>
                <h3>Better cooperation</h3>
                <p>Create a tidier and more efficient workplace</p>
            </div>
        </div>
    </div>
    <!-- Høyre side -->
    <div class="form-section">
        <h1>CREATE YOUR COMPANY ACCOUNT</h1>
        <p class="subtitle">It only takes a minute to get your company set up.</p>
        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <div class="error-message" style="background-color:#e5ffe9; border-color:#0a8a2b; color:#0a6b21;">
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="">
            <h2>COMPANY INFORMATION</h2>
            <label for="companyName">COMPANY NAME</label>
            <input
                type="text"
                id="companyName"
                name="companyName"
                placeholder="Enter company name"
                value="<?= htmlspecialchars($companyName) ?>"
                required
            >

            <label for="phone">PHONE NUMBER</label>
            <input
                type="tel"
                id="phone"
                name="phone"
                placeholder="Enter phone number"
                value="<?= htmlspecialchars($phone) ?>"
                required
            >
            <!-- MANAGER -->
            <h2>CREATE MANAGER ACCOUNT</h2>
            <label for="managerName">YOUR NAME</label>
            <input
                type="text"
                id="managerName"
                name="managerName"
                placeholder="Enter your name"
                value="<?= htmlspecialchars($managerName) ?>"
                required
            >
            <label for="email">EMAIL</label>
            <input
                type="email"
                id="email"
                name="email"
                placeholder="Enter your email"
                value="<?= htmlspecialchars($email) ?>"
                required
            >
            <label for="password">PASSWORD</label>
            <input
                type="password"
                id="password"
                name="password"
                placeholder="Enter password"
                required
            >
            <label for="confirmPassword">CONFIRM PASSWORD</label>
            <input
                type="password"
                id="confirmPassword"
                name="confirmPassword"
                placeholder="Confirm password"
                required
            >
            <!-- Vilkår -->
            <div class="terms">
                <input
                    type="checkbox"
                    id="terms"
                    name="terms"
                >
                <label for="terms">
                    I AGREE TO THE TERMS<br>
                    AND CONDITIONS
                </label>
            </div>

            <button type="submit">
                CREATE ACCOUNT
            </button>
        </form>
    </div>
</div>
</body>
</html>
