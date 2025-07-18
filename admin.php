<?php
session_start();
require_once 'php/database.php';

// Simple admin authentication
$admin_username = 'admin';
$admin_password = 'admin123'; // In production, this should be hashed

$isLoggedIn = $_SESSION['admin_logged_in'] ?? false;

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username === $admin_username && $password === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
        $isLoggedIn = true;
    } else {
        $loginError = 'Invalid credentials';
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// If not logged in, show login form
if (!$isLoggedIn) {
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Browsergame</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="admin-login">
        <h2>Admin Login</h2>
        <?php if (isset($loginError)): ?>
            <div class="error"><?= htmlspecialchars($loginError) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" name="login">Login</button>
        </form>
    </div>
</body>
</html>
<?php
exit;
}

// Admin dashboard - redirect to overview
header('Location: php/admin-overview.php');
exit;
?>