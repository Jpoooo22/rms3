-- RMS database dump (auto-generated for Docker)
SET FOREIGN_KEY_CHECKS=0;
SET NAMES utf8mb4;

DROP TABLE IF EXISTS `announcements`;
CREATE TABLE `announcements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `posted_by` varchar(100) DEFAULT '',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `announcements` (`id`,`title`,`body`,`posted_by`,`created_at`) VALUES
('1','Welcome to the CoE Records Management System','Use the megaphone button to post announcements for the College of Engineering.','1','2026-07-08 18:19:08'),
('2','Faculty Meeting','CoE faculty meeting on Friday 2PM at the Dean\'s office.','1','2026-07-08 18:21:27');

DROP TABLE IF EXISTS `assigned_to`;
CREATE TABLE `assigned_to` (
  `id_assigned_to` int NOT NULL AUTO_INCREMENT,
  `task_id` varchar(100) NOT NULL,
  `user_id` varchar(100) NOT NULL,
  `assigning_date` varchar(255) NOT NULL,
  `assigned_By` varchar(255) NOT NULL,
  `user_class` varchar(255) NOT NULL,
  `assigned_by_id` varchar(100) NOT NULL,
  `approver_id` varchar(100) NOT NULL,
  PRIMARY KEY (`id_assigned_to`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

DROP TABLE IF EXISTS `darreport`;
CREATE TABLE `darreport` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `start` varchar(255) NOT NULL,
  `end` varchar(255) NOT NULL,
  `sig_file` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

DROP TABLE IF EXISTS `events`;
CREATE TABLE `events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `start_date` varchar(255) NOT NULL,
  `end_date` varchar(255) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

DROP TABLE IF EXISTS `history_log`;
CREATE TABLE `history_log` (
  `history_no` int NOT NULL,
  `task_id` varchar(100) NOT NULL,
  `assigner_id` varchar(100) NOT NULL,
  `assignee_id` varchar(255) NOT NULL,
  `return_date` varchar(255) NOT NULL,
  `approve_date` varchar(255) NOT NULL,
  `n_phase` varchar(100) NOT NULL,
  `approver_id_h` varchar(100) NOT NULL,
  `sign_date` varchar(255) NOT NULL,
  `re_assign_date` varchar(255) NOT NULL,
  `percent` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `history_log` (`history_no`,`task_id`,`assigner_id`,`assignee_id`,`return_date`,`approve_date`,`n_phase`,`approver_id_h`,`sign_date`,`re_assign_date`,`percent`) VALUES
('1','82','1','61','','2022-12-09 10:24:09','1','','','','40'),
('2','82','1','61','','','2','','','',''),
('1','83','1','61','','2023-03-03 14:16:06','1','','','','32'),
('2','83','1','61','','','2','','','','');

DROP TABLE IF EXISTS `log_activities`;
CREATE TABLE `log_activities` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(255) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `ip` varchar(255) NOT NULL,
  `action_name` varchar(255) NOT NULL,
  `action_page` varchar(255) NOT NULL,
  `action_date` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=919 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `log_activities` (`id`,`user_id`,`user_name`,`ip`,`action_name`,`action_page`,`action_date`) VALUES
('906','1','Administrator ','::1','Successfully logged in.','Login page','2026-07-08 17:15:16'),
('907','1','Administrator ','::1','Successfully logged in.','Login page','2026-07-08 17:15:44'),
('908','1','Administrator ','::1','Successfully logged in.','Login page','2026-07-08 17:20:21'),
('909','1','Administrator ','::1','Successfully logged in.','Login page','2026-07-08 17:31:59'),
('910','1','Administrator ','::1','Successfully logged in.','Login page','2026-07-08 17:32:45'),
('911','1','Administrator ','::1','Successfully logged in.','Login page','2026-07-08 17:39:14'),
('912','1','Administrator ','::1','Successfully logged in.','Login page','2026-07-08 17:43:33'),
('913','1','Administrator ','::1','Successfully logged in.','Login page','2026-07-08 18:12:25'),
('914','1','Administrator ','::1','Successfully logged in.','Login page','2026-07-08 18:21:26'),
('915','1','Administrator ','::1','Successfully logged in.','Login page','2026-07-08 18:22:04'),
('916','1','Administrator ','::1','Successfully logged in.','Login page','2026-07-08 18:22:19'),
('917','1','Administrator ','::1','Successfully logged in.','Login page','2026-07-08 18:36:10'),
('918','1','Administrator ','::1','Successfully logged in.','Login page','2026-07-11 17:55:17');

DROP TABLE IF EXISTS `messages_task`;
CREATE TABLE `messages_task` (
  `message_id` int NOT NULL AUTO_INCREMENT,
  `message` varchar(255) NOT NULL,
  `task_id` varchar(255) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `message_date` varchar(255) NOT NULL,
  `phase_id` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`message_id`)
) ENGINE=InnoDB AUTO_INCREMENT=221 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `notification_id` int NOT NULL AUTO_INCREMENT,
  `notifi_title` varchar(255) NOT NULL,
  `notifi_userid` varchar(255) NOT NULL,
  `notifi_type` varchar(255) NOT NULL,
  `notifi_name` varchar(255) NOT NULL,
  `notifi_status` varchar(255) NOT NULL DEFAULT 'unread',
  `notifi_date` varchar(255) NOT NULL,
  `notifi_fromid` int NOT NULL,
  `task_id` int NOT NULL,
  `phase_id` int NOT NULL,
  `phase_name` varchar(100) NOT NULL,
  PRIMARY KEY (`notification_id`)
) ENGINE=InnoDB AUTO_INCREMENT=257 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

