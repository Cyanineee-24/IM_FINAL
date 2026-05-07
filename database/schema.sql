-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 07, 2026 at 10:12 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dbtech-opsrefactored`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `AdminID` int(11) NOT NULL,
  `UID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`AdminID`, `UID`) VALUES
(1, 4);

-- --------------------------------------------------------

--
-- Table structure for table `defectreport`
--

CREATE TABLE `defectreport` (
  `ReportID` int(11) NOT NULL,
  `Status` enum('Pending','In-Progress','Resolved') NOT NULL DEFAULT 'Pending',
  `Component` enum('Mouse','Keyboard','Display','RAM','System Unit','Audio') NOT NULL,
  `Description` text DEFAULT NULL,
  `DateFiled` datetime NOT NULL DEFAULT current_timestamp(),
  `WorkstationID` int(11) DEFAULT NULL,
  `AssignedPersonnelID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `defectreport`
--

INSERT INTO `defectreport` (`ReportID`, `Status`, `Component`, `Description`, `DateFiled`, `WorkstationID`, `AssignedPersonnelID`) VALUES
(1, 'Resolved', 'Keyboard', 'keyboard input won\'t register', '2026-05-06 14:11:49', 25, 1),
(2, 'Resolved', 'Mouse', NULL, '2026-05-06 14:20:50', 43, 1),
(3, 'Pending', 'Audio', NULL, '2026-05-06 14:21:15', 42, NULL),
(4, 'Pending', 'System Unit', NULL, '2026-05-06 14:21:39', 38, NULL),
(5, 'Pending', 'Audio', NULL, '2026-05-06 14:21:50', 15, NULL),
(6, 'Pending', 'RAM', NULL, '2026-05-06 14:21:59', 40, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

CREATE TABLE `faculty` (
  `FacultyID` int(11) NOT NULL,
  `UID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `faculty`
--

INSERT INTO `faculty` (`FacultyID`, `UID`) VALUES
(1, 2),
(2, 7),
(3, 8);

-- --------------------------------------------------------

--
-- Table structure for table `lab`
--

CREATE TABLE `lab` (
  `LabID` int(11) NOT NULL,
  `LabName` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lab`
--

INSERT INTO `lab` (`LabID`, `LabName`) VALUES
(1, 'Laboratory 1'),
(2, 'Laboratory 2'),
(3, 'Laboratory 3'),
(4, 'Laboratory 4'),
(5, 'Laboratory 5');

-- --------------------------------------------------------

--
-- Table structure for table `reportfaculty`
--

CREATE TABLE `reportfaculty` (
  `ID` int(11) NOT NULL,
  `ReportID` int(11) NOT NULL,
  `FacultyID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reportstudent`
--

CREATE TABLE `reportstudent` (
  `ID` int(11) NOT NULL,
  `ReportID` int(11) NOT NULL,
  `StudentID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reportstudent`
--

INSERT INTO `reportstudent` (`ID`, `ReportID`, `StudentID`) VALUES
(1, 1, 1),
(2, 2, 1),
(3, 3, 1),
(4, 4, 1),
(5, 5, 1),
(6, 6, 1);

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `StudentID` int(11) NOT NULL,
  `Course` varchar(100) DEFAULT NULL,
  `YearLevel` int(11) DEFAULT NULL,
  `UID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`StudentID`, `Course`, `YearLevel`, `UID`) VALUES
(1, 'BSCS', 2, 1),
(2, 'CS', 1, 3),
(3, 'BSCS', 1, 5);

-- --------------------------------------------------------

--
-- Table structure for table `tsg_personnel`
--

CREATE TABLE `tsg_personnel` (
  `PersonnelID` int(11) NOT NULL,
  `FacultyID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tsg_personnel`
--

INSERT INTO `tsg_personnel` (`PersonnelID`, `FacultyID`) VALUES
(1, 1),
(2, 2);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `UID` int(11) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `FirstName` varchar(100) NOT NULL,
  `MiddleName` varchar(100) DEFAULT NULL,
  `LastName` varchar(100) NOT NULL,
  `Contact` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`UID`, `Email`, `Password`, `FirstName`, `MiddleName`, `LastName`, `Contact`) VALUES
(1, 'scoutchampion123@gmail.com', '1234', 'Charles', NULL, 'Daragosa Student', NULL),
(2, 'charlesbenedict.daragosa@cit.edu', '1234', 'Charles', NULL, 'Personnel', NULL),
(3, 'tester@cit.edu', 'password123', 'Tester', NULL, 'User', NULL),
(4, 'admin@cit.edu', 'admin123', 'Super', NULL, 'Admin', '00000000000'),
(5, 'sim@cit.edu', 'sim123sim123', 'Sim', NULL, 'Student', '123'),
(7, 'wakandaforevah2@gmail.com', '123', 'Charles', NULL, 'Daragosa', '00000000000'),
(8, 'teacher@cit.edu', '1234', 'Charles', NULL, 'Teacher', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `workstation`
--

CREATE TABLE `workstation` (
  `WorkstationID` int(11) NOT NULL,
  `WorkstationNo` varchar(20) NOT NULL,
  `LabID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `workstation`
--

INSERT INTO `workstation` (`WorkstationID`, `WorkstationNo`, `LabID`) VALUES
(1, 'WS-01', 1),
(2, 'WS-02', 1),
(3, 'WS-03', 1),
(4, 'WS-04', 1),
(5, 'WS-05', 1),
(6, 'WS-06', 1),
(7, 'WS-07', 1),
(8, 'WS-08', 1),
(9, 'WS-09', 1),
(10, 'WS-10', 1),
(11, 'WS-01', 2),
(12, 'WS-02', 2),
(13, 'WS-03', 2),
(14, 'WS-04', 2),
(15, 'WS-05', 2),
(16, 'WS-06', 2),
(17, 'WS-07', 2),
(18, 'WS-08', 2),
(19, 'WS-09', 2),
(20, 'WS-10', 2),
(21, 'WS-01', 3),
(22, 'WS-02', 3),
(23, 'WS-03', 3),
(24, 'WS-04', 3),
(25, 'WS-05', 3),
(26, 'WS-06', 3),
(27, 'WS-07', 3),
(28, 'WS-08', 3),
(29, 'WS-09', 3),
(30, 'WS-10', 3),
(31, 'WS-01', 4),
(32, 'WS-02', 4),
(33, 'WS-03', 4),
(34, 'WS-04', 4),
(35, 'WS-05', 4),
(36, 'WS-06', 4),
(37, 'WS-07', 4),
(38, 'WS-08', 4),
(39, 'WS-09', 4),
(40, 'WS-10', 4),
(41, 'WS-01', 5),
(42, 'WS-02', 5),
(43, 'WS-03', 5),
(44, 'WS-04', 5),
(45, 'WS-05', 5),
(46, 'WS-06', 5),
(47, 'WS-07', 5),
(48, 'WS-08', 5),
(49, 'WS-09', 5),
(50, 'WS-10', 5);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`AdminID`),
  ADD KEY `UID` (`UID`);

--
-- Indexes for table `defectreport`
--
ALTER TABLE `defectreport`
  ADD PRIMARY KEY (`ReportID`),
  ADD KEY `WorkstationID` (`WorkstationID`),
  ADD KEY `AssignedPersonnelID` (`AssignedPersonnelID`);

--
-- Indexes for table `faculty`
--
ALTER TABLE `faculty`
  ADD PRIMARY KEY (`FacultyID`),
  ADD KEY `UID` (`UID`);

--
-- Indexes for table `lab`
--
ALTER TABLE `lab`
  ADD PRIMARY KEY (`LabID`);

--
-- Indexes for table `reportfaculty`
--
ALTER TABLE `reportfaculty`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `ReportID` (`ReportID`),
  ADD KEY `FacultyID` (`FacultyID`);

--
-- Indexes for table `reportstudent`
--
ALTER TABLE `reportstudent`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `ReportID` (`ReportID`),
  ADD KEY `StudentID` (`StudentID`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`StudentID`),
  ADD KEY `UID` (`UID`);

--
-- Indexes for table `tsg_personnel`
--
ALTER TABLE `tsg_personnel`
  ADD PRIMARY KEY (`PersonnelID`),
  ADD KEY `FacultyID` (`FacultyID`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`UID`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Indexes for table `workstation`
--
ALTER TABLE `workstation`
  ADD PRIMARY KEY (`WorkstationID`),
  ADD KEY `LabID` (`LabID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `AdminID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `defectreport`
--
ALTER TABLE `defectreport`
  MODIFY `ReportID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `faculty`
--
ALTER TABLE `faculty`
  MODIFY `FacultyID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `lab`
--
ALTER TABLE `lab`
  MODIFY `LabID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `reportfaculty`
--
ALTER TABLE `reportfaculty`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reportstudent`
--
ALTER TABLE `reportstudent`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `StudentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tsg_personnel`
--
ALTER TABLE `tsg_personnel`
  MODIFY `PersonnelID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `UID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `workstation`
--
ALTER TABLE `workstation`
  MODIFY `WorkstationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`UID`) REFERENCES `user` (`UID`) ON DELETE CASCADE;

--
-- Constraints for table `defectreport`
--
ALTER TABLE `defectreport`
  ADD CONSTRAINT `defectreport_ibfk_1` FOREIGN KEY (`WorkstationID`) REFERENCES `workstation` (`WorkstationID`),
  ADD CONSTRAINT `defectreport_ibfk_2` FOREIGN KEY (`AssignedPersonnelID`) REFERENCES `tsg_personnel` (`PersonnelID`);

--
-- Constraints for table `faculty`
--
ALTER TABLE `faculty`
  ADD CONSTRAINT `faculty_ibfk_1` FOREIGN KEY (`UID`) REFERENCES `user` (`UID`) ON DELETE CASCADE;

--
-- Constraints for table `reportfaculty`
--
ALTER TABLE `reportfaculty`
  ADD CONSTRAINT `reportfaculty_ibfk_1` FOREIGN KEY (`ReportID`) REFERENCES `defectreport` (`ReportID`) ON DELETE CASCADE,
  ADD CONSTRAINT `reportfaculty_ibfk_2` FOREIGN KEY (`FacultyID`) REFERENCES `faculty` (`FacultyID`) ON DELETE CASCADE;

--
-- Constraints for table `reportstudent`
--
ALTER TABLE `reportstudent`
  ADD CONSTRAINT `reportstudent_ibfk_1` FOREIGN KEY (`ReportID`) REFERENCES `defectreport` (`ReportID`) ON DELETE CASCADE,
  ADD CONSTRAINT `reportstudent_ibfk_2` FOREIGN KEY (`StudentID`) REFERENCES `student` (`StudentID`) ON DELETE CASCADE;

--
-- Constraints for table `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `student_ibfk_1` FOREIGN KEY (`UID`) REFERENCES `user` (`UID`) ON DELETE CASCADE;

--
-- Constraints for table `tsg_personnel`
--
ALTER TABLE `tsg_personnel`
  ADD CONSTRAINT `tsg_personnel_ibfk_1` FOREIGN KEY (`FacultyID`) REFERENCES `faculty` (`FacultyID`) ON DELETE CASCADE;

--
-- Constraints for table `workstation`
--
ALTER TABLE `workstation`
  ADD CONSTRAINT `workstation_ibfk_1` FOREIGN KEY (`LabID`) REFERENCES `lab` (`LabID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
