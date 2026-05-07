-- TSG Alert and Repair Network — Database Schema
-- Database: dbtech-opsrefactored
-- Run this in phpMyAdmin or via MySQL CLI: mysql -u root < schema.sql

CREATE DATABASE IF NOT EXISTS `dbtech-opsrefactored`
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE `dbtech-opsrefactored`;

-- ─────────────────────────────────────────────
-- USERS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `User` (
    `UID`        INT          AUTO_INCREMENT PRIMARY KEY,
    `Email`      VARCHAR(255) NOT NULL UNIQUE,
    `Password`   VARCHAR(255) NOT NULL,
    `FirstName`  VARCHAR(100) NOT NULL,
    `MiddleName` VARCHAR(100) DEFAULT NULL,
    `LastName`   VARCHAR(100) NOT NULL,
    `Contact`    VARCHAR(20)  DEFAULT NULL
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- SUBTYPES
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `Student` (
    `StudentID` INT AUTO_INCREMENT PRIMARY KEY,
    `Course`    VARCHAR(100) DEFAULT NULL,
    `YearLevel` INT          DEFAULT NULL,
    `UID`       INT NOT NULL,
    FOREIGN KEY (`UID`) REFERENCES `User`(`UID`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `Faculty` (
    `FacultyID` INT AUTO_INCREMENT PRIMARY KEY,
    `UID`       INT NOT NULL,
    FOREIGN KEY (`UID`) REFERENCES `User`(`UID`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `TSG_Personnel` (
    `PersonnelID` INT AUTO_INCREMENT PRIMARY KEY,
    `FacultyID`   INT NOT NULL,
    FOREIGN KEY (`FacultyID`) REFERENCES `Faculty`(`FacultyID`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- LABS & WORKSTATIONS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `Lab` (
    `LabID`   INT AUTO_INCREMENT PRIMARY KEY,
    `LabName` VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `Workstation` (
    `WorkstationID` INT AUTO_INCREMENT PRIMARY KEY,
    `WorkstationNo` VARCHAR(20)  NOT NULL,
    `LabID`         INT NOT NULL,
    FOREIGN KEY (`LabID`) REFERENCES `Lab`(`LabID`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- DEFECT REPORTS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `DefectReport` (
    `ReportID`            INT AUTO_INCREMENT PRIMARY KEY,
    `Status`              ENUM('Pending','In-Progress','Resolved') NOT NULL DEFAULT 'Pending',
    `Component`           ENUM('Mouse','Keyboard','Display','RAM','System Unit','Audio') NOT NULL,
    `Description`         TEXT DEFAULT NULL,
    `DateFiled`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `WorkstationID`       INT DEFAULT NULL,
    `AssignedPersonnelID` INT DEFAULT NULL,
    FOREIGN KEY (`WorkstationID`)       REFERENCES `Workstation`(`WorkstationID`),
    FOREIGN KEY (`AssignedPersonnelID`) REFERENCES `TSG_Personnel`(`PersonnelID`)
) ENGINE=InnoDB;

-- Links a report to the student who filed it
CREATE TABLE IF NOT EXISTS `ReportStudent` (
    `ID`        INT AUTO_INCREMENT PRIMARY KEY,
    `ReportID`  INT NOT NULL,
    `StudentID` INT NOT NULL,
    FOREIGN KEY (`ReportID`)  REFERENCES `DefectReport`(`ReportID`) ON DELETE CASCADE,
    FOREIGN KEY (`StudentID`) REFERENCES `Student`(`StudentID`)     ON DELETE CASCADE
) ENGINE=InnoDB;

-- Links a report to the faculty who filed it
CREATE TABLE IF NOT EXISTS `ReportFaculty` (
    `ID`        INT AUTO_INCREMENT PRIMARY KEY,
    `ReportID`  INT NOT NULL,
    `FacultyID` INT NOT NULL,
    FOREIGN KEY (`ReportID`)  REFERENCES `DefectReport`(`ReportID`) ON DELETE CASCADE,
    FOREIGN KEY (`FacultyID`) REFERENCES `Faculty`(`FacultyID`)     ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- SAMPLE DATA
-- ─────────────────────────────────────────────
INSERT IGNORE INTO `Lab` (`LabName`) VALUES
    ('Laboratory 1'),
    ('Laboratory 2'),
    ('Laboratory 3'),
    ('Laboratory 4'),
    ('Laboratory 5');

-- 10 workstations per lab
INSERT IGNORE INTO `Workstation` (`WorkstationNo`, `LabID`) VALUES
    ('WS-01',1),('WS-02',1),('WS-03',1),('WS-04',1),('WS-05',1),
    ('WS-06',1),('WS-07',1),('WS-08',1),('WS-09',1),('WS-10',1),
    ('WS-01',2),('WS-02',2),('WS-03',2),('WS-04',2),('WS-05',2),
    ('WS-06',2),('WS-07',2),('WS-08',2),('WS-09',2),('WS-10',2),
    ('WS-01',3),('WS-02',3),('WS-03',3),('WS-04',3),('WS-05',3),
    ('WS-06',3),('WS-07',3),('WS-08',3),('WS-09',3),('WS-10',3),
    ('WS-01',4),('WS-02',4),('WS-03',4),('WS-04',4),('WS-05',4),
    ('WS-06',4),('WS-07',4),('WS-08',4),('WS-09',4),('WS-10',4),
    ('WS-01',5),('WS-02',5),('WS-03',5),('WS-04',5),('WS-05',5),
    ('WS-06',5),('WS-07',5),('WS-08',5),('WS-09',5),('WS-10',5);
