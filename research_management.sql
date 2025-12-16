-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Dec 16, 2025 at 06:44 AM
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
-- Database: `research_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `collaboration`
--

CREATE TABLE `collaboration` (
  `collaboration_id` varchar(36) NOT NULL,
  `collaboration_type` enum('interdepartmental','inter-university') NOT NULL,
  `country` varchar(100) NOT NULL,
  `mou_agreement_date` date DEFAULT NULL,
  `mou_agreement_details` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `collaboration`
--

INSERT INTO `collaboration` (`collaboration_id`, `collaboration_type`, `country`, `mou_agreement_date`, `mou_agreement_details`) VALUES
('c100', 'inter-university', 'Bangladesh', NULL, NULL),
('c101', 'interdepartmental', 'Bangladesh', NULL, NULL),
('c102', 'inter-university', 'Japan', NULL, NULL),
('col_693ecc5621e19', 'interdepartmental', 'Bangladesh', '2025-11-30', 'Sell everything'),
('col_693fb7446baaf', 'interdepartmental', 'Bangladesh', '2025-12-15', 'fund'),
('col_693fe63c51a3c', 'interdepartmental', 'Bangladesh', '2025-12-15', 'vjljfljlj.uj.du');

-- --------------------------------------------------------

--
-- Table structure for table `expenditures`
--

CREATE TABLE `expenditures` (
  `funding_id` varchar(36) NOT NULL,
  `expenditure_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL CHECK (`amount` >= 0),
  `expenditure_date` date NOT NULL,
  `details` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenditures`
--

INSERT INTO `expenditures` (`funding_id`, `expenditure_id`, `amount`, `expenditure_date`, `details`) VALUES
('f100', 2, 50000.00, '2025-12-13', 'Buying shotgun shells'),
('f102', 3, 25000.00, '2025-12-14', 'Buying tanks from Russia as a guise for research because why not'),
('f102', 5, 500.00, '2025-12-15', 'apple'),
('f103', 4, 23000.00, '2025-12-14', 'apples');

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

CREATE TABLE `faculty` (
  `researcher_id` varchar(36) NOT NULL,
  `experience` int(11) NOT NULL,
  `initials` varchar(10) DEFAULT NULL,
  `designation` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty`
--

INSERT INTO `faculty` (`researcher_id`, `experience`, `initials`, `designation`) VALUES
('r1', 6, 'Ak47', 'Professor'),
('r3', 10, 'IMh', 'Assistant Professor');

-- --------------------------------------------------------

--
-- Table structure for table `funding`
--

