<?php
session_start();

// Add some debugging for new installations
if (isset($_GET['debug']) && $_GET['debug'] === 'session') {
    echo "<pre>";
    echo "Session debug info:\n";
    echo "Session ID: " . session_id() . "\n";
    echo "Session save path: " . session_save_path() . "\n";
    echo "Session data: " . print_r($_SESSION, true) . "\n";
    echo "</pre>";
}

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
        // Add a small delay to ensure session is written
        usleep(100000); // 0.1 seconds
    } else {
        $loginError = 'Ungültige Zugangsdaten. Verwende: admin / admin123';
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
                <input type="text" id="username" name="username" required placeholder="admin">
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required placeholder="admin123">
            </div>
            <button type="submit" name="login">Login</button>
            <div style="margin-top: 15px; font-size: 0.9em; color: #666;">
                Standard-Zugangsdaten:<br>
                <strong>Username:</strong> admin<br>
                <strong>Password:</strong> admin123
            </div>
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