-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 14, 2026 at 12:11 PM
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
-- Database: `campushub_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `society_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `venue` varchar(255) DEFAULT NULL,
  `event_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time DEFAULT NULL,
  `poster_path` varchar(255) DEFAULT NULL,
  `registration_link` varchar(255) DEFAULT NULL,
  `event_mode` enum('physical','online','hybrid') DEFAULT 'physical',
  `status` enum('upcoming','ongoing','completed','cancelled') DEFAULT 'upcoming',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `society_id`, `title`, `description`, `category`, `venue`, `event_date`, `start_time`, `end_time`, `poster_path`, `registration_link`, `event_mode`, `status`, `created_at`) VALUES
(5, 9, 'AI Workshop 2026', 'Hands-on AI and machine learning workshop for beginners.', 'Workshop', 'Main Auditorium', '2026-06-25', '09:00:00', '17:00:00', 'ai_workshop.jpg', 'https://forms.gle/abc123xyz', 'physical', 'upcoming', '2026-06-14 05:30:26'),
(6, 9, 'Tech Fest 2026', 'Annual technology exhibition with project displays.', 'Technology', 'CS Building', '2026-07-15', '10:00:00', '18:00:00', 'tech_fest.jpg', 'https://forms.gle/def456uvw', 'physical', 'upcoming', '2026-06-14 05:30:26'),
(7, 9, 'CodeFest 2026', '24-hour hackathon for university students.', 'Competition', 'Innovation Hub', '2026-08-05', '08:00:00', '08:00:00', 'codefest.jpg', 'https://forms.gle/ghi789rst', 'hybrid', 'upcoming', '2026-06-14 05:30:26'),
(8, 9, 'Leadership Summit', 'Seminar on leadership and soft skills.', 'Seminar', 'Conference Hall', '2026-05-30', '14:00:00', '17:30:00', 'leadership.jpg', 'https://forms.gle/jkl012mno', 'physical', 'completed', '2026-06-14 05:30:26'),
(9, 9, 'Cultural Night 2026', 'Annual cultural evening with music and dance.', 'Cultural', 'Open Air Theatre', '2026-06-10', '18:00:00', '22:00:00', 'cultural_night.jpg', 'https://forms.gle/pqr345stu', 'physical', 'completed', '2026-06-14 05:30:26'),
(10, 6, 'Startup Pitch Day', 'Pitch your startup ideas to industry experts.', 'Career', 'Business School', '2026-07-01', '10:00:00', '16:00:00', 'startup_pitch.jpg', 'https://forms.gle/vwx678yza', 'physical', 'upcoming', '2026-06-14 05:30:26'),
(11, 6, 'Networking Night', 'Meet alumni and industry professionals.', 'Career', 'Grand Ballroom', '2026-06-20', '18:30:00', '21:30:00', 'networking.jpg', 'https://forms.gle/bcd901efg', 'physical', 'ongoing', '2026-06-14 05:30:26'),
(12, 8, 'Music Fiesta', 'Live performances by university bands.', 'Music', 'Amphitheatre', '2026-06-28', '17:00:00', '23:00:00', 'music_fiesta.jpg', 'https://forms.gle/hij234klm', 'physical', 'upcoming', '2026-06-14 05:30:26'),
(13, 8, 'Sports Meet 2026', 'Inter-society sports tournament.', 'Sports', 'University Ground', '2026-07-20', '08:00:00', '18:00:00', 'sports_meet.jpg', 'https://forms.gle/nop567qrs', 'physical', 'upcoming', '2026-06-14 05:30:26');

-- --------------------------------------------------------

--
-- Table structure for table `event_likes`
--

CREATE TABLE `event_likes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `saved_events`
--

CREATE TABLE `saved_events` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `societies`
--

