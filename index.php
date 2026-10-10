<?php
session_start();
include "config/database.php";

// Already logged in — skip straight to the dashboard.
if (isset($_SESSION['username'])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = "Please enter both username and password.";
    } else {

        $stmt = $conn->prepare("SELECT username, password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                $_SESSION['username'] = $user['username'];
                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Incorrect password.";
            }
        } else {
            $error = "User not found.";
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Medipoint Medical Stores — Login</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-body">

  <div class="login-card">

    <div class="login-brand">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
        <path d="M4.5 14.5 14.5 4.5a5 5 0 1 1 7 7L11.5 21.5a5 5 0 0 1-7-7Z"/>
        <line x1="9" y1="10" x2="15" y2="16"/>
      </svg>
      <h1>Medipoint</h1>
      <p>Medical Stores — Near Ruby Hall Clinic, Pune</p>
    </div>

    <?php if ($error): ?>
      <div class="banner banner-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" required autofocus>

      <label for="password">Password</label>
      <input type="password" id="password" name="password" required>

      <button type="submit" class="btn btn-primary btn-block" style="margin-top:18px;">Log in</button>
    </form>

  </div>

</body>
</html>
