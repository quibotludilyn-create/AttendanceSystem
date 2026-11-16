-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 16, 2026 at 06:50 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.5.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `attendance_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `time_in` time DEFAULT NULL,
  `status` enum('present','absent','late') DEFAULT 'present',
  `subject` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `location_verified` tinyint(1) DEFAULT 0,
  `is_manual` tinyint(1) DEFAULT 0,
  `manual_by` int(11) DEFAULT NULL,
  `manual_reason` varchar(255) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `student_id`, `date`, `time_in`, `status`, `subject`, `latitude`, `longitude`, `location_verified`, `is_manual`, `manual_by`, `manual_reason`, `subject_id`, `teacher_id`, `recorded_by`, `created_at`) VALUES
(17, 14, '2026-11-15', '14:44:04', 'present', 'Introduction to Programming', 9.32190400, 123.29148900, 1, 0, NULL, NULL, 1, 1, NULL, '2026-11-15 06:44:04'),
(18, 15, '2026-11-15', '21:42:26', 'late', 'General', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, '2026-11-15 13:42:27'),
(19, 17, '2026-11-15', '15:45:19', 'absent', 'Introduction to Programming', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 1, '2026-11-15 14:45:19');

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`id`, `subject_id`, `student_id`, `enrolled_at`) VALUES
(11, 1, 14, '2026-11-15 06:32:50'),
(12, 1, 15, '2026-11-15 08:18:01'),
(16, 1, 17, '2026-11-15 14:27:58');

-- --------------------------------------------------------

--
-- Table structure for table `feedbacks`
--

CREATE TABLE `feedbacks` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `role` enum('student','teacher','admin','other') DEFAULT 'student',
  `rating` tinyint(4) NOT NULL DEFAULT 5,
  `message` text NOT NULL,
  `is_visible` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedbacks`
--

INSERT INTO `feedbacks` (`id`, `name`, `student_id`, `role`, `rating`, `message`, `is_visible`, `created_at`) VALUES
(1, 'Maria Santos', NULL, 'student', 5, 'Grabe ka dali mag-record og attendance! Dili na kinahanglan mag-sign manually. Love it!', 1, '2026-10-23 03:04:40'),
(2, 'Jun Reyes', NULL, 'teacher', 5, 'As a teacher, napakadali na ng attendance monitoring. Real-time ang results at may notifications pa!', 1, '2026-10-23 03:04:40'),
(3, 'Ana dela Cruz', NULL, 'student', 5, 'The face recognition is so accurate and fast. Very convenient especially on busy days.', 1, '2026-10-23 03:04:40'),
(4, 'sheila', NULL, 'other', 5, 'all i can say this is very helpful to the teacher and student', 1, '2026-10-23 04:15:02'),
(5, 'sheila', NULL, 'other', 5, 'ddsgdsgdsgdsgdsgs', 1, '2026-10-23 04:22:14'),
(6, 'ere', NULL, 'student', 5, 'wqeeqw erwrwer', 1, '2026-10-23 04:23:24'),
(7, 'she', NULL, 'other', 5, 'sdjdhjskhd', 1, '2026-10-24 03:40:27'),
(14, 'quibot.ludilyn@mdci.edu.ph', NULL, 'student', 5, 'fdsf gdr fgr fgedb ret', 1, '2026-10-24 05:17:58'),
(15, 'test@gmail.com', NULL, 'student', 5, 'Great system! Love the face recognition feature.', 1, '2026-10-24 05:20:05'),
(16, 'quibot.ludilyn@mdci.edu.ph', NULL, 'student', 5, 'eresrsev   eewre ewer', 1, '2026-10-24 05:22:17'),
(17, 'quibot.ludilyn@mdci.edu.ph', NULL, 'student', 5, 'sdsfs a fsf sf sf sf sf 11111244335 55', 1, '2026-10-24 05:23:13'),
(18, 'sheilamaesolamillo@gmail.com', NULL, 'student', 5, 'sdnsjadsjasdasdsadsad', 1, '2026-11-13 05:48:45'),
(19, 'sheilamaesolamillo@gmail.com', NULL, 'student', 5, 'EWWRQW DFDS SDFSD', 1, '2026-11-13 05:58:36'),
(20, 'quibotludilyn1@gmail.com', NULL, 'student', 5, 'nice system  guiz', 1, '2026-11-14 14:33:43');

