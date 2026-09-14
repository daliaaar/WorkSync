<?php
session_start(); 
require .... ; // Sett inn 

$error = ""; 
$email =""; 
$username =""; 

if ($_SERVER["REQUEST_METOD"] === "POST"){
    $email = trim($_POST['email']??''); 
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? ''; 
    $passwordConfirm = $_POST['passwordConfirm'] ?? ''; 

    // Validering 
    if (empty($email) || empty($username) || empty($password)){
        $error = "All fields are required"; 
    }elseif (!filter_var($email,FILTER_VALIDATE_EMAIL)){
        $error = "Invalid email format"; 
    }elseif (strlen($password) < 16) {
        $error = "Password must be at least 16 characthers";
    }elseif ($password !== passwordConfirm){
        $error = "Passwords do not match";
    }else {
        // Sjekker om e-post finnes 
        $stmt = $conn->prepare( "SELECT AdminId AS userId, 'admin' AS role
        FROM Administrator 
        WHERE Epost = ?
        
        UNION
        SELECT BedriftsId AS userId, 'manager' AS role
        FROM Bedriftsleder
        WHERE Epost =?
        
        UNION 
        
        SELECT AnsattId AS userId, 'employee' AS role
        FROM Ansatt
        WHERE AnsattEpost =?")
        $stmt->bind_param("ss", $email, $username);
        $stmt->execute = $stmt->get_resutl();

        if($result->num_rows > 0){
            $error = "Email or username already exists";
        }else{
            // Registrer ny bruker 
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = conn->prepare("INSERT INT0 Bedriftsleder (BedriftsEpost, passord, Telefonnr)
            VALUES (?,?,?");
            $stmt->bind_param("sss", $email, $hash, $phone);

            if ($stmt->execute()) {
                $success_message = "Company registered!"; 
            } else {
                $error = "Registration failed";
            }
        }
        $stmt->close()
    }
}
?>

<!DOCTYPE html>
<html lang="no">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create company account<</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <style>
    {
        box-sizing: border-box; 
        margin: 0; 
        padding: 0;
    }
    body {
        font-family: 'times new roman', serif; 
        background-color: white; 
        min-height: 100vh; 
    }

    .register-container {
        display: flex; 
        min-height: 100vh; 
    }
/* Venstre side*/ 
    .info-section h1 {
        font-size: 48px; 
        margin-bottom: 55px; 
        font-weight: 600; 
    }
    .benefit {
        display: flex;
        align-items: center; 
        gap: 25px; 
        margin-bottom: 45px; 
    }
    .icon {
        width: 60px;
        height: 60px; 
        border: 3px solid black;
        border-radius: 4px;
        display: flex;
        align-items: center; 
        justify-content: center; 
        font-size: 28px;
    }
    .benefit h3 {
        font-size: 20px; 
        margin-bottom: 7px; 
    }
    .benefit p {
        font-size: 16px; 
    }
    /* Høyre side */
    .form-section {
        width: 55%;
        padding: 45px 70px; 
    }
    .form-section h1 {
        font-size: 34px;
        margin-bottom: 35px; 
    }
    .form-section h2 {
        font-size: 21px; 
        margin-top: 25px; 
        margin-bottom: 15px; 
    }
    /*Labels*/ 
    form label {
        display: block; 
        font-size: 15px; 
        font-weight: bold; 
        margin-bottom: 7px; 
    }
    /*Input*/ 
    input[type="text"], 
    input[type="email"],
    input[type="tel"],
    input[type="password"] {
        width: 100%;
        height: 45px; 
        border: 2px solid #222;
        border-radius: 3px; 
        padding: 0 13px; 
        font-size: 15px; 
        margin-bottom: 18px; 
    }
    input:focus {
        outline: none; 
        border-color: #2f6df6; 
    }
    /*Terms*/ 
    .terms {
        display: flex; 
        align-itmes: flex-start; 
        gap: 10px; 
        margin-top: 5px; 
        margin-bottom: 25px; 
    }
    .terms input {
        width: 20px; 
        height: 20px; 
        margin-top: 2px; 
    }
    .terms label {
        font-size: 15px; 
        line-height: 1.4; 
    }
    /*button*/ 
    button {
        background-color: white; 
        border: 3px solid #2f6df6; 
        color: #2f6df6; 
        padding: 12px 25px; 
        font-size: 17px;
        font-weight: bold;
        cursor: pointer;
        transition: 0.2s; 
    }
    button:hover {
        background-color: #2f6df6; 
        color: white; 
    }

    .error-message {
        background-color: #ffe5e5; 
        border: 1px solid #d00000; 
        color: #b00000 ;
        padding: 12px; 
        margin-bottom: 20px; 
        border-radius: 4px; 
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
                <h3>Better cooperation</h3>
                <p>Create a tidier and more efficient workplace</p>
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
    <!-- Høyre side -->
     <div class="form-section">
        <h1>CREATE YOUR COMPANY ACCOUNT</h1>
        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="">
            <h2>COMPANY INFORMATION</h2>¨
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
                value="<?= htmlspecialchars($phone)?>"
                required
            >
            <!-- MANAGER --> 
             <h2>CREATE MANAGER ACCOUNT</h2>
             <label for="mangerName">YOUR NAME</label>
             <input 
                type="text"
                id="managerName"
                name="managerName"
                placeholder="Enter your name"
                value="<?= htmlspecialchars($mangerName) ?>"
                required
            >
            


