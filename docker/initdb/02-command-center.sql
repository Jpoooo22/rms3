-- Command Center: projects layer (runs after 01-rms.sql on a fresh Docker DB).
-- For an existing database, run install-command-center.php instead (idempotent).
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `projects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `subtitle` varchar(255) NOT NULL DEFAULT '',
  `tracks` text,
  `description` text,
  `image` varchar(500) NOT NULL DEFAULT '',
  `status` varchar(50) NOT NULL DEFAULT 'On Track',
  `progress` int NOT NULL DEFAULT '0',
  `next_milestone` varchar(255) NOT NULL DEFAULT '',
  `next_milestone_date` date DEFAULT NULL,
  `lead_user_id` int DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_archived` tinyint NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_project_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `project_members` (
  `id` int NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `user_id` int NOT NULL,
  `role` varchar(100) NOT NULL DEFAULT 'Member',
  `added_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_project_member` (`project_id`,`user_id`),
  KEY `idx_pm_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

ALTER TABLE `task` ADD COLUMN `project_id` int NOT NULL DEFAULT '0';
ALTER TABLE `task` ADD INDEX `idx_task_project` (`project_id`);

INSERT IGNORE INTO `projects`
  (`code`,`title`,`subtitle`,`tracks`,`status`,`progress`,`sort_order`) VALUES
('DAGAT2','PROJECT DAGAT II','Maritime Domain Awareness','GC revisions, acoustic system, DND/DOST/PN coordination, next milestones','On Track',0,1),
('NEXUSPRO','NEXUS PRO','LORA-IOT Solutions','Beta rollout, universities, industry partners, CoLab, commercialization','On Track',0,2),
('ALTAIHUB','ALTA-iHUB / KIST','Innovation & Commercialization','Incubation, PEZA requirements, locators, commercialization','In Progress',0,3),
('SPACEPROG','SPACE PROGRAM','PERPSAT & Beyond','PERPSAT / satellite activities and partnerships','On Track',0,4),
('COE','COLLEGE OF ENGINEERING','Education & Research','Accreditation, faculty, students, laboratories and academic deliverables','On Track',0,5),
('DNDAFP','DND / AFP Collaboration','Defense & Security','Defense R&D, meetings, proposals and partnership actions','Engaged',0,6);