CREATE TABLE `societies` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `society_name` varchar(255) NOT NULL,
  `faculty` varchar(100) DEFAULT NULL,
  `founded_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `vision` text DEFAULT NULL,
  `mission` text DEFAULT NULL,
  `email_1` varchar(255) NOT NULL,
  `email_2` varchar(255) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `website_url` varchar(255) DEFAULT NULL,
  `facebook_url` varchar(255) DEFAULT NULL,
  `instagram_link` varchar(255) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `cover_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','verified') DEFAULT 'pending',
  `verify_token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `societies`
--

INSERT INTO `societies` (`id`, `admin_id`, `society_name`, `faculty`, `founded_date`, `description`, `address`, `vision`, `mission`, `email_1`, `email_2`, `contact_number`, `website_url`, `facebook_url`, `instagram_link`, `logo_path`, `cover_path`, `status`, `verify_token`, `created_at`) VALUES
(3, 3, 'dilshni_society', NULL, NULL, 'ai balanne', NULL, NULL, NULL, 'ramanay-ps22158@stu.kln.ac.lk', 'weerasi-ps22142@stu.kln.ac.lk', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '709d204f08cc17c4c6662fd93c1841bd01ea1e4faf045f04a1b8ad80ef208f08', '2026-06-01 17:41:01'),
(4, 8, 'mokda', NULL, NULL, 'wterdyftjghkjnk', NULL, NULL, NULL, 'weerasi-ps221455@stu.kln.ac.lk', 'weerasi-ps22188@stu.kln.ac.lk', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '208961f51c37c7eb34dd095d6be8d317cdcb89863628ecf73b1f2c21a8449129', '2026-06-04 14:31:58'),
(5, 3, 'PSSF', NULL, NULL, 'wdsafgbgnvb', NULL, NULL, NULL, 'weerasi-ps22142@stu.kln.ac.lk', 'dushman-se22019@stu.kln.ac.lk', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '0d999912182e76200002aafacc02ad1d4c9cf4c2cc98ff77432aa4eddf49ef0c', '2026-06-04 14:35:55'),
(6, 3, 'new society', NULL, NULL, 'wasdgfhgvmbn,m', NULL, NULL, NULL, 'weerasi-ps22142@stu.kln.ac.lk', 'kumaras-ps22200@stu.kln.ac.lk', NULL, NULL, NULL, NULL, NULL, NULL, 'verified', NULL, '2026-06-04 15:03:17'),
(7, 10, 'eghjklwesrdtfghjbnkm', NULL, NULL, 'dsfgchvbjnkmlfghvbjjnm', NULL, NULL, NULL, 'weerasi-ps22142@stu.kln.ac.lk', 'bldsbal-ps22176@stu.kln.ac.lk', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '354fd5f9dbbd713b617b98d1de9be10740e1315e3ed1ebf1e4e60916acf3767a', '2026-06-05 07:34:18'),
(8, 10, 'eghjklwesrdtfghjbnkm', NULL, NULL, 'dsfgchvbjnkmlfghvbjjnm', NULL, NULL, NULL, 'weerasi-ps22142@stu.kln.ac.lk', 'bldsbal-ps22176@stu.kln.ac.lk', NULL, NULL, NULL, NULL, NULL, NULL, 'verified', NULL, '2026-06-05 07:35:14'),
(9, 3, 'PSSF Society', NULL, NULL, 'Premier tech society', NULL, NULL, NULL, 'pssf@example.com', '', NULL, NULL, NULL, NULL, 'pssf_logo.png', NULL, 'verified', NULL, '2026-06-14 05:19:12');

-- --------------------------------------------------------

--
-- Table structure for table `society_followers`
--

CREATE TABLE `society_followers` (
  `id` int(11) NOT NULL,
  `society_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `university` varchar(100) NOT NULL,
  `school` varchar(150) DEFAULT NULL,
  `qualifications` varchar(255) DEFAULT NULL,
  `category` varchar(50) NOT NULL,
  `student_number` varchar(50) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `avatar_url` varchar(255) DEFAULT 'assets/images/default_avatar.png',
  `cover_url` varchar(255) DEFAULT 'assets/images/default_cover.png',
  `tagline` varchar(150) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `otp` varchar(10) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `university`, `school`, `qualifications`, `category`, `student_number`, `email`, `password`, `avatar_url`, `cover_url`, `tagline`, `location`, `otp`, `is_verified`, `created_at`) VALUES
