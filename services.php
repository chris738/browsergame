<?php
    require_once 'php/database.php';
    require_once 'php/emoji-config.php';
    
    $method = $_SERVER['REQUEST_METHOD'];
    $settlementId = $_GET['settlementId'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Services</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/services.css">
    <script src="js/theme-switcher.js"></script>
    <script src="js/emoji-config.js"></script>
    <script src="js/translations.js"></script>
    <script src="js/backend.js" defer></script>
</head>
<body>
    <?php include 'php/navigation.php'; ?>
    
    <section class="services-section">
        <h2><?= EmojiConfig::getUIEmoji('building') ?> Professional Services</h2>
        <p>Premium services for your settlement and vehicles</p>
        
        <div class="services-grid">
            <div class="service-box">
                <div class="service-icon">🏆</div>
                <div class="service-title">Professionell</div>
                <div class="service-description">Premium professional services for all your needs</div>
            </div>
            
            <div class="service-box">
                <div class="service-icon">💎</div>
                <div class="service-title">Hochwertig</div>
                <div class="service-description">High-quality materials and craftsmanship</div>
            </div>
            
            <div class="service-box">
                <div class="service-icon">⚡</div>
                <div class="service-title">Schnell</div>
                <div class="service-description">Fast and efficient service delivery</div>
            </div>
            
            <div class="service-box">
                <div class="service-icon">🚗</div>
                <div class="service-title">Fahrzeugaufbereitung</div>
                <div class="service-description">Complete vehicle preparation and maintenance</div>
            </div>
            
            <div class="service-box">
                <div class="service-icon">🔧</div>
                <div class="service-title">Wartung</div>
                <div class="service-description">Regular maintenance and repair services</div>
            </div>
            
            <div class="service-box">
                <div class="service-icon">🛡️</div>
                <div class="service-title">Schutz</div>
                <div class="service-description">Protection and security services</div>
            </div>
            
            <div class="service-box">
                <div class="service-icon">⭐</div>
                <div class="service-title">Premium</div>
                <div class="service-description">Exclusive premium service packages</div>
            </div>
            
            <div class="service-box">
                <div class="service-icon">🎯</div>
                <div class="service-title">Präzise</div>
                <div class="service-description">Precise and accurate service execution</div>
            </div>
        </div>
    </section>
</body>
</html>