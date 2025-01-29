# Browsergame

## Installation
Es ist zu beachten, dass hier nur eine dev umgebung eingerichtet wird, es ist keinesfalls für ein Produktiv System geeignet.

### Ubuntu
Notwendige Packete installieren
```sudo apt install apache2 php php-mysql mariadb-server```

Datenbank einrichten:
```mysql_secure_installation```

Auf Ubuntu solle, die Standart konfiguration von apache soweit in Ordnung sein, dass keine weitere Einstellarbeiten, wie das einrichten von php etc. notwendig sind.
Die `Database.sql` Datei solle so ein zu ein in mysql eingefügt werden können
```mysql -u root -p```

mit ```CreatePlayerWithSettlement('SpielerName')``` lässt sich das erste Settlement erstellen.

Auch die Dateien sollten einfach nach `/var/www/html/game` kopiert werden können und mit `localhost/game/index.php?settlementId=1` aufrufbar sein.