(3, 'dilshnai balasuriya', 'Moratuwa', NULL, NULL, 'student', 'PS/2022/181', 'bldsbal-ps22176@stu.kln.ac.lk', '$2y$10$vRaHPLbgcksFXi6yRDLznOY/1yNlMjx5gNbc0z6K3XfplgfWhTtma', 'assets/images/uploads/1781355808_avatar_3.jpeg', 'assets/images/uploads/1781355880_cover_3.jpeg', NULL, NULL, NULL, 1, '2026-06-01 17:11:59'),
(6, 'kavindu', 'Sabaragamuwa', NULL, NULL, 'lecturer', NULL, 'dushman-se22019@stu.kln.ac.lk', '$2y$10$7n9PoZTSbunEDfQAVmB5KuaWJXZJ59RUbGG9JoZuDjF9360mGSGzO', 'assets/images/default_avatar.png', 'assets/images/default_cover.png', NULL, NULL, '654500', 0, '2026-06-02 14:45:35'),
(8, 'pasindu vidushan', 'Jaffna', NULL, NULL, 'student', 'ps/2022/142', 'weerasi-ps22142@stu.kln.ac.lk', '$2y$10$ELNmuRhPGUpYhP/4m3jb0.NEHbWOHsMkFR.nC13XINZeL2MouiIeG', 'assets/images/uploads/1780734292_avatar_8.png', 'assets/images/uploads/1780734299_cover_8.jpeg', NULL, NULL, NULL, 1, '2026-06-04 14:16:11'),
(9, 'werdfghj', 'Rajarata', NULL, NULL, 'student', 'PS/2022/142', 'bldsbal-ps22456@stu.kln.ac.lk', '$2y$10$mxLHi2UnW0rKtfeka/fgnuA3A7v28e0vXsAHJK9h2S89JQ439dtJe', 'assets/images/default_avatar.png', 'assets/images/default_cover.png', NULL, NULL, '910986', 0, '2026-06-04 19:18:36'),
(10, 'saduni ramanayak', 'Peradeniya', 'efvsbnsth', 'BSC computer science', 'student', 'PS/2022/158', 'ramanay-ps22158@stu.kln.ac.lk', '$2y$10$gEDMCGWUsThsUkVn2STRQ.hLUbro1iLSKOXIGD8LBMf1/5zrog87S', 'assets/images/uploads/1780695690_avatar_10.jpeg', 'assets/images/uploads/1780700209_cover_10.jpeg', 'wesrtdycfvgbhujnkimletycfvgbhjukn', 'gampaha', NULL, 1, '2026-06-05 07:30:34'),
(14, 'dannene', 'Kelaniya', NULL, NULL, 'lecturer', NULL, 'kumaras-ps22181@stu.kln.ac.lk', '$2y$10$mDVvw1oowmBLAFmJotxkKukIRahG8se6PCQ/qcX/pHzQhoLcFr7wC', 'assets/images/default_avatar.png', 'assets/images/default_cover.png', NULL, NULL, NULL, 1, '2026-06-05 08:46:34');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `society_id` (`society_id`);

--
-- Indexes for table `event_likes`
--
ALTER TABLE `event_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_event_unique` (`user_id`,`event_id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `saved_events`
--
ALTER TABLE `saved_events`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_event_unique` (`user_id`,`event_id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `societies`
--
ALTER TABLE `societies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `society_followers`
--
ALTER TABLE `society_followers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `event_likes`
--
ALTER TABLE `event_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `saved_events`
--
ALTER TABLE `saved_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `societies`
--
ALTER TABLE `societies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `society_followers`
--
ALTER TABLE `society_followers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`society_id`) REFERENCES `societies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `event_likes`
--
ALTER TABLE `event_likes`
  ADD CONSTRAINT `event_likes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_likes_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `saved_events`
--
ALTER TABLE `saved_events`
  ADD CONSTRAINT `saved_events_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `saved_events_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `societies`
--
ALTER TABLE `societies`
  ADD CONSTRAINT `societies_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
