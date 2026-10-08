<?php
require 'config.php'; 

$token = $_GET['token'] ?? $_POST['token'] ?? ''; 
$email = null; 

$stmt = $conn-> prepare("SELECT Epost FROM PasswordReset WHERE Token = ? AND ExpiresAt > NOW()");
$stmt-> bind_param("s", $token); 
$stmt-> execute(); 
$row = $stmt->get_Result()->fetch_assoc();
$stmt->close();

if ($row) {
    $email = $row['Epost'];
}

if ($email && SERVER["REQUEST_METHOD"] === "POST"){
    $pw = $_POST['password'] ?? ''; 
    $pw2 = $_POST['password2'] ?? ''; 

    if (strlen($pw) < 8) {
        $error = "Password must be at leat 8 charcters"; 
    } elseif ($pw !== $pw2) {
        $error = "Passwords mmust be alike."
    } else {
        $hash = password_hash($pw, PASSWORD_DEFAULT);

        $queries = [
            "UPDATE ADMINISTRATOR SET Passprd =? WHERE Epost =?",
            "UPDATE COMPANYMANAGER SET Passord = ? WHERE Epost =?",
            "UPDATE EMPLOYEE SET AnsattPassord =? WHERE AnsattEpost =?"
        ]; 
        foreach ($queries as $sql) {
            $u = $conn->prepare($sql); 
            $u->bind_param("ss", $hash, $email);
            $u->execute();
            $u->close();
        }

        $d = $conn->prepare("DELETE FROM PasswordReset WHERE Epost = ?");
        $d->bind_param("ss", $email);
        $d->execute(); 
        $d->close();

        $done = true; 
    }
}
?>

<!DOCTYPE html>
<html lang="no">
<head>
  <meta charset="UTF-8">
  <title>Reset Password - WorkSync</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center vh-100 bg-light">
<div class="card p-5" style="max-width: 420px; width: 100%;">
  <h3 class="text-center mb-4">WorkSync</h3>

  <?php if ($email): ?>
    <div class="alert alert-success"> Link valid for <?= htmlspecialchars($email) ?></div>
  <?php else: ?>
    <div class="alert alert-danger"> Link is unvalid or expired. </div>
    <a href="forgot_password.php">Ask for another link.</a>
  <?php endif; ?>
  </div>
  </body>
  </html>
