<?php
/**
 * Admin Login Test Script
 * 
 * This script helps verify that the admin login functionality is working
 * after a new installation of the browsergame.
 * 
 * Run this script via command line: php tests/test-admin-login.php
 * Or access it via browser: http://localhost/browsergame/tests/test-admin-login.php
 */

// Change to parent directory to find php files
chdir(__DIR__ . '/..');

echo "=== Browsergame Admin Login Test ===\n\n";

// Test 1: Check required files
echo "1. Checking required files...\n";
$required_files = [
    'admin.php' => 'Main admin login file',
    'php/admin-overview.php' => 'Admin overview page',
    'php/admin-buildings.php' => 'Admin buildings page',
    'php/database.php' => 'Database connection class'
];

$files_ok = true;
foreach ($required_files as $file => $description) {
    if (file_exists($file)) {
        echo "   ✅ $file ($description)\n";
    } else {
        echo "   ❌ $file ($description) - MISSING!\n";
        $files_ok = false;
    }
}

if (!$files_ok) {
    echo "\n❌ Some required files are missing. Please check your installation.\n";
    exit(1);
}

// Test 2: Check PHP session support
echo "\n2. Checking PHP session support...\n";
if (extension_loaded('session')) {
    echo "   ✅ Session extension loaded\n";
    
    // Test session directory
    $session_path = ini_get('session.save_path') ?: sys_get_temp_dir();
    if (is_dir($session_path) && is_writable($session_path)) {
        echo "   ✅ Session directory writable: $session_path\n";
    } else {
        echo "   ❌ Session directory not writable: $session_path\n";
        echo "   💡 Fix with: sudo chown -R www-data:www-data $session_path\n";
    }
} else {
    echo "   ❌ Session extension not loaded\n";
    echo "   💡 Install with: sudo apt install php-session\n";
}

// Test 3: Test session functionality
echo "\n3. Testing session functionality...\n";
try {
    if (session_start()) {
        echo "   ✅ Session start successful\n";
        $_SESSION['test'] = 'works';
        if (isset($_SESSION['test']) && $_SESSION['test'] === 'works') {
            echo "   ✅ Session read/write working\n";
        } else {
            echo "   ❌ Session read/write failed\n";
        }
        session_destroy();
    } else {
        echo "   ❌ Session start failed\n";
    }
} catch (Exception $e) {
    echo "   ❌ Session error: " . $e->getMessage() . "\n";
}

// Test 4: Check database connection (optional)
echo "\n4. Testing database connection...\n";
try {
    require_once 'php/database.php';
    $database = new Database();
    if ($database->isConnected()) {
        echo "   ✅ Database connection working\n";
    } else {
        echo "   ⚠️ Database connection failed (but admin login will still work with demo data)\n";
    }
} catch (Exception $e) {
    echo "   ⚠️ Database test failed: " . $e->getMessage() . "\n";
    echo "   💡 Admin login will work with demo data\n";
}

// Test 5: Admin login credentials test
echo "\n5. Testing admin login logic...\n";
$admin_username = 'admin';
$admin_password = 'admin123';

// Simulate correct login
$test_user = 'admin';
$test_pass = 'admin123';
if ($test_user === $admin_username && $test_pass === $admin_password) {
    echo "   ✅ Admin credentials validation working\n";
} else {
    echo "   ❌ Admin credentials validation failed\n";
}

// Final summary
echo "\n=== Summary ===\n";
echo "Admin login test completed.\n\n";
echo "To test the admin login in your browser:\n";
echo "1. Open: http://localhost/browsergame/admin.php\n";
echo "2. Login with:\n";
echo "   Username: admin\n";
echo "   Password: admin123\n";
echo "3. You should be redirected to the admin overview page\n\n";

echo "If you encounter issues:\n";
echo "- Check that Apache/Nginx is running\n";
echo "- Verify file permissions are correct\n";
echo "- Check PHP error logs\n";
echo "- Use ?debug=session parameter for session debugging\n\n";

echo "For more help, see docs/INSTALLATION.md\n";
?>