-- --------------------------------------------------------

--
-- Table structure for table `location_settings`
--

CREATE TABLE `location_settings` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL DEFAULT 'School Location',
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `radius_meters` int(11) NOT NULL DEFAULT 100,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `location_settings`
--

INSERT INTO `location_settings` (`id`, `name`, `latitude`, `longitude`, `radius_meters`, `is_active`, `created_by`, `updated_at`) VALUES
(1, 'Catan Residence pulangtubig', 9.32190400, 123.29148900, 10, 1, 1, '2026-11-14 14:26:02');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `recipient_type` enum('admin','teacher','student') NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `type` enum('attendance','schedule','system','manual') DEFAULT 'attendance',
  `is_read` tinyint(1) DEFAULT 0,
  `link` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `recipient_type`, `recipient_id`, `title`, `message`, `type`, `is_read`, `link`, `created_at`) VALUES
(1, 'admin', 1, 'New Teacher Registration', 'Teacher Juan Dela Cruz (0001) has registered and awaits approval.', 'system', 1, NULL, '2026-10-14 11:03:23'),
(2, 'teacher', 2, 'Account Approved', 'Your teacher account has been approved. You can now login to AttendEase.', 'system', 1, NULL, '2026-10-14 11:07:07'),
(3, 'teacher', 1, 'Attendance Recorded', 'Ludilyn Quibot has been marked Late in Introduction to Programming (a) at 10:09 PM on October 14, 2026.', 'attendance', 1, NULL, '2026-10-14 14:09:12'),
(4, 'student', 6, 'Attendance Recorded', 'Your attendance for Introduction to Programming (a) has been recorded as Late at 10:09 PM on October 14, 2026.', 'attendance', 1, NULL, '2026-10-14 14:09:12'),
(5, 'admin', 1, 'Attendance Recorded', 'Ludilyn Quibot marked Late in Introduction to Programming (a) — Teacher: Sample Teacher at 10:09 PM.', 'attendance', 1, NULL, '2026-10-14 14:09:12'),
(6, 'admin', 1, 'Test Notification', 'This is a real-time test notification. If you see this popup, notifications are working!', 'system', 1, NULL, '2026-10-24 04:36:14'),
(7, 'admin', 1, '?? Notification Test', 'Notifications are working! You will see this popup when you open any admin page.', 'system', 1, NULL, '2026-10-24 04:45:41'),
(8, 'admin', 1, '????? New Feedback Received', '????? From: test@gmail.com (student) ù Great system! Love the face recognition feature.', 'system', 1, NULL, '2026-10-24 05:20:24'),
(9, 'admin', 1, 'New Feedback Received ★★★★★', '????‍???? From: quibot.ludilyn@mdci.edu.ph (student) — \"eresrsev   eewre ewer\"', 'system', 1, NULL, '2026-10-24 05:22:18'),
(10, 'admin', 1, 'New Feedback Received ★★★★★', '????‍???? From: quibot.ludilyn@mdci.edu.ph (student) — \"sdsfs a fsf sf sf sf sf 11111244335 55\"', 'system', 1, NULL, '2026-10-24 05:23:13'),
(11, 'teacher', 1, 'Attendance Recorded', 'Sheila Mae Anitan Solamillo has been marked Late in Introduction to Programming (a) at 11:55 AM on October 26, 2026.', 'attendance', 1, NULL, '2026-10-26 03:55:36'),
(12, 'student', 7, 'Attendance Recorded', 'Your attendance for Introduction to Programming (a) has been recorded as Late at 11:55 AM on October 26, 2026.', 'attendance', 1, NULL, '2026-10-26 03:55:36'),
(13, 'admin', 1, 'Attendance Recorded', 'Sheila Mae Anitan Solamillo marked Late in Introduction to Programming (a) — Teacher: Sample Teacher at 11:55 AM.', 'attendance', 1, NULL, '2026-10-26 03:55:36'),
(14, 'teacher', 1, 'Attendance Recorded', 'Ludilyn Quibot has been marked Late in Introduction to Programming (a) at 12:50 PM on October 26, 2026.', 'attendance', 1, NULL, '2026-10-26 04:50:50'),
(15, 'student', 6, 'Attendance Recorded', 'Your attendance for Introduction to Programming (a) has been recorded as Late at 12:50 PM on October 26, 2026.', 'attendance', 1, NULL, '2026-10-26 04:50:50'),
(16, 'admin', 1, 'Attendance Recorded', 'Ludilyn Quibot marked Late in Introduction to Programming (a) — Teacher: Sample Teacher at 12:50 PM.', 'attendance', 1, NULL, '2026-10-26 04:50:50'),
(17, 'teacher', 1, 'Attendance Recorded', 'Ludilyn Quibot has been marked Late in Introduction to Programming (a) at 11:19 AM on October 30, 2026.', 'attendance', 1, NULL, '2026-10-30 03:19:30'),
(18, 'student', 6, 'Attendance Recorded', 'Your attendance for Introduction to Programming (a) has been recorded as Late at 11:19 AM on October 30, 2026.', 'attendance', 1, NULL, '2026-10-30 03:19:30'),
(19, 'admin', 1, 'Attendance Recorded', 'Ludilyn Quibot marked Late in Introduction to Programming (a) — Teacher: Sample Teacher at 11:19 AM.', 'attendance', 1, NULL, '2026-10-30 03:19:30'),
(21, 'student', 12, 'Attendance Recorded', 'Your attendance for Introduction to Programming (a) has been recorded as Late at 12:12 PM on November 13, 2026.', 'attendance', 1, NULL, '2026-11-13 04:12:50'),
(22, 'admin', 1, 'Attendance Recorded', 'Maemae Solamillo marked Late in Introduction to Programming (a) — Teacher: Sample Teacher at 12:12 PM.', 'attendance', 1, NULL, '2026-11-13 04:12:50'),
(23, 'admin', 1, 'New Feedback Received ★★★★★', '????‍???? From: sheilamaesolamillo@gmail.com (student) — \"sdnsjadsjasdasdsadsad\"', 'system', 1, NULL, '2026-11-13 05:48:45'),
(24, 'admin', 1, 'New Feedback Received ★★★★★', '????‍???? From: sheilamaesolamillo@gmail.com (student) — \"EWWRQW DFDS SDFSD\"', 'system', 1, NULL, '2026-11-13 05:58:36'),
(25, 'admin', 1, 'New Feedback Received ★★★★★', '????‍???? From: quibotludilyn1@gmail.com (student) — \"nice system  guiz\"', 'system', 1, NULL, '2026-11-14 14:33:43'),
(27, 'student', 13, 'Attendance Recorded', 'Your attendance for Introduction to Programming (a) has been recorded as Late at 01:35 PM on November 15, 2026.', 'attendance', 1, NULL, '2026-11-15 05:35:52'),
(28, 'admin', 1, 'Attendance Recorded', 'Ludilyn Quiboy marked Late in Introduction to Programming (a) — Teacher: Sample Teacher at 01:35 PM.', 'attendance', 1, NULL, '2026-11-15 05:35:52'),
(30, 'student', 13, 'Attendance Recorded', 'Your attendance for math (A) has been recorded as Late at 01:52 PM on November 15, 2026.', 'attendance', 1, NULL, '2026-11-15 05:52:54'),
(31, 'admin', 1, 'Attendance Recorded', 'Ludilyn Quiboy marked Late in math (A) — Teacher: Sample Teacher at 01:52 PM.', 'attendance', 1, NULL, '2026-11-15 05:52:54'),
(34, 'admin', 1, 'Attendance Recorded', 'Sheila Mae Anitan Solamillo marked Present in Introduction to Programming (a) — Teacher: Sample Teacher at 02:44 PM.', 'attendance', 1, NULL, '2026-11-15 06:44:04'),
(35, 'admin', 1, 'New Teacher Registration', 'Teacher Ludilyn Quiboy (123456) has registered and awaits approval.', 'system', 0, NULL, '2026-11-15 14:51:56'),
(36, 'teacher', 3, 'Account Approved', 'Your teacher account has been approved. You can now login to AttendEase.', 'system', 0, NULL, '2026-11-15 14:54:51'),
(37, 'teacher', 3, 'Account Approved', 'Your teacher account has been approved. You can now login to AttendEase.', 'system', 0, NULL, '2026-11-15 15:13:28');

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room` varchar(60) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`id`, `subject_id`, `day_of_week`, `start_time`, `end_time`, `room`, `created_at`) VALUES
(6, 1, 'Sunday', '14:40:00', '15:40:00', 'room 201', '2026-11-15 06:39:37');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `course` varchar(100) DEFAULT NULL,
  `year_level` int(11) DEFAULT NULL,
  `section` varchar(20) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `google_email` varchar(150) DEFAULT NULL,
  `google_id` varchar(100) DEFAULT NULL,
  `google_picture` varchar(500) DEFAULT NULL,
  `profile_picture` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `face_descriptor` text DEFAULT NULL,
  `face_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `email_verified_at` datetime DEFAULT NULL,
  `email_verification_token_hash` char(64) DEFAULT NULL,
  `email_verification_expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `student_id`, `full_name`, `course`, `year_level`, `section`, `password`, `email`, `google_email`, `google_id`, `google_picture`, `profile_picture`, `is_active`, `face_descriptor`, `face_image`, `created_at`, `email_verified_at`, `email_verification_token_hash`, `email_verification_expires_at`) VALUES
(14, '2026-0003', 'Sheila Mae Anitan Solamillo', 'BSIT', 1, 'B', '$2y$12$av/mSYvKcpJ9vzqSXnnL4eZX.2dpk82xkm5CKU1ONPRYkyFYL9w3C', 'sheilamaesolamillo@gmail.com', 'sheilamaesolamillo@gmail.com', NULL, NULL, '../uploads/profiles/student_14_1794740705.jpg', 1, '[-0.1657177358865738,0.13398580253124237,0.08659366518259048,-0.1554563045501709,-0.0881652683019638,0.005346586927771568,-0.006871221587061882,-0.11802548170089722,0.18936023116111755,-0.1740671694278717,0.27749624848365784,-0.06389711052179337,-0.28960734605789185,-0.04558326303958893,-0.04756203293800354,0.24723578989505768,-0.18972720205783844,-0.1358199268579483,-0.07261252403259277,-0.011182068847119808,0.010606504045426846,0.0168217271566391,0.0539521686732769,0.04194936901330948,-0.1033027246594429,-0.41118124127388,-0.1068960577249527,-0.016436228528618813,-0.10107412934303284,-0.058165304362773895,-0.0647006630897522,0.07193467766046524,-0.20275865495204926,-0.037359870970249176,0.0022066221572458744,0.13393832743167877,-0.04382376745343208,-0.053885091096162796,0.13584165275096893,0.008404524065554142,-0.22373829782009125,-0.020017363131046295,0.06640435010194778,0.2873632609844208,0.2136775553226471,0.023882338777184486,-0.009263025596737862,-0.10755449533462524,0.07098159193992615,-0.19330866634845734,0.03748181462287903,0.06424041092395782,0.05991915240883827,0.004344376269727945,0.061884380877017975,-0.17942625284194946,0.021124498918652534,0.14392273128032684,-0.11014252156019211,-0.06780650466680527,0.04493994265794754,-0.13228613138198853,-0.033437203615903854,-0.08410178124904633,0.2526114881038666,0.11631500720977783,-0.14735233783721924,-0.1232454776763916,0.16708168387413025,-0.04130842909216881,0.0315377339720726,0.051299504935741425,-0.17910929024219513,-0.1747456043958664,-0.30205443501472473,-0.012397504411637783,0.39813414216041565,0.07993339002132416,-0.17389462888240814,-0.005880151875317097,-0.05181727930903435,0.04116703197360039,0.11878444999456406,0.1550106704235077,-0.040171682834625244,0.011430133134126663,-0.14783859252929688,0.059326257556676865,0.13929127156734467,-0.043146759271621704,-0.0577753446996212,0.21099282801151276,-0.05035537853837013,0.035095635801553726,0.029594672843813896,0.058642759919166565,-0.13813011348247528,0.039351485669612885,-0.1708863228559494,-0.027087939903140068,0.021454740315675735,-0.05045412853360176,-0.05858216434717178,0.15345127880573273,-0.15809710323810577,0.09030935913324356,0.006133458577096462,0.04719094932079315,0.0015030298382043839,-0.035374533385038376,-0.02781047858297825,-0.06302366405725479,0.08789900690317154,-0.24871288239955902,0.09416552633047104,0.1806277483701706,-0.07949689775705338,0.16879837214946747,0.0898423120379448,0.07324439287185669,-0.03810233995318413,-0.024549473077058792,-0.24200421571731567,0.013518150895833969,0.0973193347454071,-0.010987314395606518,0.14574743807315826,0.014842670410871506]', NULL, '2026-11-15 06:31:36', '2026-11-15 14:36:23', NULL, NULL),
(15, '2026-0004', 'Sol She', 'BSIT', 1, 'A', '$2y$12$JwAK9F0TIZh0VOBmbbz99OdRu8hiR1eFeEK8/8wvVmA3nllML5Fum', 'solshe27@gmail.com', 'solshe27@gmail.com', NULL, NULL, NULL, 1, '[-0.10488730669021606,0.13373658061027527,0.08963464945554733,-0.13497155904769897,-0.09154874831438065,0.0295125562697649,0.0022249051835387945,-0.12131944298744202,0.20939426124095917,-0.19027137756347656,0.2572297751903534,-0.0800885483622551,-0.29464322328567505,-0.10981667041778564,-0.05753178894519806,0.2639261484146118,-0.18431195616722107,-0.13614685833454132,-0.042003802955150604,-0.009672706015408039,0.002900581806898117,0.008223025128245354,0.07956314831972122,0.050407178699970245,-0.07936317473649979,-0.421001136302948,-0.10674732178449631,-0.03261760249733925,-0.06922183930873871,-0.054018400609493256,-0.1077156588435173,0.06866588443517685,-0.19064299762248993,-0.05891329050064087,-0.01120748557150364,0.11040650308132172,-0.06220336630940437,-0.05540235713124275,0.13896752893924713,0.014862728305161,-0.22775326669216156,-0.0497538186609745,0.07536855340003967,0.28539136052131653,0.18947230279445648,0.015112262219190598,0.00827213004231453,-0.10699159651994705,0.06806469708681107,-0.20058156549930573,0.04396859183907509,0.07104216516017914,0.049898598343133926,0.01076599769294262,0.04983549937605858,-0.1646532267332077,-0.011721032671630383,0.15918612480163574,-0.10661023110151291,-0.031355053186416626,0.03790893405675888,-0.13524603843688965,-0.0543222539126873,-0.06937824189662933,0.27745285630226135,0.12895631790161133,-0.14437082409858704,-0.13040891289710999,0.1837376356124878,-0.061906132847070694,0.020538603886961937,0.026108520105481148,-0.17016738653182983,-0.147508442401886,-0.32681116461753845,-0.008561708964407444,0.4152674973011017,0.07137046009302139,-0.13539838790893555,0.002044000895693898,-0.06464439630508423,0.037181489169597626,0.10929775983095169,0.20815221965312958,-0.0407603494822979,0.03664621710777283,-0.1110989972949028,0.06645042449235916,0.1002354621887207,-0.07779623568058014,-0.04391897842288017,0.20829588174819946,-0.047468431293964386,0.06519179046154022,0.016230393201112747,0.04490398243069649,-0.12481821328401566,0.052525486797094345,-0.1817169189453125,-0.009885053150355816,0.024246137589216232,-0.06025174632668495,-0.020324591547250748,0.14242465794086456,-0.15851834416389465,0.060788825154304504,0.02787918783724308,0.020282244309782982,0.046289894729852676,-0.026689769700169563,-0.0355907678604126,-0.09507233649492264,0.08524592965841293,-0.25441238284111023,0.09769841283559799,0.18489426374435425,-0.03894633799791336,0.1729593575000763,0.0988333597779274,0.09248064458370209,-0.0011438749497756362,-0.058809272944927216,-0.20973961055278778,0.035631000995635986,0.10179803520441055,-0.018495123833417892,0.10997028648853302,0.011874010786414146]', NULL, '2026-11-15 08:15:20', '2026-11-15 16:21:36', NULL, NULL),
(17, '2026-0001', 'Ludilyn Quibot', 'BSIT', 1, 'A', '$2y$12$5.F6e8ZW9r7rP7vNM1kp9OVe7NesaqHKxa19W6nB3Srwq4CGbPyTy', 'quibotludilyn1@gmail.com', 'quibotludilyn1@gmail.com', NULL, NULL, NULL, 1, '[-0.18878187239170074,0.11783570051193237,0.05197908356785774,-0.03616458922624588,-0.09548445791006088,-0.06663402915000916,0.009771348908543587,-0.1544254571199417,0.20738662779331207,-0.05422546714544296,0.2645992636680603,-0.02663215808570385,-0.16533246636390686,-0.12580059468746185,0.004959718324244022,0.18528228998184204,-0.18284188210964203,-0.1086118146777153,-0.030891388654708862,-0.004678716883063316,-0.03346705809235573,-0.016242343932390213,0.06701459735631943,0.11715507507324219,-0.05496152490377426,-0.4365510642528534,-0.10739974677562714,-0.14854851365089417,0.04889374598860741,-0.05806988850235939,-0.0369383841753006,0.1193835660815239,-0.17679262161254883,-0.10273922234773636,-0.02069980837404728,0.04537210986018181,0.027676885947585106,0.036363616585731506,0.2094612717628479,0.04221441224217415,-0.22402571141719818,-0.05563753843307495,0.016868535429239273,0.28236690163612366,0.1725471019744873,0.038453176617622375,0.03801410645246506,-0.07212193310260773,0.029105784371495247,-0.18256723880767822,0.03423599898815155,0.07987252622842789,0.07857310026884079,0.023290421813726425,-0.016343854367733,-0.18324987590312958,-0.016309507191181183,0.039967507123947144,-0.22618986666202545,0.04859133064746857,0.048404671251773834,-0.11464861780405045,-0.07875242829322815,-0.008796569891273975,0.3278519809246063,0.14213815331459045,-0.1482403576374054,-0.09307167679071426,0.10245152562856674,-0.1279471218585968,-0.005437290295958519,0.06694834679365158,-0.1222822517156601,-0.1906089335680008,-0.3585968315601349,0.06988361477851868,0.42052266001701355,0.1091417744755745,-0.23751425743103027,-0.026383424177765846,-0.14047884941101074,-0.03335119038820267,0.0919136330485344,0.1693352609872818,-0.06362909823656082,0.029385654255747795,-0.08150506764650345,-0.030815061181783676,0.08816591650247574,0.021241294220089912,-0.04377151280641556,0.2614372670650482,-0.028178080916404724,0.11352077126502991,-0.0070101250894367695,0.0950692892074585,-0.07228397578001022,-0.021809276193380356,-0.12163417041301727,-0.048283711075782776,0.031728968024253845,0.0036320830695331097,0.01335993967950344,0.09319084882736206,-0.2050304263830185,0.13321548700332642,0.03161441534757614,-0.0008244989439845085,0.005595061928033829,0.005752332508563995,-0.043896306306123734,-0.09658921509981155,0.1413419544696808,-0.24974732100963593,0.1817294955253601,0.19963929057121277,-0.004459862597286701,0.2082449495792389,0.0812537893652916,0.050366830080747604,-0.04395581781864166,-0.04839523136615753,-0.1089961901307106,0.0460713654756546,0.13114170730113983,-0.08873973041772842,0.0886748656630516,0.01346958801150322]', NULL, '2026-11-15 14:26:48', '2026-11-15 22:35:05', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `subject_code` varchar(30) NOT NULL,
  `subject_name` varchar(150) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `section` varchar(30) NOT NULL,
  `course` varchar(100) DEFAULT NULL,
  `year_level` tinyint(4) DEFAULT 1,
  `room` varchar(60) DEFAULT NULL,
  `school_year` varchar(20) DEFAULT NULL,
  `semester` enum('1st','2nd','Summer') DEFAULT '1st',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `teacher_id`, `section`, `course`, `year_level`, `room`, `school_year`, `semester`, `is_active`, `created_at`) VALUES
(1, 'CS101', 'Introduction to Programming', 1, 'a', '', 1, 'room 201', '2026-2027', '1st', 1, '2026-10-14 11:55:02');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(30) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `username` varchar(60) NOT NULL,
  `password` varchar(255) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_approved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `employee_id`, `full_name`, `email`, `username`, `password`, `department`, `phone`, `profile_pic`, `is_active`, `is_approved`, `created_at`) VALUES
(1, 'EMP-001', 'Sample Teacher', 'teacher@school.edu', 'teacher1', '$2y$12$3BUoDRlH5ps6Ov.4XyL7teC9sEgvQ6KQ6ou4/a1PVBjJNqkSDk5Du', 'Computer Science', '', NULL, 1, 1, '2026-10-14 10:39:48'),
(2, '0001', 'Juan Dela Cruz', 'teacher@shool.edu', 'JdelCruz', '$2y$12$99AihshRahdYpoi2cv8vF.fxcg2NETfDeQ2BPkxHoKY6OxyRzED6W', 'BSIT', '0912 345 6789', NULL, 1, 1, '2026-10-14 11:03:22'),
(3, '123456', 'Ludilyn Quiboy', 'quibot.ludilyn@mdci.edu.ph', 'Lyn', '$2y$12$TR7nXK/ACTPO2KS3nkJ4tueXEsTLjbvoKLSoF9bcTd82UGp/c5Wxy', 'Bsit dept', '09512269475', NULL, 1, 1, '2026-11-15 14:51:56');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_locations`
--

CREATE TABLE `teacher_locations` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL DEFAULT 'My Classroom',
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `radius_meters` int(11) NOT NULL DEFAULT 100,
  `is_active` tinyint(1) DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_locations`
--

INSERT INTO `teacher_locations` (`id`, `teacher_id`, `subject_id`, `name`, `latitude`, `longitude`, `radius_meters`, `is_active`, `updated_at`) VALUES
(11, 1, NULL, 'My Classroom', 9.32143584, 123.29086804, 100, 1, '2026-11-14 15:42:24');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','teacher') DEFAULT 'teacher',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$12$.7wLDVz10Gl51yBM69r1mO9wuPNXCYNTzXyZtRLzZukrtjJOJg4O6', 'Administrator', 'admin', '2026-09-28 02:55:22');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `recorded_by` (`recorded_by`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_enroll` (`subject_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `location_settings`
--
ALTER TABLE `location_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `teacher_locations`
--
ALTER TABLE `teacher_locations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `feedbacks`
--
ALTER TABLE `feedbacks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `location_settings`
--
ALTER TABLE `location_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `teacher_locations`
--
ALTER TABLE `teacher_locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_locations`
--
ALTER TABLE `teacher_locations`
  ADD CONSTRAINT `teacher_locations_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_locations_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