DROP TABLE IF EXISTS `schedule_list`;
CREATE TABLE `schedule_list` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` text NOT NULL,
  `description` text NOT NULL,
  `user_id` int NOT NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime DEFAULT NULL,
  `file_title` varchar(255) NOT NULL,
  `file_des` varchar(255) NOT NULL,
  `file` varchar(255) NOT NULL,
  `file_date` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

DROP TABLE IF EXISTS `task`;
CREATE TABLE `task` (
  `id_task` int NOT NULL AUTO_INCREMENT,
  `task_name` varchar(255) NOT NULL,
  `classification` varchar(255) NOT NULL,
  `task_start_date` varchar(255) NOT NULL,
  `task_end_date` varchar(255) NOT NULL,
  `contributor` varchar(255) NOT NULL,
  `ref_file` varchar(255) NOT NULL,
  `added_date_time` varchar(255) NOT NULL,
  `added_By` varchar(255) NOT NULL,
  `task_status` varchar(255) NOT NULL DEFAULT 'open',
  `closed_on` varchar(255) NOT NULL,
  `task_phase_n` int NOT NULL,
  `co_id` varchar(100) NOT NULL,
  `co_name` varchar(255) NOT NULL,
  `percent` varchar(255) NOT NULL,
  PRIMARY KEY (`id_task`)
) ENGINE=InnoDB AUTO_INCREMENT=90 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

DROP TABLE IF EXISTS `task_phase`;
CREATE TABLE `task_phase` (
  `id_p` int NOT NULL AUTO_INCREMENT,
  `p_task_id` varchar(100) NOT NULL,
  `phase_name` varchar(255) NOT NULL,
  `phase_status` varchar(255) NOT NULL,
  `phase_date` varchar(100) NOT NULL,
  `phase_edate` varchar(100) NOT NULL,
  PRIMARY KEY (`id_p`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

DROP TABLE IF EXISTS `task_reports`;
CREATE TABLE `task_reports` (
  `report_id` int NOT NULL AUTO_INCREMENT,
  `report_title` varchar(255) NOT NULL,
  `report_des` text NOT NULL,
  `report_file` varchar(255) NOT NULL,
  `task_id` varchar(100) NOT NULL,
  `reports_userid` varchar(100) NOT NULL,
  `report_date` varchar(255) NOT NULL,
  `report_fullname` varchar(255) NOT NULL,
  `report_pid` varchar(100) NOT NULL,
  PRIMARY KEY (`report_id`)
) ENGINE=InnoDB AUTO_INCREMENT=93 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

DROP TABLE IF EXISTS `todolist`;
CREATE TABLE `todolist` (
  `idlist` int NOT NULL AUTO_INCREMENT,
  `userid` varchar(255) NOT NULL,
  `description` varchar(1000) NOT NULL,
  `startDate` varchar(255) NOT NULL,
  `endDate` varchar(255) NOT NULL,
  PRIMARY KEY (`idlist`)
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `SerialID` varchar(255) NOT NULL,
  `department` varchar(255) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `firstname` varchar(255) NOT NULL,
  `middlename` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL DEFAULT 'newuser',
  `password` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phoneNo` varchar(255) NOT NULL,
  `profilepic` varchar(1000) NOT NULL,
  `approval` varchar(255) NOT NULL DEFAULT 'unverified',
  `usertype` varchar(255) NOT NULL,
  `addedBy` varchar(255) NOT NULL,
  `firstlogin` int NOT NULL DEFAULT '0',
  `code` int NOT NULL,
  `user_status` varchar(255) NOT NULL DEFAULT 'offline',
  `registerDate` varchar(255) NOT NULL,
  `position` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=80 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `users` (`id`,`SerialID`,`department`,`lastname`,`firstname`,`middlename`,`username`,`password`,`email`,`phoneNo`,`profilepic`,`approval`,`usertype`,`addedBy`,`firstlogin`,`code`,`user_status`,`registerDate`,`position`) VALUES
('1','01','Office of the Dean','','Administrator','','admin','$2y$10$DZehUCBroIN8AnCvMOyJXubLIny0pLGVQgHwR8qlq9tjK33wSZX8y','guillergalera727@gmail.com','','./img/profile_imgs/user_profile_imgs/defaultprofilepic.png','verified','Admin','','1','0','online','2021-08-16 04:20:34','Dean');

DROP TABLE IF EXISTS `validators`;
CREATE TABLE `validators` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` varchar(100) NOT NULL,
  `validator_id` int NOT NULL,
  `task_id` int NOT NULL,
  `action` varchar(255) DEFAULT NULL,
  `sender_id` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

SET FOREIGN_KEY_CHECKS=1;