CREATE TABLE `funding` (
  `funding_id` varchar(36) NOT NULL,
  `agency_name` varchar(200) NOT NULL,
  `total_grant` decimal(12,2) NOT NULL CHECK (`total_grant` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `funding`
--

INSERT INTO `funding` (`funding_id`, `agency_name`, `total_grant`) VALUES
('f100', 'BD Agriculture Board', 1500000.00),
('f102', 'bKash', 55000.00),
('f103', 'AmraIT', 130031.00);

-- --------------------------------------------------------

--
-- Stand-in structure for view `funding_with_amount_left`
-- (See below for the actual view)
--
CREATE TABLE `funding_with_amount_left` (
`funding_id` varchar(36)
,`agency_name` varchar(200)
,`total_grant` decimal(12,2)
,`amount_left` decimal(35,2)
);

-- --------------------------------------------------------

--
-- Table structure for table `mou`
--

CREATE TABLE `mou` (
  `mou_id` varchar(36) NOT NULL,
  `agreement_date` date NOT NULL,
  `agreement_details` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mou`
--

INSERT INTO `mou` (`mou_id`, `agreement_date`, `agreement_details`) VALUES
('mou1', '2024-01-10', 'Research data sharing and joint workshops.'),
('mou2', '2024-03-05', 'Technology transfer and innovation collaboration.'),
('mou3', '2024-04-22', 'International partnership on AI and cybersecurity.'),
('mou4', '2024-06-12', 'Academic exchange and capacity building.');

-- --------------------------------------------------------

--
-- Table structure for table `mou_project`
--

CREATE TABLE `mou_project` (
  `mou_id` varchar(36) NOT NULL,
  `project_id` varchar(36) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mou_project`
--

INSERT INTO `mou_project` (`mou_id`, `project_id`) VALUES
('mou1', 'p100'),
('mou1', 'p101'),
('mou2', 'p102'),
('mou3', 'p103'),
('mou4', 'p100');

-- --------------------------------------------------------

--
-- Table structure for table `project`
--

CREATE TABLE `project` (
  `project_id` varchar(36) NOT NULL,
  `project_title` varchar(255) NOT NULL,
  `project_lead` varchar(36) DEFAULT NULL,
  `status` enum('ongoing','completed','published') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project`
--

INSERT INTO `project` (`project_id`, `project_title`, `project_lead`, `status`, `start_date`, `end_date`) VALUES
('p100', 'IoT-based Smart Irrigation', 'r1', 'ongoing', '2025-07-01', '2025-12-11'),
('p101', 'Renewable Energy Microgrid Pilot', 'r3', 'published', '2024-01-10', '2024-12-20'),
('p102', 'Effective way of performing a backflip', 'r4', 'ongoing', '2025-11-26', '2026-04-17'),
('p103', 'How to shoot someone in the head', 'r2', 'published', '2025-11-28', '2025-11-29'),
('p104', 'Storing light in Rb vapor', 'r5', 'ongoing', '2025-12-31', '2026-06-25'),
('p105', 'Calculating the distance to the sun', 'r5', 'published', '2025-12-15', '2026-08-12');

-- --------------------------------------------------------

--
-- Table structure for table `project_collaboration`
--

CREATE TABLE `project_collaboration` (
  `project_id` varchar(36) NOT NULL,
  `collaboration_id` varchar(36) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_collaboration`
--

INSERT INTO `project_collaboration` (`project_id`, `collaboration_id`) VALUES
('p100', 'col_693ecc5621e19'),
('p105', 'col_693fb7446baaf');

-- --------------------------------------------------------

--
-- Table structure for table `project_collaborator`
--

CREATE TABLE `project_collaborator` (
  `project_id` varchar(36) NOT NULL,
  `collaboration_id` varchar(36) NOT NULL,
  `researcher_id` varchar(36) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_collaborator`
--

INSERT INTO `project_collaborator` (`project_id`, `collaboration_id`, `researcher_id`) VALUES
('p100', 'c100', 'r2'),
('p100', 'c101', 'r1'),
('p101', 'c102', 'r3');

-- --------------------------------------------------------

--
-- Table structure for table `project_funding`
--

CREATE TABLE `project_funding` (
  `project_id` varchar(36) NOT NULL,
  `funding_id` varchar(36) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_funding`
--

INSERT INTO `project_funding` (`project_id`, `funding_id`) VALUES
('p100', 'f100'),
('p102', 'f102'),
('p104', 'f103');

-- --------------------------------------------------------

--
-- Stand-in structure for view `project_progress`
-- (See below for the actual view)
--
CREATE TABLE `project_progress` (
`project_id` varchar(36)
,`project_title` varchar(255)
,`start_date` date
,`end_date` date
,`days_remaining` int(7)
,`days_elapsed` int(7)
);

-- --------------------------------------------------------

--
-- Table structure for table `project_task`
--

CREATE TABLE `project_task` (
  `task_id` varchar(36) NOT NULL,
  `project_id` varchar(36) NOT NULL,
  `task_name` varchar(255) NOT NULL,
  `task_description` text DEFAULT NULL,
  `task_status` enum('not_started','in_progress','completed') NOT NULL,
  `due_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_task`
--

INSERT INTO `project_task` (`task_id`, `project_id`, `task_name`, `task_description`, `task_status`, `due_date`) VALUES
('t101', 'p100', 'Gateway integration', 'LoRaWAN gateway + data pipeline', 'in_progress', '2025-09-01'),
('t102', 'p101', 'Finalize report', 'Closeout documentation and lessons', 'completed', '2024-12-10'),
('t103', 'p103', 'Shoot someone in the head once', 'only once', 'in_progress', '2025-12-11'),
('t104', 'p100', 'fkherkj', 'frger', 'not_started', '2025-12-25');

-- --------------------------------------------------------

--
-- Table structure for table `publication`
--

CREATE TABLE `publication` (
  `publication_id` varchar(36) NOT NULL,
  `project_id` varchar(36) NOT NULL,
  `title` varchar(255) NOT NULL,
  `type` enum('paper','journal','conference') NOT NULL,
  `department` varchar(120) NOT NULL,
  `publication_date` date NOT NULL,
  `citation_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `publication`
--

INSERT INTO `publication` (`publication_id`, `project_id`, `title`, `type`, `department`, `publication_date`, `citation_count`) VALUES
('pub101', 'p101', 'Renewable Energy Microgrid Pilot', 'conference', 'EEE', '2024-12-20', 7),
('pub_p103', 'p103', 'How to shoot someone in the head', 'paper', 'EEE', '2025-11-29', 0),
('pub_p105', 'p105', 'Calculating the distance to the sun', 'conference', 'Computer Science', '2026-08-12', 36);

-- --------------------------------------------------------

--
-- Table structure for table `report`
--

CREATE TABLE `report` (
  `project_id` varchar(36) NOT NULL,
  `researcher_id` varchar(36) NOT NULL,
  `collaboration_id` varchar(36) NOT NULL,
  `department` varchar(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `report`
--

INSERT INTO `report` (`project_id`, `researcher_id`, `collaboration_id`, `department`) VALUES
('p100', 'r1', 'c101', 'CSE'),
('p100', 'r2', 'c100', 'CSE'),
('p101', 'r3', 'c102', 'EEE');

-- --------------------------------------------------------

--
-- Table structure for table `researcher`
--

CREATE TABLE `researcher` (
  `researcher_id` varchar(36) NOT NULL,
  `f_name` varchar(100) NOT NULL,
  `l_name` varchar(100) NOT NULL,
  `department` varchar(120) NOT NULL,
  `email` varchar(150) NOT NULL,
  `type` enum('student','faculty') NOT NULL,
  `password` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `researcher`
--

INSERT INTO `researcher` (`researcher_id`, `f_name`, `l_name`, `department`, `email`, `type`, `password`) VALUES
('r1', 'Ayesha', 'Khan', 'CSE', 'ayesha@example.com', 'faculty', 'pass123'),
('r2', 'Rahim', 'Uddin', 'EEE', 'rahim@example.com', 'student', 'pass123'),
('r3', 'Imran', 'Hossain', 'EEE', 'imran@example.com', 'faculty', 'pass123'),
('r4', 'Sara', 'Ahmed', 'ME', 'sara@example.com', 'student', 'pass123'),
('r5', 'Shishir', 'Dhar', 'Computer Science', 'keumeu@example.com', 'student', 'pass123');

-- --------------------------------------------------------

--
-- Table structure for table `researcher_contact`
--

CREATE TABLE `researcher_contact` (
  `researcher_id` varchar(36) NOT NULL,
  `contact_no` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `researcher_contact`
--

INSERT INTO `researcher_contact` (`researcher_id`, `contact_no`) VALUES
('r1', '+8801700000001'),
('r2', '+8801700000003'),
('r3', '+8801700000004'),
('r4', '+8801700000005'),
('r5', '43434636');

-- --------------------------------------------------------

--
-- Table structure for table `researcher_profile`
--

CREATE TABLE `researcher_profile` (
  `researcher_id` varchar(36) NOT NULL,
  `biography` text DEFAULT NULL,
  `research_interests` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `researcher_profile`
--

INSERT INTO `researcher_profile` (`researcher_id`, `biography`, `research_interests`) VALUES
('r1', 'Works on IoT and embedded systems. Also shoots people when free', 'IoT, WSN, Precision Agriculture'),
('r2', 'Undergrad RA focusing on sensors.', 'Signal Processing, Low-power Devices'),
('r3', 'Microgrids and control specialist.', 'Power Systems, Control'),
('r4', 'Thermal systems enthusiast.', 'CFD, Heat Transfer'),
('r5', 'heheboi', 'Power Systems, Control');

-- --------------------------------------------------------

--
-- Table structure for table `researcher_project`
--

CREATE TABLE `researcher_project` (
  `project_id` varchar(36) NOT NULL,
  `researcher_id` varchar(36) NOT NULL,
  `role` varchar(80) DEFAULT 'member'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `researcher_project`
--

INSERT INTO `researcher_project` (`project_id`, `researcher_id`, `role`) VALUES
('p100', 'r1', 'lead'),
('p100', 'r2', 'RA'),
('p100', 'r4', 'RA'),
('p101', 'r3', 'lead'),
('p101', 'r4', 'member'),
('p102', 'r2', 'Co-op'),
('p102', 'r4', 'lead'),
('p103', 'r1', 'RA'),
('p103', 'r2', 'lead'),
('p104', 'r1', 'Assist'),
('p104', 'r2', 'Assist'),
('p104', 'r5', 'lead'),
('p105', 'r4', 'RA');

-- --------------------------------------------------------

--
-- Table structure for table `researcher_publication`
--

CREATE TABLE `researcher_publication` (
  `publication_id` varchar(36) NOT NULL,
  `researcher_id` varchar(36) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `researcher_publication`
--

INSERT INTO `researcher_publication` (`publication_id`, `researcher_id`) VALUES
('pub101', 'r3');

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `researcher_id` varchar(36) NOT NULL,
  `degree_program` varchar(120) NOT NULL,
  `year_level` varchar(20) DEFAULT NULL,
  `cgpa` decimal(3,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`researcher_id`, `degree_program`, `year_level`, `cgpa`) VALUES
('r2', 'BSc in Electrical Engineering', '3', 3.85),
('r4', 'BSc in Mechanical Engineering', '2', 3.75),
('r5', 'Bachelors', '2', 3.98);

-- --------------------------------------------------------

--
-- Structure for view `funding_with_amount_left`
--
DROP TABLE IF EXISTS `funding_with_amount_left`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `funding_with_amount_left`  AS SELECT `f`.`funding_id` AS `funding_id`, `f`.`agency_name` AS `agency_name`, `f`.`total_grant` AS `total_grant`, `f`.`total_grant`- coalesce(sum(`e`.`amount`),0) AS `amount_left` FROM (`funding` `f` left join `expenditures` `e` on(`f`.`funding_id` = `e`.`funding_id`)) GROUP BY `f`.`funding_id`, `f`.`agency_name`, `f`.`total_grant` ;

-- --------------------------------------------------------

--
-- Structure for view `project_progress`
--
DROP TABLE IF EXISTS `project_progress`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `project_progress`  AS SELECT `p`.`project_id` AS `project_id`, `p`.`project_title` AS `project_title`, `p`.`start_date` AS `start_date`, `p`.`end_date` AS `end_date`, to_days(`p`.`end_date`) - to_days(curdate()) AS `days_remaining`, to_days(curdate()) - to_days(`p`.`start_date`) AS `days_elapsed` FROM `project` AS `p` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `collaboration`
--
ALTER TABLE `collaboration`
  ADD PRIMARY KEY (`collaboration_id`);

--
-- Indexes for table `expenditures`
--
ALTER TABLE `expenditures`
  ADD PRIMARY KEY (`funding_id`,`expenditure_id`);

--
-- Indexes for table `faculty`
--
ALTER TABLE `faculty`
  ADD PRIMARY KEY (`researcher_id`);

--
-- Indexes for table `funding`
--
ALTER TABLE `funding`
  ADD PRIMARY KEY (`funding_id`);

--
-- Indexes for table `mou`
--
ALTER TABLE `mou`
  ADD PRIMARY KEY (`mou_id`);

--
-- Indexes for table `mou_project`
--
ALTER TABLE `mou_project`
  ADD PRIMARY KEY (`mou_id`,`project_id`),
  ADD KEY `fk_mp_project` (`project_id`);

--
-- Indexes for table `project`
--
ALTER TABLE `project`
  ADD PRIMARY KEY (`project_id`),
  ADD KEY `project_lead` (`project_lead`);

--
-- Indexes for table `project_collaboration`
--
ALTER TABLE `project_collaboration`
  ADD PRIMARY KEY (`project_id`,`collaboration_id`),
  ADD KEY `fk_pc_collab` (`collaboration_id`);

--
-- Indexes for table `project_collaborator`
--
ALTER TABLE `project_collaborator`
  ADD PRIMARY KEY (`project_id`,`collaboration_id`,`researcher_id`),
  ADD KEY `fk_pcol_collab` (`collaboration_id`),
  ADD KEY `fk_pcol_researcher` (`researcher_id`);

--
-- Indexes for table `project_funding`
--
ALTER TABLE `project_funding`
  ADD PRIMARY KEY (`project_id`,`funding_id`),
  ADD KEY `fk_pf_funding` (`funding_id`);

--
-- Indexes for table `project_task`
--
ALTER TABLE `project_task`
  ADD PRIMARY KEY (`task_id`),
  ADD KEY `fk_project_task` (`project_id`);

--
-- Indexes for table `publication`
--
ALTER TABLE `publication`
  ADD PRIMARY KEY (`publication_id`),
  ADD KEY `fk_pub_project` (`project_id`);

--
-- Indexes for table `report`
--
ALTER TABLE `report`
  ADD PRIMARY KEY (`project_id`,`researcher_id`,`collaboration_id`),
  ADD KEY `fk_rep_researcher` (`researcher_id`),
  ADD KEY `fk_rep_collab` (`collaboration_id`);

--
-- Indexes for table `researcher`
--
ALTER TABLE `researcher`
  ADD PRIMARY KEY (`researcher_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `researcher_contact`
--
ALTER TABLE `researcher_contact`
  ADD PRIMARY KEY (`researcher_id`,`contact_no`);

--
-- Indexes for table `researcher_profile`
--
ALTER TABLE `researcher_profile`
  ADD PRIMARY KEY (`researcher_id`);

--
-- Indexes for table `researcher_project`
--
ALTER TABLE `researcher_project`
  ADD PRIMARY KEY (`project_id`,`researcher_id`),
  ADD KEY `fk_rp_researcher` (`researcher_id`);

--
-- Indexes for table `researcher_publication`
--
ALTER TABLE `researcher_publication`
  ADD PRIMARY KEY (`publication_id`,`researcher_id`),
  ADD KEY `fk_researcher` (`researcher_id`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`researcher_id`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `expenditures`
--
ALTER TABLE `expenditures`
  ADD CONSTRAINT `fk_expenditures_funding` FOREIGN KEY (`funding_id`) REFERENCES `funding` (`funding_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `faculty`
--
ALTER TABLE `faculty`
  ADD CONSTRAINT `fk_faculty_researcher` FOREIGN KEY (`researcher_id`) REFERENCES `researcher` (`researcher_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `mou_project`
--
ALTER TABLE `mou_project`
  ADD CONSTRAINT `fk_mp_mou` FOREIGN KEY (`mou_id`) REFERENCES `mou` (`mou_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mp_project` FOREIGN KEY (`project_id`) REFERENCES `project` (`project_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `project`
--
ALTER TABLE `project`
  ADD CONSTRAINT `fk_project_lead` FOREIGN KEY (`project_lead`) REFERENCES `researcher` (`researcher_id`) ON DELETE SET NULL;

--
-- Constraints for table `project_collaboration`
--
ALTER TABLE `project_collaboration`
  ADD CONSTRAINT `fk_pc_collab` FOREIGN KEY (`collaboration_id`) REFERENCES `collaboration` (`collaboration_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pc_project` FOREIGN KEY (`project_id`) REFERENCES `project` (`project_id`) ON DELETE CASCADE;

--
-- Constraints for table `project_collaborator`
--
ALTER TABLE `project_collaborator`
  ADD CONSTRAINT `fk_pcol_collab` FOREIGN KEY (`collaboration_id`) REFERENCES `collaboration` (`collaboration_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pcol_project` FOREIGN KEY (`project_id`) REFERENCES `project` (`project_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pcol_researcher` FOREIGN KEY (`researcher_id`) REFERENCES `researcher` (`researcher_id`) ON DELETE CASCADE;

--
-- Constraints for table `project_funding`
--
ALTER TABLE `project_funding`
  ADD CONSTRAINT `fk_pf_funding` FOREIGN KEY (`funding_id`) REFERENCES `funding` (`funding_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pf_project` FOREIGN KEY (`project_id`) REFERENCES `project` (`project_id`) ON DELETE CASCADE;

--
-- Constraints for table `project_task`
--
ALTER TABLE `project_task`
  ADD CONSTRAINT `fk_project_task` FOREIGN KEY (`project_id`) REFERENCES `project` (`project_id`) ON DELETE CASCADE;

--
-- Constraints for table `publication`
--
ALTER TABLE `publication`
  ADD CONSTRAINT `fk_pub_project` FOREIGN KEY (`project_id`) REFERENCES `project` (`project_id`) ON DELETE CASCADE;

--
-- Constraints for table `report`
--
ALTER TABLE `report`
  ADD CONSTRAINT `fk_rep_collab` FOREIGN KEY (`collaboration_id`) REFERENCES `collaboration` (`collaboration_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rep_project` FOREIGN KEY (`project_id`) REFERENCES `project` (`project_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rep_researcher` FOREIGN KEY (`researcher_id`) REFERENCES `researcher` (`researcher_id`) ON DELETE CASCADE;

--
-- Constraints for table `researcher_contact`
--
ALTER TABLE `researcher_contact`
  ADD CONSTRAINT `fk_rc_researcher` FOREIGN KEY (`researcher_id`) REFERENCES `researcher` (`researcher_id`) ON DELETE CASCADE;

--
-- Constraints for table `researcher_profile`
--
ALTER TABLE `researcher_profile`
  ADD CONSTRAINT `fk_researcher_profile` FOREIGN KEY (`researcher_id`) REFERENCES `researcher` (`researcher_id`) ON DELETE CASCADE;

--
-- Constraints for table `researcher_project`
--
ALTER TABLE `researcher_project`
  ADD CONSTRAINT `fk_rp_project` FOREIGN KEY (`project_id`) REFERENCES `project` (`project_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rp_researcher` FOREIGN KEY (`researcher_id`) REFERENCES `researcher` (`researcher_id`) ON DELETE CASCADE;

--
-- Constraints for table `researcher_publication`
--
ALTER TABLE `researcher_publication`
  ADD CONSTRAINT `fk_publication` FOREIGN KEY (`publication_id`) REFERENCES `publication` (`publication_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_researcher` FOREIGN KEY (`researcher_id`) REFERENCES `researcher` (`researcher_id`) ON DELETE CASCADE;

--
-- Constraints for table `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `fk_student_researcher` FOREIGN KEY (`researcher_id`) REFERENCES `researcher` (`researcher_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
