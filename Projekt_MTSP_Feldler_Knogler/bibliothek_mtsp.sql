-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Erstellungszeit: 20. Jan 2026 um 06:16
-- Server-Version: 10.4.32-MariaDB
-- PHP-Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `bibliothek_mtsp`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `ausleihen`
--

CREATE TABLE `ausleihen` (
  `ausleihen_nr` int(11) NOT NULL,
  `kunden_nr` int(11) DEFAULT NULL,
  `buch_nr` int(11) DEFAULT NULL,
  `bibliothekar_nr` int(11) DEFAULT NULL,
  `datum` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `bibliothekar`
--

CREATE TABLE `bibliothekar` (
  `bibliothekar_id` int(11) NOT NULL,
  `benutzername` varchar(50) NOT NULL,
  `passwort` varchar(255) NOT NULL,
  `erstellt_am` timestamp NOT NULL DEFAULT current_timestamp(),
  `vorname` varchar(100) DEFAULT NULL,
  `nachname` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `bibliothekar`
--

INSERT INTO `bibliothekar` (`bibliothekar_id`, `benutzername`, `passwort`, `erstellt_am`, `vorname`, `nachname`, `email`) VALUES
(1, 'admin', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', '2026-01-12 16:44:31', NULL, NULL, NULL),
(2, 'mani', '$2y$10$RWIV29HaXJwg7h4a1.zdye1.lbXCBzL6L4q7gbXr66GxqIXGrThK2', '2026-01-12 16:46:58', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `buch`
--

CREATE TABLE `buch` (
  `buch_nr` int(11) NOT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `titel` varchar(255) DEFAULT NULL,
  `autor` varchar(255) DEFAULT NULL,
  `verlag` varchar(255) DEFAULT NULL,
  `beschreibung` text DEFAULT NULL,
  `anschaffungspreis` decimal(10,2) DEFAULT NULL,
  `kategorie` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `buch`
--

INSERT INTO `buch` (`buch_nr`, `isbn`, `titel`, `autor`, `verlag`, `beschreibung`, `anschaffungspreis`, `kategorie`) VALUES
(4, '978-3-123456-78-9', 'PHP für Anfänger', 'John Doe', 'Verlag A', '0', 12.00, ''),
(6, '978-3-123456-78-4', 'Harry Potter', 'John Doe', 'Verlag X', '0', 15.00, 'Allgemeinbildung'),
(7, '978-3-446-45678-9', 'Grundlagen der Mechatronik', 'Prof. Dr. Hans Müller', 'Springer Verlag', 'Einführung in die Grundlagen der Mechatronik mit praktischen Beispielen und Übungen.', 45.99, 'Mechatronik'),
(8, '978-3-540-12345-6', 'Programmierung in Python', 'Michael Schmidt', 'O\'Reilly Verlag', 'Umfassendes Lehrbuch für Python-Programmierung von den Grundlagen bis zu fortgeschrittenen Themen.', 39.90, 'Informationstechnik'),
(9, '978-3-8274-23456-7', 'Elektrotechnik für Ingenieure', 'Dr. Thomas Weber', 'Hanser Verlag', 'Lehrbuch zur Elektrotechnik mit Schaltungen, Berechnungen und Anwendungsbeispielen.', 52.50, 'Elektrotechnik'),
(10, '978-3-658-34567-8', 'Maschinenbau Grundlagen', 'Ing. Peter Fischer', 'Vieweg Verlag', 'Einführung in die Grundlagen des Maschinenbaus mit Konstruktionsbeispielen.', 48.75, 'Maschinenbau'),
(11, '978-3-446-45679-6', 'Datenbanken und SQL', 'Sarah Becker', 'dpunkt.verlag', 'Praktisches Handbuch für Datenbankdesign und SQL-Abfragen mit vielen Beispielen.', 34.99, 'Informationstechnik'),
(12, '978-3-540-12346-3', 'Deutsche Geschichte im Überblick', 'Dr. Anna Wagner', 'C.H. Beck', 'Überblick über die deutsche Geschichte von der Antike bis zur Gegenwart.', 29.90, 'Allgemeinbildung'),
(13, '978-3-8274-23457-4', 'Regelungstechnik', 'Prof. Dr. Klaus Hoffmann', 'Hanser Verlag', 'Lehrbuch zur Regelungstechnik mit MATLAB/Simulink-Beispielen.', 55.00, 'Mechatronik'),
(14, '978-3-658-34568-5', 'Webentwicklung mit JavaScript', 'Markus Schulz', 'Rheinwerk Verlag', 'Moderne Webentwicklung mit JavaScript, HTML5 und CSS3.', 42.50, 'Informationstechnik'),
(15, '978-3-446-45680-2', 'Elektrische Maschinen', 'Dr. Julia Klein', 'Springer Verlag', 'Grundlagen elektrischer Maschinen und Antriebe für Ingenieure.', 49.99, 'Elektrotechnik'),
(16, '978-3-540-12347-0', 'Werkstoffkunde', 'Ing. Florian Bauer', 'Vieweg Verlag', 'Einführung in die Werkstoffkunde mit Materialprüfung und Anwendungen.', 46.80, 'Maschinenbau'),
(17, '978-3-8274-23458-1', 'Mathematik für Ingenieure', 'Prof. Dr. Laura Koch', 'Hanser Verlag', 'Mathematische Grundlagen für Ingenieure mit Übungsaufgaben und Lösungen.', 44.90, 'Allgemeinbildung'),
(18, '978-3-658-34569-2', 'Robotik und Automation', 'Dr. Sebastian Richter', 'Springer Verlag', 'Grundlagen der Robotik, Sensoren, Aktoren und Steuerungssysteme.', 58.50, 'Mechatronik'),
(19, '978-3-446-45681-9', 'Netzwerktechnik', 'Michael Wolf', 'O\'Reilly Verlag', 'Grundlagen der Netzwerktechnik, Protokolle und Sicherheit.', 41.99, 'Informationstechnik'),
(20, '978-3-540-12348-7', 'Elektronik Grundlagen', 'Thomas Neumann', 'Hanser Verlag', 'Einführung in die Elektronik mit Schaltungen und Bauteilen.', 38.75, 'Elektrotechnik'),
(21, '978-3-8274-23459-8', 'Konstruktionslehre', 'Ing. Nina Schwarz', 'Vieweg Verlag', 'Methoden und Werkzeuge für die Konstruktion im Maschinenbau.', 51.20, 'Maschinenbau'),
(22, '978-3-658-34570-8', 'Künstliche Intelligenz', 'Dr. Philipp Zimmermann', 'dpunkt.verlag', 'Einführung in KI, Machine Learning und Deep Learning.', 47.90, 'Informationstechnik'),
(23, '978-3-446-45682-6', 'Physik für Ingenieure', 'Prof. Dr. Mia Schröder', 'Springer Verlag', 'Physikalische Grundlagen für Ingenieure mit Anwendungsbeispielen.', 43.60, 'Allgemeinbildung'),
(24, '978-3-540-12349-4', 'Sensortechnik', 'Dr. Jan Meier', 'Hanser Verlag', 'Grundlagen der Sensortechnik und Messtechnik in der Mechatronik.', 50.40, 'Mechatronik'),
(25, '978-3-8274-23460-4', 'Software Engineering', 'Sarah Lang', 'Rheinwerk Verlag', 'Methoden und Praktiken des Software Engineering von der Planung bis zur Wartung.', 45.30, 'Informationstechnik'),
(26, '978-3-658-34571-5', 'Antriebstechnik', 'Ing. Daniel Braun', 'Vieweg Verlag', 'Grundlagen der Antriebstechnik für Maschinenbau und Mechatronik.', 53.80, 'Maschinenbau');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `kunde`
--

CREATE TABLE `kunde` (
  `kunden_nr` int(11) NOT NULL,
  `vorname` varchar(100) DEFAULT NULL,
  `nachname` varchar(100) DEFAULT NULL,
  `tel` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `kunde`
--

INSERT INTO `kunde` (`kunden_nr`, `vorname`, `nachname`, `tel`, `email`) VALUES
(1, 'Max', 'Mustermann', '0123456789', 'max.mustermann@email.com'),
(2, 'Anna', 'Schmidt', '0987654321', 'anna.schmidt@email.com'),
(3, 'Manuel', 'Knogler', '0660588949', 'manuel.knogler@gmail.com'),
(4, 'Max', 'Mustermann', '0123456789', 'max.mustermann@email.de'),
(5, 'Anna', 'Schmidt', '0123456790', 'anna.schmidt@email.de'),
(6, 'Thomas', 'Müller', '0123456791', 'thomas.mueller@email.de'),
(7, 'Lisa', 'Fischer', '0123456792', 'lisa.fischer@email.de'),
(8, 'Michael', 'Weber', '0123456793', 'michael.weber@email.de'),
(9, 'Sarah', 'Meyer', '0123456794', 'sarah.meyer@email.de'),
(10, 'David', 'Wagner', '0123456795', 'david.wagner@email.de'),
(11, 'Julia', 'Becker', '0123456796', 'julia.becker@email.de'),
(12, 'Daniel', 'Schulz', '0123456797', 'daniel.schulz@email.de'),
(13, 'Sophie', 'Hoffmann', '0123456798', 'sophie.hoffmann@email.de'),
(14, 'Markus', 'Schäfer', '0123456799', 'markus.schaefer@email.de'),
(15, 'Laura', 'Koch', '0123456800', 'laura.koch@email.de'),
(16, 'Sebastian', 'Bauer', '0123456801', 'sebastian.bauer@email.de'),
(17, 'Emma', 'Richter', '0123456802', 'emma.richter@email.de'),
(18, 'Florian', 'Klein', '0123456803', 'florian.klein@email.de'),
(19, 'Hannah', 'Wolf', '0123456804', 'hannah.wolf@email.de'),
(20, 'Jan', 'Schröder', '0123456805', 'jan.schroeder@email.de'),
(21, 'Nia', 'Neumann', '0123456806', 'nia.neumann@email.de'),
(22, 'Philipp', 'Schwarz', '0123456807', 'philipp.schwarz@email.de'),
(23, 'Mia', 'Zimmermann', '0123456808', 'mia.zimmermann@email.de');

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `ausleihen`
--
ALTER TABLE `ausleihen`
  ADD PRIMARY KEY (`ausleihen_nr`),
  ADD KEY `kunden_nr` (`kunden_nr`),
  ADD KEY `buch_nr` (`buch_nr`),
  ADD KEY `bibliothekar_nr` (`bibliothekar_nr`);

--
-- Indizes für die Tabelle `bibliothekar`
--
ALTER TABLE `bibliothekar`
  ADD PRIMARY KEY (`bibliothekar_id`),
  ADD UNIQUE KEY `benutzername` (`benutzername`);

--
-- Indizes für die Tabelle `buch`
--
ALTER TABLE `buch`
  ADD PRIMARY KEY (`buch_nr`);

--
-- Indizes für die Tabelle `kunde`
--
ALTER TABLE `kunde`
  ADD PRIMARY KEY (`kunden_nr`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `ausleihen`
--
ALTER TABLE `ausleihen`
  MODIFY `ausleihen_nr` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT für Tabelle `bibliothekar`
--
ALTER TABLE `bibliothekar`
  MODIFY `bibliothekar_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT für Tabelle `buch`
--
ALTER TABLE `buch`
  MODIFY `buch_nr` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT für Tabelle `kunde`
--
ALTER TABLE `kunde`
  MODIFY `kunden_nr` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
