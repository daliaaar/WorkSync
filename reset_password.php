<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mailer.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $epost = trim($_POST['epost'] ?? '');

    if (filter_var($epost, FILTER_VALIDATE_EMAIL)) {
        $stmt = $conn->prepare("SELECT AnsattId FROM EMPLOYEE WHERE AnsattEpost = ?");
        $stmt->bind_param("s", $epost);
        $stmt->execute();
        $finnes = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        if ($finnes) {
            $token = bin2hex(random_bytes(32));
            $hash  = hash('sha256', $token);

            $stmt = $conn->prepare("DELETE FROM PasswordReset WHERE Epost = ?");
            $stmt->bind_param("s", $epost);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO PasswordReset (Epost, Token, ExpiresAt) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))");
            $stmt->bind_param("ss", $epost, $hash);
            $stmt->execute();
            $stmt->close();

            $base = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
            $link = $base . '/reset_password.php?token=' . $token;
            sendMail($epost, 'WorkSync – reset your password', "Use this link to choose a new password (valid for 1 hour):\n\n" . $link);
        }
    }
    $message = 'If the address is registered, a reset link has been sent.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>WorkSync – Forgot password</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<h2>Forgot your password?</h2>
<?php if ($message): ?>
    <p><?= htmlspecialchars($message) ?></p>
<?php endif; ?>
<form method="post">
    <input type="email" name="epost" placeholder="Your email" required>
    <button type="submit">Send reset link</button>
</form>
<p><a href="log_in.php">Back to login</a></p>
</body>
</html>