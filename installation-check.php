<?php
/**
 * Installation Verification Script for Browsergame
 * 
 * This script checks if the game is properly installed and configured.
 * Run this script after installation to verify everything is working.
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browsergame - Installation Check</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .check { margin: 10px 0; padding: 10px; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        h1 { color: #333; }
        h2 { color: #666; margin-top: 30px; }
        .status { font-weight: bold; }
        code { background: #f8f9fa; padding: 2px 4px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>🎮 Browsergame Installation Check</h1>
    <p>Dieser Check überprüft, ob das Browsergame korrekt installiert ist.</p>

    <h2>📋 System Requirements</h2>
    
    <?php
    // PHP Version Check
    $phpVersion = phpversion();
    $phpMinVersion = '7.4.0';
    if (version_compare($phpVersion, $phpMinVersion, '>=')) {
        echo "<div class='check success'>✅ PHP Version: $phpVersion (OK - min. $phpMinVersion required)</div>";
    } else {
        echo "<div class='check error'>❌ PHP Version: $phpVersion (ERROR - min. $phpMinVersion required)</div>";
    }

    // PDO MySQL Extension Check
    if (extension_loaded('pdo_mysql')) {
        echo "<div class='check success'>✅ PDO MySQL Extension: Verfügbar</div>";
    } else {
        echo "<div class='check error'>❌ PDO MySQL Extension: Nicht verfügbar (php-mysql installieren)</div>";
    }

    // File Permissions Check
    $files = ['index.php', 'backend.php', 'database.php', 'admin.php', 'database.sql'];
    $allFilesExist = true;
    
    foreach ($files as $file) {
        if (file_exists($file) && is_readable($file)) {
            echo "<div class='check success'>✅ Datei $file: Vorhanden und lesbar</div>";
        } else {
            echo "<div class='check error'>❌ Datei $file: Fehlt oder nicht lesbar</div>";
            $allFilesExist = false;
        }
    }
    ?>

    <h2>🗄️ Database Connection</h2>
    
    <?php
    try {
        require_once 'database.php';
        $database = new Database();
        
        if ($database->isConnected()) {
            echo "<div class='check success'>✅ Datenbankverbindung: Erfolgreich</div>";
            
            // Test basic database operations
            try {
                $playerCount = $database->getPlayerCount();
                echo "<div class='check success'>✅ Datenbankoperationen: Funktionieren (Spieleranzahl: $playerCount)</div>";
            } catch (Exception $e) {
                echo "<div class='check warning'>⚠️ Datenbankoperationen: Verbindung OK, aber Tabellen möglicherweise nicht initialisiert</div>";
                echo "<div class='check info'>💡 Führe das database.sql Script aus: <code>mysql -u root -p < database.sql</code></div>";
            }
        } else {
            echo "<div class='check error'>❌ Datenbankverbindung: Fehlgeschlagen</div>";
            echo "<div class='check info'>💡 Überprüfe die Zugangsdaten in database.php</div>";
        }
    } catch (Exception $e) {
        echo "<div class='check error'>❌ Datenbankverbindung: Fehler - " . htmlspecialchars($e->getMessage()) . "</div>";
        echo "<div class='check info'>💡 Stelle sicher, dass MySQL/MariaDB läuft und die Datenbank 'browsergame' existiert</div>";
    }
    ?>

    <h2>🎮 Game Components</h2>
    
    <?php
    // Check if game can be accessed with a test settlement ID
    $gameUrl = "index.php?settlementId=1";
    if (file_exists('index.php')) {
        echo "<div class='check success'>✅ Hauptspiel: <a href='$gameUrl' target='_blank'>Spiel öffnen (Settlement ID: 1)</a></div>";
    } else {
        echo "<div class='check error'>❌ Hauptspiel: index.php nicht gefunden</div>";
    }

    // Admin Panel Check
    if (file_exists('admin.php')) {
        echo "<div class='check success'>✅ Admin Panel: <a href='admin.php' target='_blank'>Admin Panel öffnen</a></div>";
        echo "<div class='check info'>💡 Standard-Zugangsdaten: admin / admin123</div>";
    } else {
        echo "<div class='check error'>❌ Admin Panel: admin.php nicht gefunden</div>";
    }

    // JavaScript and CSS Files
    if (file_exists('backend.js')) {
        echo "<div class='check success'>✅ Frontend JavaScript: backend.js vorhanden</div>";
    } else {
        echo "<div class='check error'>❌ Frontend JavaScript: backend.js fehlt</div>";
    }

    if (file_exists('style.css')) {
        echo "<div class='check success'>✅ Stylesheet: style.css vorhanden</div>";
    } else {
        echo "<div class='check error'>❌ Stylesheet: style.css fehlt</div>";
    }
    ?>

    <h2>🔧 Next Steps</h2>
    
    <div class='check info'>
        <strong>Falls alle Checks erfolgreich sind:</strong><br>
        1. Öffne das <a href="index.php?settlementId=1">Hauptspiel</a><br>
        2. Logge dich ins <a href="admin.php">Admin Panel</a> ein<br>
        3. Erstelle neue Spieler über das Admin Panel oder mit: <code>CALL CreatePlayerWithSettlement('DeinName');</code>
    </div>
    
    <div class='check warning'>
        <strong>Falls Fehler auftreten:</strong><br>
        1. Überprüfe die README.md für detaillierte Installationsanweisungen<br>
        2. Stelle sicher, dass Apache und MySQL/MariaDB laufen<br>
        3. Überprüfe die Datenbankzugangsdaten in database.php<br>
        4. Führe das database.sql Script aus, falls noch nicht geschehen
    </div>

    <h2>📖 Documentation</h2>
    <div class='check info'>
        <ul>
            <li><strong>README.md</strong> - Vollständige Installationsanweisungen</li>
            <li><strong>ADMIN_README.md</strong> - Admin Panel Dokumentation</li>
            <li><strong>Spiel URL</strong> - <code><?php echo ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/'); ?>index.php?settlementId=1</code></li>
            <li><strong>Admin URL</strong> - <code><?php echo ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/'); ?>admin.php</code></li>
        </ul>
    </div>

    <hr style="margin: 30px 0;">
    <p style="text-align: center; color: #666; font-size: 0.9em;">
        Browsergame Installation Check - 
        Führe diesen Check nach jeder Installation oder Konfigurationsänderung aus.
    </p>
</body>
</html>