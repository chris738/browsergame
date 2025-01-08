-- Erstellung einer neuen Datenbank
CREATE DATABASE browsergame;

-- Erstellung eines neuen Benutzers
CREATE USER 'browsergame'@'localhost' IDENTIFIED BY 'sicheresPasswort';

-- Berechtigungen für den Benutzer
GRANT ALL PRIVILEGES ON browsergame.* TO 'browsergame'@'localhost';

-- Wechsel zur neuen Datenbank
USE browsergame;
