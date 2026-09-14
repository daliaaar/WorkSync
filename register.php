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

    .info-sectio
