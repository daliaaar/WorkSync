<?php
session_start();
require 'config.php';

$error = "";
$success_message = "";
$email = "";
$resetLink = ""; // For å teste lokalt.

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = "Please enter your email address";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        $emailExists = false;
        $stmt = $conn->prepare(
            "SELECT Epost FROM Administrator WHERE Epost = ?
            UNION
            SELECT Epost FROM Bedriftsleder WHERE Epost = ?
            UNION
            SELECT AnsattEpost FROM Ansatt WHERE AnsattEpost = ? "
        );
        $stmt->bind_param("sss", $email, $email, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $emailExists = true;
        }
        $stmt->close();

        if ($emailExists) {
            $token = bin2hex(random_bytes(32));
            $expireAt = date("Y-m-d H:i:s", strtotime("+1 hour"));

            $insertStmt = $conn->prepare("
            INSERT INTO PasswordReseet (Epost, Token, ExpireAt)
            VALUES (?,?,?)
            ");
            $insertStmt->bind_param("sss", $email, $token, $expireAt);
            $insertStmt->execute();
            $insertStmt->close();

            // $resetLink = "http://localhost/reset_password.php?token=" . urlencode($token);
        }
        $success_message = "If an account with that email exists, a password reset link has been sent";
    }
}
?>

<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password?</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --beauty-color: #3456d2;
            --beauty-color-hover: #2d37c2;
            --text-dark: #212529;
        }
        body {
            font-family: 'Times New Roman', serif;
            margin: 0;
            height: 100vh;
            overflow: hidden;
            position: relative;
            background-color: #1a4bc8;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.150);
            position: relative;
            z-index: 1;
        }
        h3 {
            font-weight: bold;
            letter-spacing: 1px;
            color: var(--text-dark);
        }
        .form-control {
            border-radius: 50px;
            padding: 12px 20px;
            border: 1px solid #ddd;
            background: rgba(227, 255, 255, 0.9);
        }
        .form-control:focus {
            box-shadow: 0 0 0 4px rgba(227, 184, 210, 0.3);
            border-color: var(--beauty-color);
        }
        .btn-beauty {
            background-color: var(--beauty-color);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all .3s ease;
        }
        .btn-beauty:hover {
            background-color: var(--beauty-color-hover);
            transform: translateY(-2px);
            color: white;
            box-shadow: 0 4px 10px rgba(227, 184, 210, 0.6);
        }
        .bottom-links a {
            color: #666;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s;
        }
        .bottom-links a:hover {
            color: var(--beauty-color-hover);
            text-decoration: underline;
        }

        /* Dynamisk bakgrunn */
        .wave-background {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 0;
        overflow: hidden;
        background: linear-gradient(135deg, #1b47c1, #0f2c82);
      }
       .wave-line {
        position: absolute;
        width: 200%;
        height: 120px;
        opacity: 0.6;
        filter: blur(10px);
        animation: waveAnimation 12s ease-in-out infinite alternate;
      }
      .line-blue {
        background: linear-gradient(90deg, transparent, #4da6ff, transparent);
        top: 15%;
        animation-duration: 14s;
      }
      .line-green {
        background: linear-gradient(90deg, transparent, #2ecc71, transparent);
        top: 45%;
        animation-duration: 10s;
        animation-delay: -2s;
      }
       .line-yellow{
        background: linear-gradient(90deg, transparent, #f1c40f, transparent);
        top: 75%;
        animation-duration: 16s;
        animation-delay: -5s;
      }
      @keyframes waveAnimation {
        0% {
            transform: translateX(-25%) translateY(0) rotate(-3deg);
        }
        50% {
            transform: translateX(0%) translateY(-35px) rotate(3deg);
        }
        100% {
            transform: translateX(-25%) translateY(25px) rotate(-2deg);
        }
      }
      </style>
    </head>
    <body class="d-flex align-items-center justify-content-center vh-100">
    <div class="wave-background">
        <div class="wave-line line-blue"></div>
        <div class="wave-line line-green"></div>
        <div class="wave-line line-yellow"></div>
    </div>

    <div class="card glass-card p-5" style="max-width:420px; width: 100%;">
        <div class="text-center mb-4">
            <h2 class="fw-bold">WorkSync</h2>
            <p class="text-muted small">Enter your email and we will send you a reset link.</p>
    </div>

    <?php if (!empty($success_message)): ?>
        <div class='alert alert-success text-center rounded-pill py-2 small mb-3'><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>

        <?php if (!empty($error)) : ?>
            <div class='alert alert-danger text-center rounded-pill py-2 small mb-3'><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($resetLink)) : ?>
            <div class='alert alert-warning small mb-3'>
                <strong>Dev-modus:</strong> siden sender ikke e-post lokalt, så her er lenken for testing:<br>
        <a href="<?= htmlspecialchars($resetLink) ?>"><?= htmlspecialchars($resetLink) ?></a>
             </div>
        <?php endif; ?>

  <form method="POST" novalidate>
    <div class="mb-4">
      <label class="form-label fw-semibold ms-2 small text-muted">Email Address</label>
      <input type="email" class="form-control" name="email" placeholder="name@example.com" value="<?= htmlspecialchars($email) ?>" required>
    </div>

    <button type="submit" class="btn btn-beauty w-100 mb-3">Send Reset Link</button>
  </form>

  <div class="d-flex justify-content-center align-items-center bottom-links mt-2 border-top pt-3">
      <a href="log_in.php"><i class="bi bi-arrow-left"></i> Back to Login</a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>