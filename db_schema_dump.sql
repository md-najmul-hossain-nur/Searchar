CREATE TABLE `admin_chat_messages` (
  `message_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `participant_role` varchar(20) NOT NULL,
  `participant_id` int(10) unsigned NOT NULL,
  `sender_role` varchar(20) NOT NULL,
  `sender_id` int(10) unsigned NOT NULL DEFAULT 0,
  `message_text` text NOT NULL,
  `is_read_by_admin` tinyint(1) NOT NULL DEFAULT 0,
  `is_read_by_participant` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`message_id`),
  KEY `idx_admin_chat_participant` (`participant_role`,`participant_id`,`message_id`),
  KEY `idx_admin_chat_created` (`created_at`),
  KEY `idx_admin_chat_admin_unread` (`is_read_by_admin`,`participant_role`,`participant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
INSERT INTO `admin_chat_messages` VALUES (2,'volunteer',1,'volunteer',1,'her',1,1,'2026-06-05 18:25:16'),(3,'volunteer',1,'volunteer',1,'hellop',1,1,'2026-06-05 18:25:20'),(4,'volunteer',1,'volunteer',1,'dfdf',1,1,'2026-06-05 18:42:00'),(5,'volunteer',1,'volunteer',1,'dfdsfsdf',1,1,'2026-06-05 18:42:14'),(6,'volunteer',1,'volunteer',1,'kkk',1,1,'2026-06-05 18:47:54'),(7,'police',1,'police',1,'hii',1,1,'2026-06-05 19:10:04'),(8,'police',1,'police',1,'hello',1,1,'2026-06-05 19:10:15'),(9,'police',1,'police',1,'jhdfhdjf',1,1,'2026-06-05 19:20:03'),(10,'police',1,'police',1,'hii',1,1,'2026-06-05 19:20:27'),(11,'police',1,'police',1,'hfhsdjf',1,1,'2026-06-05 19:20:32'),(12,'police',1,'police',1,'hdfdhf',1,1,'2026-06-05 19:20:35'),(13,'police',1,'police',1,'hdfhd',1,1,'2026-06-05 19:20:37'),(14,'police',1,'police',1,'dffd',1,1,'2026-06-05 19:21:50'),(15,'police',1,'police',1,'ddfd',1,1,'2026-06-05 19:23:45'),(16,'police',1,'police',1,'ddd',1,1,'2026-06-05 19:23:50'),(17,'police',1,'police',1,'ddd',1,1,'2026-06-05 19:23:54'),(18,'police',1,'police',1,'ddfsdf',1,1,'2026-06-05 19:24:06'),(19,'police',1,'police',1,'dfdsdfsd',0,1,'2026-06-05 19:24:13'),(20,'police',1,'police',1,'sdfdsfdsf',0,1,'2026-06-05 19:24:16'),(21,'police',1,'police',1,'sdfsd',0,1,'2026-06-05 19:24:17'),(22,'police',1,'police',1,'sdf',0,1,'2026-06-05 19:24:18'),(23,'police',1,'police',1,'sdf',0,1,'2026-06-05 19:24:18'),(24,'police',1,'police',1,'df',0,1,'2026-06-05 19:24:18');
CREATE TABLE `admin_logs` (
  `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int(10) unsigned NOT NULL,
  `action_type` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `idx_admin_logs_admin_id` (`admin_id`),
  CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`admin_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `admin_logs` VALUES (1,1,'Added Sub-Admin','Added sub-admin: najmulhossainnur510@gmail.com','2026-06-06 20:24:54'),(2,1,'Added Sub-Admin','Added sub-admin: najmulhosainnur544@gmail.com','2026-06-06 20:46:37');
CREATE TABLE `admins` (
  `admin_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(255) NOT NULL,
  `mobile` varchar(30) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('main_admin','sub_admin') NOT NULL DEFAULT 'sub_admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `uq_admins_email` (`email`),
  UNIQUE KEY `uq_admins_mobile` (`mobile`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `admins` VALUES (1,'Main Admin','mnajmulhossainnur@gmail.com','01743094595','$2y$10$Hi3rBLViP9hj/YHfsN2EVe/kQppcooz/oqM1OZHAh/s9tszMx9Gqa','main_admin','2026-06-06 20:04:22');
CREATE TABLE `auth_users` (
  `auth_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `provider` enum('local','google','facebook') NOT NULL DEFAULT 'local',
  `role` enum('user','policeman','volunteer','camera_contributor') NOT NULL DEFAULT 'user',
  `provider_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`auth_id`),
  UNIQUE KEY `uq_auth_email` (`email`),
  KEY `idx_auth_provider_id` (`provider_id`),
  KEY `idx_auth_provider` (`provider`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `camera_cctv_feeds` (
  `feed_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `camera_id` int(10) unsigned NOT NULL,
  `feed_label` varchar(150) NOT NULL,
  `feed_type` varchar(20) NOT NULL DEFAULT 'live',
  `is_indoor` tinyint(1) NOT NULL DEFAULT 1,
  `stream_scope` varchar(20) NOT NULL DEFAULT 'private',
  `live_url` varchar(1200) DEFAULT NULL,
  `video_path` varchar(600) DEFAULT NULL,
  `camera_location` varchar(255) DEFAULT NULL,
  `streaming_hours` varchar(80) NOT NULL DEFAULT 'continuous',
  `allow_ai_detection` tinyint(1) NOT NULL DEFAULT 0,
  `allow_public_viewing` tinyint(1) NOT NULL DEFAULT 0,
  `ai_alerts_to_volunteers` tinyint(1) NOT NULL DEFAULT 0,
  `accumulated_seconds` bigint(20) unsigned NOT NULL DEFAULT 0,
  `payout_count` int(10) unsigned NOT NULL DEFAULT 0,
  `active_started_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`feed_id`),
  KEY `idx_camera_active` (`camera_id`,`is_active`),
  KEY `idx_camera_created` (`camera_id`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `camera_cctv_feeds` VALUES (10,2,'Camera 1','webcam',1,'private',NULL,'uploads/cctv_snapshots/feed_10_latest.jpg','Dhaka purbo vatara jame mosjid, Saind Nagar Auto stand, vaata thana, notun bazar,Dhaka Bangladesh','continuous',0,0,0,1396,23,'2026-06-11 00:39:20',1,0,'2026-06-10 20:40:12','2026-06-11 09:46:08'),(11,2,'Camera 2','webcam',1,'private',NULL,'uploads/cctv_snapshots/feed_11_latest.jpg','Dhaka purbo vatara jame mosjid, Saind Nagar Auto stand, vaata thana, notun bazar,Dhaka Bangladesh','continuous',0,0,0,0,5,'2026-06-11 09:24:37',1,0,'2026-06-11 07:24:37','2026-06-11 09:54:38'),(12,2,'Camera 3','webcam',1,'private',NULL,'uploads/cctv_snapshots/feed_12_latest.jpg','Dhaka purbo vatara jame mosjid, Saind Nagar Auto stand, vaata thana, notun bazar,Dhaka Bangladesh','continuous',0,0,0,0,5,'2026-06-11 09:24:43',1,0,'2026-06-11 07:24:43','2026-06-11 09:55:08'),(13,2,'Camera 4','webcam',1,'private',NULL,'uploads/cctv_snapshots/feed_13_latest.jpg','Dhaka purbo vatara jame mosjid, Saind Nagar Auto stand, vaata thana, notun bazar,Dhaka Bangladesh','continuous',0,0,0,0,5,'2026-06-11 09:24:52',1,0,'2026-06-11 07:24:52','2026-06-11 09:55:08');
CREATE TABLE `camera_contributors` (
  `camera_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(255) NOT NULL,
  `mobile` varchar(30) NOT NULL,
  `nid_number` varchar(100) NOT NULL,
  `nid_photo` varchar(255) NOT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `cover_photo` varchar(255) DEFAULT NULL,
  `bio` varchar(500) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `street` varchar(255) DEFAULT NULL,
  `city` varchar(120) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(120) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`camera_id`),
  UNIQUE KEY `uq_camera_email` (`email`),
  UNIQUE KEY `uq_camera_mobile` (`mobile`),
  UNIQUE KEY `uq_camera_nid` (`nid_number`),
  KEY `idx_camera_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `camera_contributors` VALUES (1,'Najmulhosian nur','nirjhorislam07@gmail.com','','sdfhgasdfgjk;asdgfjksdf','',NULL,NULL,'sdfasdfasdf',NULL,'Male',NULL,NULL,NULL,NULL,NULL,NULL,'12345678','2026-06-07 11:09:53'),(2,'Najmul Hossain Nur','najmulhosainnur515@gmail.com','01743094510','uryeuyroijhdhewihrfkdf','nid__6a255b19ab1803.99346832.jpg','profile__6a255b19ac0199.41421794.jpeg','cover__6a255b19ac4687.04865272.jpg',NULL,'2026-06-07','male','purbo vatara jame mosjid, Saind Nagar Auto stand, vaata thana, notun bazar,Dhaka','Dhaka','1212','Bangladesh',23.7975633,90.4344553,'$2y$10$cWlgYoTENxI.3wVoQ5tGb.27SHabYwAJpqgIvlVQ3rjJZ7bevaqim','2026-06-07 11:50:49');
CREATE TABLE `chat_broadcasts` (
  `broadcast_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sender_role` varchar(80) NOT NULL DEFAULT 'admin',
  `sender_id` int(10) unsigned NOT NULL DEFAULT 0,
  `target_role` varchar(80) NOT NULL,
  `message` text NOT NULL,
  `delivered_count` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`broadcast_id`),
  KEY `idx_chat_broadcasts_target` (`target_role`),
  KEY `idx_chat_broadcasts_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_role` varchar(20) NOT NULL,
  `sender_id` int(11) NOT NULL DEFAULT 0,
  `receiver_role` varchar(20) NOT NULL,
  `receiver_id` int(11) NOT NULL DEFAULT 0,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `chat_messages` VALUES (1,'police',1,'admin',0,'hii',0,'2026-06-05 14:45:44');
CREATE TABLE `chatbot_admin_replies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `session_token` varchar(128) NOT NULL,
  `reply_text` text NOT NULL,
  `is_delivered` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `delivered_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_chatbot_admin_replies_session` (`session_token`),
  KEY `idx_chatbot_admin_replies_delivered` (`is_delivered`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `chatbot_admin_replies` VALUES (1,'1780591962751-v1w9hywg','Thanks for your message. Our team is checking now.',1,'2026-06-05 20:58:33','2026-06-11 16:36:34'),(2,'1780591962751-v1w9hywg','Thanks for your message. Our team is checking now.',1,'2026-06-05 20:58:39','2026-06-11 16:36:34'),(3,'1780591962751-v1w9hywg','dsfdsfdsf',1,'2026-06-05 21:50:37','2026-06-11 16:36:34'),(4,'1780591962751-v1w9hywg','Thanks for your message. Our team is checking now.',1,'2026-06-11 05:23:16','2026-06-11 16:36:34');
CREATE TABLE `chatbot_comment_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `comment_text` varchar(300) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_chatbot_comment_templates_text` (`comment_text`),
  KEY `idx_chatbot_comment_templates_active` (`is_active`),
  KEY `idx_chatbot_comment_templates_sort` (`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `chatbot_comment_templates` VALUES (1,'Thanks for your message. Our team is checking now.',1,1,'2026-06-04 23:25:53'),(2,'Your report has been received and forwarded to the support team.',1,2,'2026-06-04 23:25:53'),(3,'Please share location and time details for faster action.',1,3,'2026-06-04 23:25:53'),(4,'We could not verify this yet. Please provide a clear photo or reference.',1,4,'2026-06-04 23:25:53'),(5,'This issue has been noted and marked for follow-up.',1,5,'2026-06-04 23:25:53'),(6,'In an emergency, please call 999 immediately.',1,6,'2026-06-04 23:25:53'),(7,'dsfdsfdsf',1,7,'2026-06-05 21:50:33');
CREATE TABLE `chatbot_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `question` text NOT NULL,
  `reply` text NOT NULL,
  `source_page` varchar(64) NOT NULL DEFAULT 'index',
  `session_token` varchar(128) DEFAULT NULL,
  `ip_address` varchar(64) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_chatbot_logs_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `chatbot_logs` VALUES (1,'hii','I can help with donation, volunteer joining, login, and news navigation. Ask me anything about these.','index','1780591962751-v1w9hywg','::1','2026-06-05 20:58:21'),(2,'fdsfdsf','I can help with donation, volunteer joining, login, and news navigation. Ask me anything about these.','index','1780591962751-v1w9hywg','::1','2026-06-05 21:50:13'),(3,'How do I join as volunteer?','To join as volunteer, click GET INVOLVED NOW and complete registration.','index','1780591962751-v1w9hywg','::1','2026-06-05 22:03:35'),(4,'How to report a clue?','Please use the relevant logged-in dashboard to submit verified clues safely.','index','1780591962751-v1w9hywg','::1','2026-06-05 22:03:37'),(5,'How can I donate?','To donate, click MAKE DONATION or Contribute Now on this page.','index','1780591962751-v1w9hywg','::1','2026-06-08 17:49:16'),(6,'How do I join as volunteer?','To join as volunteer, click GET INVOLVED NOW and complete registration.','index','1780591962751-v1w9hywg','::1','2026-06-08 17:49:17'),(7,'How to report a clue?','Please use the relevant logged-in dashboard to submit verified clues safely.','index','1780591962751-v1w9hywg','::1','2026-06-08 17:49:18'),(8,'Where is latest news?','Check the LATEST NEWS section below. You can click Read More for full details.','index','1780591962751-v1w9hywg','::1','2026-06-08 17:49:19'),(9,'heiiii','I can help with donation, volunteer joining, login, and news navigation. Ask me anything about these.','index','1780591962751-v1w9hywg','::1','2026-06-08 17:49:25'),(10,'How can I donate?','To donate, click MAKE DONATION or Contribute Now on this page.','index','1780591962751-v1w9hywg','::1','2026-06-10 22:14:04'),(11,'Where is latest news?','Check the LATEST NEWS section below. You can click Read More for full details.','index','1780591962751-v1w9hywg','::1','2026-06-10 22:14:05'),(12,'How do I join as volunteer?','To join as volunteer, click GET INVOLVED NOW and complete registration.','index','1780591962751-v1w9hywg','::1','2026-06-10 22:14:08'),(13,'How to report a clue?','Please use the relevant logged-in dashboard to submit verified clues safely.','index','1780591962751-v1w9hywg','::1','2026-06-10 22:14:09'),(14,'How can I donate?','To donate, click MAKE DONATION or Contribute Now on this page.','index','1780591962751-v1w9hywg','::1','2026-06-10 22:14:10'),(15,'hhi','I can help with donation, volunteer joining, login, and news navigation. Ask me anything about these.','index','1780591962751-v1w9hywg','::1','2026-06-10 22:14:15'),(16,'hle','I can help with donation, volunteer joining, login, and news navigation. Ask me anything about these.','index','1780591962751-v1w9hywg','::1','2026-06-11 04:57:11'),(17,'hiii','I can help with donation, volunteer joining, login, and news navigation. Ask me anything about these.','index','1780591962751-v1w9hywg','::1','2026-06-11 04:59:46'),(18,'How do I join as volunteer?','To join as volunteer, click GET INVOLVED NOW and complete registration.','index','1780591962751-v1w9hywg','::1','2026-06-11 05:00:18'),(19,'How do I join as volunteer?','To join as volunteer, click GET INVOLVED NOW and complete registration.','index','1780591962751-v1w9hywg','::1','2026-06-11 05:01:25'),(20,'hii','I can help with donation, volunteer joining, login, and news navigation. Ask me anything about these.','index','1780591962751-v1w9hywg','::1','2026-06-11 05:05:27'),(21,'Where is latest news?','Check the LATEST NEWS section below. You can click Read More for full details.','index','1780591962751-v1w9hywg','::1','2026-06-11 05:06:28'),(22,'hii i','I can help with donation, volunteer joining, login, and news navigation. Ask me anything about these.','index','1780591962751-v1w9hywg','::1','2026-06-11 05:06:32'),(23,'jii','I can help with donation, volunteer joining, login, and news navigation. Ask me anything about these.','index','1780591962751-v1w9hywg','::1','2026-06-11 05:22:38'),(24,'hii','Please wait for the admin\'s reply.','index','1780591962751-v1w9hywg','::1','2026-06-11 05:22:59'),(25,'need helo','Please wait for the admin\'s reply.','index','1780591962751-v1w9hywg','::1','2026-06-11 05:23:37');
CREATE TABLE `comment_reports` (
  `report_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `comment_id` bigint(20) unsigned NOT NULL,
  `post_id` int(10) unsigned NOT NULL,
  `comment_author_role` varchar(50) DEFAULT NULL,
  `comment_author_id` int(10) unsigned DEFAULT NULL,
  `comment_author_name` varchar(255) DEFAULT NULL,
  `comment_text` text DEFAULT NULL,
  `reporter_role` varchar(50) NOT NULL,
  `reporter_id` int(10) unsigned NOT NULL,
  `reporter_name` varchar(255) NOT NULL,
  `report_category` varchar(80) NOT NULL,
  `report_details` text DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `admin_action_note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `actioned_at` datetime DEFAULT NULL,
  PRIMARY KEY (`report_id`),
  KEY `idx_comment_reports_comment` (`comment_id`),
  KEY `idx_comment_reports_post` (`post_id`),
  KEY `idx_comment_reports_status` (`status`),
  KEY `idx_comment_reports_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `conversations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `role` varchar(80) NOT NULL DEFAULT 'user',
  `last_message_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_conversation_user_role` (`user_id`,`role`),
  KEY `idx_user` (`user_id`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `conversations` VALUES (1,1,'user','2026-06-04 18:40:49','2026-06-04 18:06:31','2026-06-04 18:40:49');
CREATE TABLE `crime_reports` (
  `crime_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `case_ref` varchar(80) NOT NULL,
  `source_type` varchar(40) NOT NULL DEFAULT 'missing_person',
  `source_ref_id` bigint(20) unsigned DEFAULT NULL,
  `report_type` varchar(60) NOT NULL DEFAULT 'missing_person',
  `severity` varchar(20) NOT NULL DEFAULT 'high',
  `status` varchar(30) NOT NULL DEFAULT 'new',
  `landmark` varchar(255) DEFAULT NULL,
  `reporter_name` varchar(150) DEFAULT NULL,
  `anonymous` tinyint(1) NOT NULL DEFAULT 0,
  `anon_token` varchar(80) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `media_path` varchar(255) DEFAULT NULL,
  `media_json` text DEFAULT NULL,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `submitted_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `closed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`crime_id`),
  UNIQUE KEY `uq_crime_reports_case_ref` (`case_ref`),
  KEY `idx_crime_reports_status` (`status`),
  KEY `idx_crime_reports_source` (`source_type`,`source_ref_id`),
  KEY `idx_crime_reports_submitted` (`submitted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `crime_reports` VALUES (1,'PT0002','post',2,'mission','medium','new','mission','Najmul Hossain Nur',0,NULL,'sdfdsfsdf','uploads/posts/019194fccbc393e7a8bf.jpg','[\"uploads/posts/019194fccbc393e7a8bf.jpg\"]',23.8103000,90.4125000,'2026-06-05 21:47:40','2026-06-08 15:58:22',NULL,'2026-06-05 15:48:03'),(2,'MP0001','missing_person',1,'missing_person','high','closed','Notun Bazar','sadik',0,NULL,'Escalated from Missing Persons\n[Closed by Admin AI: Match Confirmed (Website Post)]','uploads/missing_person/missing_6a256e1b5a17d5.05378549.jpg','[\"uploads/missing_person/missing_6a256e1b5a17d5.05378549.jpg\"]',23.8103000,90.4125000,'2026-06-07 19:11:55','2026-06-07 19:12:11','2026-06-08 15:15:50','2026-06-07 13:12:11'),(3,'PT0004','post',4,'mission','medium','closed','mission','Rohim Isam',1,NULL,'i think he was lost  so   hep her last seen - Badda   time- 10:50 PM','uploads/posts/a9446f44a920e096247a.jpg','[\"uploads/posts/a9446f44a920e096247a.jpg\"]',23.8103000,90.4125000,'2026-06-07 19:15:12','2026-06-11 04:15:32',NULL,'2026-06-08 09:58:19'),(5,'MP0003','missing_person',3,'missing_person','high','closed','Notun Bazar','sadik',0,NULL,'Escalated from Missing Persons\n[Closed by Admin AI: Match Confirmed (Website Post)]','uploads/missing_person/missing_6a269392ae1e28.47098357.jpg','[\"uploads/missing_person/missing_6a269392ae1e28.47098357.jpg\"]',23.8103000,90.4125000,'2026-06-08 16:04:02','2026-06-08 16:04:17','2026-06-08 16:07:10','2026-06-08 10:04:02'),(7,'MP0004','missing_person',4,'missing_person','high','closed','Notun Bazar','sadik',0,NULL,'Escalated from Missing Persons\n[Closed by Admin AI: Match Confirmed (Website Post)]\n[Closed by Admin AI: Match Confirmed (Website Post)]','uploads/missing_person/missing_6a269e49e8d142.61163907.jpg','[\"uploads/missing_person/missing_6a269e49e8d142.61163907.jpg\"]',23.8103000,90.4125000,'2026-06-08 16:49:45','2026-06-08 16:50:49','2026-06-08 22:38:54','2026-06-08 10:49:45');
CREATE TABLE `donations` (
  `donation_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `donor_name` varchar(150) DEFAULT NULL,
  `donor_email` varchar(255) DEFAULT NULL,
  `sender_mobile` varchar(30) DEFAULT NULL,
  `tx_id` varchar(120) DEFAULT NULL,
  `receiver_number` varchar(40) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `date` datetime NOT NULL DEFAULT current_timestamp(),
  `anonymous` tinyint(1) NOT NULL DEFAULT 0,
  `message` text DEFAULT NULL,
  PRIMARY KEY (`donation_id`),
  KEY `idx_donations_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `fire_alerts` (
  `alert_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `feed_id` bigint(20) unsigned NOT NULL,
  `confidence` varchar(50) NOT NULL,
  `snapshot_url` varchar(600) DEFAULT NULL,
  `status` enum('new','police_dispatched','camera_man_notified','dismissed','fire_station_called') DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`alert_id`),
  KEY `feed_id` (`feed_id`),
  CONSTRAINT `fire_alerts_ibfk_1` FOREIGN KEY (`feed_id`) REFERENCES `camera_cctv_feeds` (`feed_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `fire_alerts` VALUES (1,10,'99% High','../Images/fire_snapshots/fire_10_1781163187.jpg','dismissed','2026-06-11 07:33:07','2026-06-11 07:33:46'),(2,11,'99% High','../Images/fire_snapshots/fire_11_1781163187.jpg','dismissed','2026-06-11 07:33:07','2026-06-11 07:33:50'),(3,12,'99% High','../Images/fire_snapshots/fire_12_1781163187.jpg','dismissed','2026-06-11 07:33:07','2026-06-11 07:33:53'),(4,13,'99% High','../Images/fire_snapshots/fire_13_1781163187.jpg','dismissed','2026-06-11 07:33:07','2026-06-11 07:33:55'),(5,10,'83% High','../Images/fire_snapshots/fire_10_1781163572.jpg','new','2026-06-11 07:39:32',NULL),(6,11,'83% High','../Images/fire_snapshots/fire_11_1781163572.jpg','new','2026-06-11 07:39:32',NULL),(7,12,'83% High','../Images/fire_snapshots/fire_12_1781163572.jpg','new','2026-06-11 07:39:32',NULL),(8,13,'83% High','../Images/fire_snapshots/fire_13_1781163572.jpg','new','2026-06-11 07:39:32',NULL),(9,10,'88% High','../Images/fire_snapshots/fire_10_1781163982.jpg','dismissed','2026-06-11 07:46:22','2026-06-11 07:47:26'),(10,11,'88% High','../Images/fire_snapshots/fire_11_1781163982.jpg','new','2026-06-11 07:46:22',NULL),(11,12,'88% High','../Images/fire_snapshots/fire_12_1781163982.jpg','new','2026-06-11 07:46:22',NULL),(12,13,'88% High','../Images/fire_snapshots/fire_13_1781163982.jpg','new','2026-06-11 07:46:22',NULL),(13,10,'93% High','../Images/fire_snapshots/fire_10_1781164027.jpg','new','2026-06-11 07:47:07',NULL),(14,11,'93% High','../Images/fire_snapshots/fire_11_1781164027.jpg','new','2026-06-11 07:47:07',NULL),(15,12,'93% High','../Images/fire_snapshots/fire_12_1781164027.jpg','new','2026-06-11 07:47:07',NULL),(16,13,'94% High','../Images/fire_snapshots/fire_13_1781164027.jpg','new','2026-06-11 07:47:07',NULL),(17,10,'82% High','../Images/fire_snapshots/fire_10_1781164032.jpg','new','2026-06-11 07:47:12',NULL),(18,11,'82% High','../Images/fire_snapshots/fire_11_1781164032.jpg','new','2026-06-11 07:47:12',NULL),(19,12,'82% High','../Images/fire_snapshots/fire_12_1781164032.jpg','new','2026-06-11 07:47:13',NULL),(20,13,'82% High','../Images/fire_snapshots/fire_13_1781164033.jpg','new','2026-06-11 07:47:13',NULL);
CREATE TABLE `messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sender_role` varchar(40) NOT NULL,
  `sender_id` int(10) unsigned NOT NULL,
  `receiver_role` varchar(40) NOT NULL,
  `receiver_id` int(10) unsigned NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_messages_receiver` (`receiver_role`,`receiver_id`),
  KEY `idx_messages_sender` (`sender_role`,`sender_id`),
  KEY `idx_messages_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
INSERT INTO `messages` VALUES (1,'user',1,'admin',0,'hii',0,'2026-06-05 16:03:47'),(2,'user',1,'admin',0,'hello',0,'2026-06-05 16:04:50'),(3,'user',1,'admin',0,'vcvcvcxv',0,'2026-06-05 16:07:10'),(4,'user',1,'admin',0,'hiii',0,'2026-06-05 16:12:43'),(5,'police',1,'admin',0,'hhh',1,'2026-06-05 19:44:49'),(6,'police',1,'admin',0,'hello',1,'2026-06-05 19:45:02');
CREATE TABLE `missing_person_reports` (
  `report_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reporter_user_id` int(10) unsigned DEFAULT NULL,
  `full_name` varchar(150) NOT NULL,
  `nickname` varchar(150) DEFAULT NULL,
  `gender` varchar(20) NOT NULL,
  `age` int(10) unsigned NOT NULL,
  `physical_description` varchar(500) DEFAULT NULL,
  `photo_filename` varchar(255) NOT NULL,
  `last_seen_date` date NOT NULL,
  `last_seen_location` varchar(255) NOT NULL,
  `last_seen_time` varchar(60) DEFAULT NULL,
  `mental_condition` varchar(120) DEFAULT NULL,
  `medical_notes` varchar(500) DEFAULT NULL,
  `reporter_name` varchar(150) NOT NULL,
  `reporter_mobile` varchar(30) NOT NULL,
  `relationship` varchar(120) DEFAULT NULL,
  `consent` tinyint(1) NOT NULL DEFAULT 1,
  `status` varchar(40) NOT NULL DEFAULT 'open',
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`report_id`),
  KEY `idx_missing_status` (`status`),
  KEY `idx_missing_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `missing_person_reports` VALUES (1,1,'nur','najmul','Male',20,'5.6','missing_6a256e1b5a17d5.05378549.jpg','2026-06-07','Notun Bazar','5.30','Stable','Heart Broken','sadik','01743094595','Friend',1,'closed','2026-06-08 15:15:50','2026-06-07 13:11:55'),(2,1,'Nur','najmul','Male',222,'2.6','missing_6a2690a26c0270.46089004.jpg','2026-06-08','Notun Bazar','5.30','Depression','Heart Broken','sadik','01743094595','Friend',1,'closed','2026-06-08 15:51:47','2026-06-08 09:51:30'),(3,1,'Nur','najmul','Male',10,'5.6','missing_6a269392ae1e28.47098357.jpg','2026-06-08','Notun Bazar','5.30','Depression','Heart Broken','sadik','01743094595','Friend',1,'closed','2026-06-08 16:07:10','2026-06-08 10:04:02'),(4,1,'Nur','najmul','Male',22,'5.6','missing_6a269e49e8d142.61163907.jpg','2026-06-08','Notun Bazar','5.30','Stable','Heart Broken','sadik','01743094595','Friend',1,'closed','2026-06-08 22:38:54','2026-06-08 10:49:45');
CREATE TABLE `password_resets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `code` varchar(10) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_password_resets_email` (`email`),
  KEY `idx_password_resets_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `password_resets` VALUES (3,'najmulhosainnur5@gmail.com','663205','2026-06-06 22:33:59','2026-06-06 20:18:59');
CREATE TABLE `policemen` (
  `police_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(255) NOT NULL,
  `mobile` varchar(30) NOT NULL,
  `nid_number` varchar(100) NOT NULL,
  `nid_photo` varchar(255) NOT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `cover_photo` varchar(255) DEFAULT NULL,
  `bio` varchar(500) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `street` varchar(255) DEFAULT NULL,
  `city` varchar(120) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(120) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `badge_id` varchar(100) NOT NULL,
  `designation` varchar(120) NOT NULL,
  `station` varchar(120) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `chat_status` enum('online','offline') DEFAULT 'offline',
  `chat_last_seen` datetime DEFAULT NULL,
  PRIMARY KEY (`police_id`),
  UNIQUE KEY `uq_policemen_email` (`email`),
  UNIQUE KEY `uq_policemen_mobile` (`mobile`),
  UNIQUE KEY `uq_policemen_nid` (`nid_number`),
  UNIQUE KEY `uq_policemen_badge` (`badge_id`),
  KEY `idx_policemen_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `policemen` VALUES (1,'Najmul Hossain Nur','najmulhosainnur5@gmail.com','01743094595','jhertjhfjkdhsj','nid__6a228b55c61020.03674702.jpg','profile__6a228b55c772c4.73829744.jpg','cover__6a228b55c7efc7.06344165.jpg',NULL,'2026-06-10','male','purbo vatara jame mosjid, Saind Nagar Auto stand, vaata thana, notun bazar,Dhaka','Dhaka','1212','Bangladesh',0.0000000,0.0000000,'$2y$10$ZdRWoReSeCB7C1jwU4WSzuc4Bq5KaraAZ2CFki1y48jdd5ZXwf7Hy','20','fire_service','dddd','2026-06-05 08:39:49','online','2026-06-05 20:49:48');
CREATE TABLE `post_comments` (
  `comment_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int(10) unsigned NOT NULL,
  `parent_comment_id` bigint(20) unsigned DEFAULT NULL,
  `actor_role` varchar(50) NOT NULL,
  `actor_id` int(10) unsigned NOT NULL,
  `is_anonymous` tinyint(1) NOT NULL DEFAULT 0,
  `comment_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`comment_id`),
  KEY `idx_post_comments_post` (`post_id`),
  KEY `idx_post_comments_parent` (`parent_comment_id`),
  KEY `idx_post_comments_actor` (`actor_role`,`actor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `post_likes` (
  `like_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int(10) unsigned NOT NULL,
  `actor_role` varchar(50) NOT NULL,
  `actor_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`like_id`),
  UNIQUE KEY `uq_post_actor` (`post_id`,`actor_role`,`actor_id`),
  KEY `idx_post_likes_post` (`post_id`),
  KEY `idx_post_likes_actor` (`actor_role`,`actor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `post_reports` (
  `report_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int(10) unsigned NOT NULL,
  `post_author_role` varchar(50) DEFAULT NULL,
  `post_author_id` int(10) unsigned DEFAULT NULL,
  `post_author_name` varchar(255) DEFAULT NULL,
  `reporter_role` varchar(50) NOT NULL,
  `reporter_id` int(10) unsigned NOT NULL,
  `reporter_name` varchar(255) NOT NULL,
  `report_category` varchar(80) NOT NULL,
  `report_details` text DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `admin_action_note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `actioned_at` datetime DEFAULT NULL,
  PRIMARY KEY (`report_id`),
  KEY `idx_post_reports_post` (`post_id`),
  KEY `idx_post_reports_reporter` (`reporter_role`,`reporter_id`),
  KEY `idx_post_reports_status` (`status`),
  KEY `idx_post_reports_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `post_reports` VALUES (1,1,'volunteer',1,'Najmul Hossain Nur','police',1,'Najmul Hossain Nur','Spam or misleading',NULL,'resolved',NULL,'2026-06-05 15:00:48','2026-06-05 21:00:59');
CREATE TABLE `posts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `case_id` int(11) NOT NULL DEFAULT 1,
  `author_role` varchar(50) NOT NULL,
  `author_id` int(11) NOT NULL,
  `author_name` varchar(255) DEFAULT NULL,
  `category` varchar(50) DEFAULT 'general',
  `text` text DEFAULT NULL,
  `media_path` varchar(512) DEFAULT NULL,
  `media_json` text DEFAULT NULL,
  `media_type` enum('image','video','file') DEFAULT NULL,
  `share_facebook` tinyint(1) NOT NULL DEFAULT 0,
  `share_anonymous` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(20) DEFAULT 'pending',
  `report_status` varchar(20) DEFAULT 'not_reported',
  `reported_at` datetime DEFAULT NULL,
  `report_closed_at` datetime DEFAULT NULL,
  `is_share` tinyint(1) NOT NULL DEFAULT 0,
  `shared_post_id` int(10) unsigned DEFAULT NULL,
  `shared_payload` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_posts_case` (`case_id`),
  KEY `idx_posts_author` (`author_role`,`author_id`),
  KEY `idx_posts_category` (`category`),
  KEY `idx_posts_status` (`status`),
  KEY `idx_posts_report_status` (`report_status`),
  KEY `idx_posts_report_closed_at` (`report_closed_at`),
  KEY `idx_posts_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `posts` VALUES (1,1,'volunteer',1,'Najmul Hossain Nur','mission','fddsfsdfds',NULL,NULL,NULL,0,0,'approved','reported','2026-06-05 21:00:48',NULL,0,NULL,NULL,'2026-06-05 10:43:39'),(2,1,'police',1,'Najmul Hossain Nur','mission','sdfdsfsdf','uploads/posts/019194fccbc393e7a8bf.jpg','[\"uploads/posts/019194fccbc393e7a8bf.jpg\"]','image',0,0,'approved','reported','2026-06-08 15:58:22','2026-06-08 15:53:05',1,4294967295,'{\"attempted\":true,\"shared\":true,\"post_id\":\"122116330082817051\",\"endpoint\":\"photos\",\"response\":{\"id\":\"122116330082817051\",\"post_id\":\"992332420641048_122116330112817051\"}}','2026-06-05 15:47:40'),(3,1,'admin',0,'Admin','alert','dfdsfsdfdsf','uploads/posts/admin_6a22fc456daa12.06105918.jpg','[\"uploads/posts/admin_6a22fc456daa12.06105918.jpg\"]','image',1,0,'approved','not_reported',NULL,NULL,0,NULL,NULL,'2026-06-05 16:41:41'),(4,1,'user',1,'Rohim Isam','mission','i think he was lost  so   hep her last seen - Badda   time- 10:50 PM','uploads/posts/a9446f44a920e096247a.jpg','[\"uploads/posts/a9446f44a920e096247a.jpg\"]','image',0,1,'approved','closed','2026-06-11 02:04:06','2026-06-11 04:15:32',0,NULL,NULL,'2026-06-07 13:15:12'),(5,1,'admin',0,'Admin','alert','hii  we found  the  criminal','uploads/posts/admin_6a26e5f2dd8a74.02506745.jpg','[\"uploads/posts/admin_6a26e5f2dd8a74.02506745.jpg\"]','image',1,0,'approved','not_reported',NULL,NULL,0,NULL,NULL,'2026-06-08 15:55:30'),(6,1,'admin',0,'Admin','general','hi found him','uploads/posts/admin_6a26e6c04d6a17.40661706.jpg','[\"uploads/posts/admin_6a26e6c04d6a17.40661706.jpg\"]','image',1,0,'approved','not_reported',NULL,NULL,0,NULL,NULL,'2026-06-08 15:58:56'),(7,1,'admin',0,'Admin','missing_person','fdsfdfdsf','uploads/posts/admin_6a26e83bd59428.66235440.jpg','[\"uploads/posts/admin_6a26e83bd59428.66235440.jpg\"]','image',1,0,'approved','not_reported',NULL,NULL,0,NULL,NULL,'2026-06-08 16:05:15'),(8,1,'admin',0,'Admin','missing_person','fdsfsdfsdf','uploads/posts/admin_6a26eab8c29071.87886007.jpg','[\"uploads/posts/admin_6a26eab8c29071.87886007.jpg\"]','image',1,0,'approved','not_reported',NULL,NULL,1,4294967295,'{\"attempted\":true,\"shared\":true,\"post_id\":\"122116326938817051\",\"endpoint\":\"photos\",\"response\":{\"id\":\"122116326938817051\",\"post_id\":\"992332420641048_122116326968817051\"}}','2026-06-08 16:15:52'),(9,1,'admin',0,'Admin','missing_person','you admin   leader bro   localtion hosse  amr','uploads/posts/admin_6a26ec6f0179b7.07453022.jpg','[\"uploads/posts/admin_6a26ec6f0179b7.07453022.jpg\"]','image',1,0,'approved','not_reported',NULL,NULL,1,4294967295,'{\"attempted\":true,\"shared\":true,\"post_id\":\"122116327820817051\",\"endpoint\":\"photos\",\"response\":{\"id\":\"122116327820817051\",\"post_id\":\"992332420641048_122116327844817051\"}}','2026-06-08 16:23:11'),(10,1,'admin',0,'Admin','missing_person','leader','uploads/posts/admin_6a26ecb12c6b69.08660871.jpg','[\"uploads/posts/admin_6a26ecb12c6b69.08660871.jpg\"]','image',1,0,'approved','not_reported',NULL,NULL,1,4294967295,'{\"attempted\":true,\"shared\":true,\"post_id\":\"122116327946817051\",\"endpoint\":\"photos\",\"response\":{\"id\":\"122116327946817051\",\"post_id\":\"992332420641048_122116327988817051\"}}','2026-06-08 16:24:17'),(11,1,'admin',0,'Admin','missing_person','sdfsdfds','uploads/posts/admin_6a26ecc4b092d9.15237135.jpg','[\"uploads/posts/admin_6a26ecc4b092d9.15237135.jpg\"]','image',0,0,'approved','not_reported',NULL,NULL,0,NULL,NULL,'2026-06-08 16:24:36'),(12,1,'admin',0,'Admin','alert','hi bondhu','uploads/posts/admin_6a2717bca2c8f3.51388135.jpg','[\"uploads/posts/admin_6a2717bca2c8f3.51388135.jpg\"]','image',1,0,'approved','not_reported',NULL,NULL,0,NULL,NULL,'2026-06-08 19:27:56'),(13,1,'admin',0,'Admin','missing_person','hiii','uploads/posts/admin_6a297a8a2c1cd7.36286827.jpg','[\"uploads/posts/admin_6a297a8a2c1cd7.36286827.jpg\"]','image',1,0,'approved','not_reported',NULL,NULL,0,NULL,NULL,'2026-06-10 14:54:02'),(14,1,'admin',0,'Admin','missing_person','hii','uploads/posts/admin_6a2a4db7bc0cf2.91333390.jpg','[\"uploads/posts/admin_6a2a4db7bc0cf2.91333390.jpg\"]','image',1,0,'approved','not_reported',NULL,NULL,0,NULL,NULL,'2026-06-11 05:55:03'),(15,1,'admin',0,'Admin','alert','hiii','uploads/posts/admin_6a2a4f3379ac38.46524596.jpg','[\"uploads/posts/admin_6a2a4f3379ac38.46524596.jpg\"]','image',1,0,'approved','not_reported',NULL,NULL,1,4294967295,'{\"attempted\":true,\"shared\":true,\"post_id\":\"122116723742817051\",\"endpoint\":\"photos\",\"response\":{\"id\":\"122116723742817051\",\"post_id\":\"992332420641048_122116723772817051\"}}','2026-06-11 06:01:23'),(16,1,'admin',0,'Admin','alert','hiii','uploads/posts/admin_6a2a5246df0ff9.26321895.jpg','[\"uploads/posts/admin_6a2a5246df0ff9.26321895.jpg\"]','image',1,0,'approved','not_reported',NULL,NULL,1,4294967295,'{\"attempted\":true,\"shared\":true,\"post_id\":\"122116726112817051\",\"endpoint\":\"photos\",\"response\":{\"id\":\"122116726112817051\",\"post_id\":\"992332420641048_122116726136817051\"}}','2026-06-11 06:14:30'),(17,1,'user',1,'Rohim Isam','general','Test multiple images directly',NULL,NULL,NULL,0,0,'pending','not_reported',NULL,NULL,0,NULL,NULL,'2026-06-11 09:37:04'),(18,1,'',0,NULL,'general','Test multiple images','uploads/posts/ecd5144e8a4a5df9c87d.jpg','[\"uploads/posts/ecd5144e8a4a5df9c87d.jpg\",\"uploads/posts/b6abc81e1228f18c3371.jpg\"]','image',0,0,'pending','not_reported',NULL,NULL,0,NULL,NULL,'2026-06-11 09:42:20'),(19,1,'police',1,'Najmul Hossain Nur','mission',NULL,'uploads/posts/3a308afe8ce8d1e7a54a.mp4',NULL,'video',0,0,'pending','not_reported',NULL,NULL,0,NULL,NULL,'2026-06-11 10:22:01');
CREATE TABLE `rescue_stories` (
  `story_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `author_name` varchar(150) NOT NULL,
  `author_role` varchar(100) NOT NULL DEFAULT 'User',
  `story_text` text NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`story_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
INSERT INTO `rescue_stories` VALUES (1,1,'dfdsfsdf','hfjdshfjd','fsdfsdfsdfsdf','approved','2026-06-08 11:07:14');
CREATE TABLE `signup_blacklist` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entity` varchar(60) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `mobile` varchar(30) DEFAULT NULL,
  `nid_number` varchar(100) DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `blocked_by` varchar(100) DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_blacklist_email` (`email`),
  KEY `idx_blacklist_mobile` (`mobile`),
  KEY `idx_blacklist_nid` (`nid_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `traffic_logs` (
  `traffic_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `referrer` varchar(255) DEFAULT NULL,
  `source` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`traffic_id`),
  KEY `idx_traffic_created` (`created_at`),
  KEY `idx_traffic_referrer` (`referrer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `user_combo_roles` (
  `combo_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `volunteer_id` int(10) unsigned NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'approved',
  `approved_by` varchar(100) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`combo_id`),
  UNIQUE KEY `uq_user_combo_user` (`user_id`),
  UNIQUE KEY `uq_user_combo_volunteer` (`volunteer_id`),
  KEY `idx_user_combo_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `user_notifications` (
  `notification_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `recipient_entity` varchar(60) NOT NULL,
  `recipient_id` int(10) unsigned NOT NULL,
  `title` varchar(190) NOT NULL,
  `message` text NOT NULL,
  `meta_json` text DEFAULT NULL,
  `level` varchar(30) NOT NULL DEFAULT 'info',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `target_post_id` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`notification_id`),
  KEY `idx_recipient` (`recipient_entity`,`recipient_id`),
  KEY `idx_notification_target_post` (`target_post_id`),
  KEY `idx_notification_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=110 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `user_notifications` VALUES (1,'user',1,'Admin Reminder','Welcome to SEARCHAR. Please complete your profile (photo, cover, date of birth, gender and address) from Edit Profile.',NULL,'warning',1,NULL,'2026-06-04 16:55:21'),(2,'volunteer',1,'Post submitted for review','We received your post. An admin will review it shortly.',NULL,'info',0,1,'2026-06-05 10:43:39'),(3,'volunteer',1,'Your post was approved','An admin approved your post.',NULL,'success',1,1,'2026-06-05 10:54:09'),(4,'policeman',1,'Post submitted for review','We received your post. An admin will review it shortly.',NULL,'info',0,2,'2026-06-05 15:47:41'),(5,'policeman',1,'Your post was approved','An admin approved your post.',NULL,'success',0,2,'2026-06-05 15:47:58'),(6,'policeman',0,'Admin Case Alert','Admin marked a post as reported. Please review it in All Cases.',NULL,'warning',0,2,'2026-06-05 15:48:03'),(7,'volunteer',1,'New Crime Assignment | Locate & Verify Alert','You have been assigned to a crime case (PT0002) near mission. Locate and verify the alert spot, then send a quick ground update.','{\"case_id\":\"PT0002\",\"landmark\":\"mission\",\"mission_type\":\"locate_verify\",\"mission_label\":\"Locate & Verify Alert\",\"mission_note\":\"Locate and verify the alert spot, then send a quick ground update.\",\"mission_id\":1,\"media\":[{\"type\":\"media\",\"url\":\"uploads/posts/019194fccbc393e7a8bf.jpg\",\"hash\":\"\"}]}','info',0,NULL,'2026-06-05 15:51:51'),(8,'user',2,'Admin Reminder','Welcome to SEARCHAR. Please complete your profile (photo, cover, date of birth, gender and address) from Edit Profile.',NULL,'warning',1,NULL,'2026-06-06 17:46:47'),(9,'contributor',2,'Admin: Stream Earnings','You earned BDT 20 from your live feed. Keep streaming to earn more.','{\"feed_id\":1,\"payout_count\":1,\"amount\":20}','success',1,NULL,'2026-06-07 12:41:40'),(10,'contributor',2,'Admin: Stream Earnings','You earned BDT 20 from your live feed. Keep streaming to earn more.','{\"feed_id\":3,\"payout_count\":1,\"amount\":20}','success',1,NULL,'2026-06-07 12:45:16'),(11,'policeman',0,'Admin Missing Case Alert','Admin escalated a missing person report for police review. Check All Cases.',NULL,'warning',0,NULL,'2026-06-07 13:12:11'),(12,'user',1,'Post submitted for review','We received your post. An admin will review it shortly.',NULL,'info',1,4,'2026-06-07 13:15:12'),(13,'user',1,'Your post was approved','An admin approved your post.',NULL,'success',1,4,'2026-06-07 13:15:30'),(14,'contributor',2,'Admin: Stream Earnings','You earned BDT 20 from your live feed. Keep streaming to earn more.','{\"feed_id\":3,\"payout_count\":2,\"amount\":20}','success',1,NULL,'2026-06-07 13:21:21'),(15,'contributor',2,'Admin: Stream Earnings','You earned BDT 120 from your live feed. Keep streaming to earn more.','{\"feed_id\":1,\"payout_count\":7,\"amount\":120}','success',1,NULL,'2026-06-07 16:35:47'),(16,'contributor',2,'Admin: Stream Earnings','You earned BDT 560 from your live feed. Keep streaming to earn more.','{\"feed_id\":1,\"payout_count\":35,\"amount\":560}','success',1,NULL,'2026-06-08 06:26:27'),(17,'contributor',2,'Admin: Stream Earnings','You earned BDT 40 from your live feed. Keep streaming to earn more.','{\"feed_id\":1,\"payout_count\":37,\"amount\":40}','success',1,NULL,'2026-06-08 07:31:36'),(18,'users',1,'SMS: Case Solved','Your case MP0001 has been solved using AI.',NULL,'success',1,NULL,'2026-06-08 09:15:50'),(19,'users',1,'Consider a Donation','We are glad your case was resolved! Please consider making a donation to support Searchar.',NULL,'info',1,NULL,'2026-06-08 09:15:50'),(20,'users',1,'Please give us a review','Your feedback helps us improve. Please leave a review for Searchar.',NULL,'info',1,NULL,'2026-06-08 09:15:50'),(21,'policemen',1,'Case Closed by Admin AI','Case MP0001 has been resolved automatically by Admin AI.',NULL,'info',0,NULL,'2026-06-08 09:15:55'),(22,'users',1,'Verification Required','We found the person you are looking for! Please bring your ID card for verification. Handover ID: HO-4BA5F7F8',NULL,'warning',1,NULL,'2026-06-08 09:38:51'),(23,'users',1,'Verification Required','We found the person you are looking for! Please bring your ID card for verification. Handover ID: HO-ED7F33D2',NULL,'warning',1,NULL,'2026-06-08 09:49:11'),(24,'policeman',0,'Case Closed by Admin','Admin closed a missing person case. Live board will auto-sync to solved history.',NULL,'info',0,NULL,'2026-06-08 09:51:47'),(25,'users',1,'SMS: Case Solved','Your case PT0002 has been solved using AI.',NULL,'success',1,NULL,'2026-06-08 09:53:05'),(26,'users',1,'Consider a Donation','We are glad your case was resolved! Please consider making a donation to support Searchar.',NULL,'info',1,NULL,'2026-06-08 09:53:05'),(27,'users',1,'Please give us a review','Your feedback helps us improve. Please leave a review for Searchar.',NULL,'info',1,NULL,'2026-06-08 09:53:05'),(28,'volunteers',1,'Mission Auto-Closed (AI Match)','Case PT0002 was solved by Admin AI. Mission closed. You earned +5 XP for accepting.',NULL,'info',0,NULL,'2026-06-08 09:53:09'),(29,'users',1,'Verification Required','We found the person you are looking for! Please bring your ID card for verification. Handover ID: HO-BE5FFD99\n\nMatch Details: 98% Match\n Post by Najmul Hossain Nur\n2026-06-05 21:47:40\n\"sdfdsfsdf\"',NULL,'warning',1,NULL,'2026-06-08 09:56:18'),(30,'policeman',0,'Admin Case Alert','Admin marked a post as reported. Please review it in All Cases.',NULL,'warning',0,4,'2026-06-08 09:58:19'),(31,'policeman',0,'Admin Case Alert','Admin marked a post as reported. Please review it in All Cases.',NULL,'warning',0,2,'2026-06-08 09:58:22'),(32,'policeman',0,'Admin Missing Case Alert','Admin escalated a missing person report for police review. Check All Cases.',NULL,'warning',0,NULL,'2026-06-08 10:04:17'),(33,'users',1,'SMS: Case Solved','Your case MP0003 has been solved using AI.',NULL,'success',1,NULL,'2026-06-08 10:07:10'),(34,'users',1,'Consider a Donation','We are glad your case was resolved! Please consider making a donation to support Searchar.',NULL,'info',1,NULL,'2026-06-08 10:07:10'),(35,'users',1,'Please give us a review','Your feedback helps us improve. Please leave a review for Searchar.',NULL,'info',1,NULL,'2026-06-08 10:07:10'),(36,'users',1,'Verification Required','We found the person you are looking for! Please bring your ID card for verification. Handover ID: HO-949D208C\n\nMatch Details: 79% Match\n Post by Rohim Isam\n2026-06-07 19:15:12\n\"i think he was lost so hep her last seen - Badda time- 10:50 PM\"',NULL,'warning',1,NULL,'2026-06-08 10:07:23'),(37,'policeman',0,'Admin Missing Case Alert','Admin escalated a missing person report for police review. Check All Cases.',NULL,'warning',1,NULL,'2026-06-08 10:50:49'),(38,'users',1,'SMS: Case Solved','Your case MP0004 has been solved using AI.',NULL,'success',1,NULL,'2026-06-08 10:52:29'),(39,'users',1,'Consider a Donation','We are glad your case was resolved! Please consider making a donation to support Searchar.',NULL,'info',1,NULL,'2026-06-08 10:52:29'),(40,'users',1,'Please give us a review','Your feedback helps us improve. Please leave a review for Searchar.',NULL,'info',1,NULL,'2026-06-08 10:52:29'),(41,'users',1,'Verification Required','We found the person you are looking for! Please bring your ID card for verification. Handover ID: HO-C5391D06\n\nMatch Details: 87% Match\n Post by Rohim Isam\n2026-06-07 19:15:12\n\"i think he was lost so hep her last seen - Badda time- 10:50 PM\"',NULL,'warning',1,NULL,'2026-06-08 10:52:50'),(42,'admins',0,'New Rescue Story','A new rescue story has been submitted by dfdsfsdf for review.',NULL,'info',0,NULL,'2026-06-08 11:07:14'),(43,'users',1,'Verification Required','We found the person you are looking for! Please bring your ID card for verification. Handover ID: HO-27638E66\n\nThey were successfully located by our team.',NULL,'warning',1,NULL,'2026-06-08 11:16:30'),(44,'users',1,'Verification Required','We found the person you are looking for! Please bring your ID card for verification. Handover ID: HO-130596B4\n\nThey were successfully located by our team.',NULL,'warning',1,NULL,'2026-06-08 11:16:40'),(45,'users',1,'Thank You! (Case MP0004)','Your post (ID: 4) was matched by our AI and helped us find a missing person for Case MP0004. Thank you for your help!',NULL,'success',1,NULL,'2026-06-08 11:19:09'),(46,'users',1,'SMS: Case Solved','Your case MP0004 has been solved using AI.',NULL,'success',0,NULL,'2026-06-08 16:38:54'),(47,'users',1,'Consider a Donation','We are glad your case was resolved! Please consider making a donation to support Searchar.',NULL,'info',0,NULL,'2026-06-08 16:38:54'),(48,'users',1,'Please give us a review','Your feedback helps us improve. Please leave a review for Searchar.',NULL,'info',0,NULL,'2026-06-08 16:38:54'),(49,'users',1,'Verification Required','We found the person you are looking for! Please bring your ID card for verification. Handover ID: HO-F56C52C1\n\nMatch Details: 87% Match\n Post by Admin\n2026-06-08 22:23:11\n\"you admin leader bro localtion hosse amr\"',NULL,'warning',0,NULL,'2026-06-08 16:39:01'),(50,'users',1,'Thank You! (Case MP0004)','Your post (ID: 4) was matched by our AI and helped us find a missing person for Case MP0004. Thank you for your help!',NULL,'success',0,NULL,'2026-06-08 19:58:55'),(51,'contributor',2,'Admin: Stream Earnings','You earned BDT 900 from your live feed. Keep streaming to earn more.','{\"feed_id\":1,\"payout_count\":82,\"amount\":900}','success',1,NULL,'2026-06-09 05:53:13'),(52,'contributor',2,'Admin: Stream Earnings','You earned BDT 20 from your live feed. Keep streaming to earn more.','{\"feed_id\":1,\"payout_count\":83,\"amount\":20}','success',1,NULL,'2026-06-09 06:09:07'),(53,'users',1,'SMS: Case Solved','Your case PT0004 has been solved using AI.',NULL,'success',0,NULL,'2026-06-09 06:30:19'),(54,'users',1,'Consider a Donation','We are glad your case was resolved! Please consider making a donation to support Searchar.',NULL,'info',0,NULL,'2026-06-09 06:30:19'),(55,'users',1,'Please give us a review','Your feedback helps us improve. Please leave a review for Searchar.',NULL,'info',0,NULL,'2026-06-09 06:30:19'),(56,'users',1,'Verification Required','We found the person you are looking for! Please bring your ID card for verification. Handover ID: HO-DE1E8BB4\n\nMatch Details: 98% Match\n Post by Admin\n2026-06-08 22:23:11\n\"you admin leader bro localtion hosse amr\"',NULL,'warning',0,NULL,'2026-06-09 06:34:59'),(57,'users',1,'Thank You! (Case PT0004)','Your post (ID: 4) was matched by our AI and helped us find a missing person for Case PT0004. Thank you for your help!',NULL,'success',0,NULL,'2026-06-09 08:09:46'),(58,'contributor',2,'Admin: Stream Earnings','You earned BDT 1280 from your live feed. Keep streaming to earn more.','{\"feed_id\":4,\"payout_count\":64,\"amount\":1280}','success',1,NULL,'2026-06-10 14:28:39'),(59,'policeman',0,'Admin Case Alert','Admin marked a post as reported. Please review it in All Cases.',NULL,'warning',0,4,'2026-06-10 14:34:23'),(60,'users',1,'SMS: Case Solved','Your case PT0004 has been solved using AI.',NULL,'success',0,NULL,'2026-06-10 14:51:48'),(61,'users',1,'Consider a Donation','We are glad your case was resolved! Please consider making a donation to support Searchar.',NULL,'info',0,NULL,'2026-06-10 14:51:48'),(62,'users',1,'Please give us a review','Your feedback helps us improve. Please leave a review for Searchar.',NULL,'info',0,NULL,'2026-06-10 14:51:48'),(63,'users',1,'Verification Required','We found the person you are looking for! Please bring your ID card for verification. Handover ID: HO-449520DB\n\nMatch Details: 43.36% Match\n Camera 2 (Dhaka purbo vatara jame mosjid, Saind Nagar Auto stand, vaata thana, notun bazar,Dhaka Bangladesh)\nCaptured at: 2026-06-10 04:51 PM',NULL,'warning',0,NULL,'2026-06-10 14:52:00'),(64,'contributor',2,'Admin: Stream Earnings','You earned BDT 20 from your live feed. Keep streaming to earn more.','{\"feed_id\":6,\"payout_count\":1,\"amount\":20}','success',1,NULL,'2026-06-10 15:01:25'),(65,'contributor',2,'Admin: Stream Earnings','You earned BDT 20 from your live feed. Keep streaming to earn more.','{\"feed_id\":7,\"payout_count\":1,\"amount\":20}','success',1,NULL,'2026-06-10 15:02:54'),(66,'contributor',2,'Admin: Stream Earnings','You earned BDT 20 from your live feed. Keep streaming to earn more.','{\"feed_id\":6,\"payout_count\":2,\"amount\":20}','success',1,NULL,'2026-06-10 15:39:00'),(67,'contributor',2,'Admin: Stream Earnings','You earned BDT 20 from your live feed. Keep streaming to earn more.','{\"feed_id\":7,\"payout_count\":2,\"amount\":20}','success',1,NULL,'2026-06-10 15:39:00'),(68,'contributor',2,'Admin: Stream Earnings','You earned BDT 20 from your live feed. Keep streaming to earn more.','{\"feed_id\":6,\"payout_count\":3,\"amount\":20}','success',1,NULL,'2026-06-10 16:01:18'),(69,'contributor',2,'Admin: Stream Earnings','You earned BDT 20 from your live feed. Keep streaming to earn more.','{\"feed_id\":7,\"payout_count\":3,\"amount\":20}','success',1,NULL,'2026-06-10 16:03:18'),(70,'contributor',2,'Admin: Stream Earnings','You earned BDT 100 from your live feed. Keep streaming to earn more.','{\"feed_id\":6,\"payout_count\":8,\"amount\":100}','success',1,NULL,'2026-06-10 18:42:05'),(71,'contributor',2,'Admin: Stream Earnings','You earned BDT 100 from your live feed. Keep streaming to earn more.','{\"feed_id\":7,\"payout_count\":8,\"amount\":100}','success',1,NULL,'2026-06-10 18:42:05'),(72,'contributor',2,'Admin: Stream Earnings','You earned BDT 20 from your live feed. Keep streaming to earn more.','{\"feed_id\":6,\"payout_count\":9,\"amount\":20}','success',1,NULL,'2026-06-10 19:01:18'),(73,'contributor',2,'Admin: Stream Earnings','You earned BDT 20 from your live feed. Keep streaming to earn more.','{\"feed_id\":7,\"payout_count\":9,\"amount\":20}','success',1,NULL,'2026-06-10 19:03:18'),(74,'contributor',2,'Admin: Stream Earnings','You earned BDT 20 from your live feed. Keep streaming to earn more.','{\"feed_id\":6,\"payout_count\":10,\"amount\":20}','success',1,NULL,'2026-06-10 19:31:22'),(75,'contributor',2,'Admin: Stream Earnings','You earned BDT 20 from your live feed. Keep streaming to earn more.','{\"feed_id\":7,\"payout_count\":10,\"amount\":20}','success',1,NULL,'2026-06-10 19:32:58'),(76,'contributor',2,'Admin: Stream Earnings','You earned BDT 20 from your live feed. Keep streaming to earn more.','{\"feed_id\":6,\"payout_count\":11,\"amount\":20}','success',1,NULL,'2026-06-10 20:01:22'),(77,'contributor',2,'Admin: Stream Earnings','You earned BDT 20 from your live feed. Keep streaming to earn more.','{\"feed_id\":7,\"payout_count\":11,\"amount\":20}','success',1,NULL,'2026-06-10 20:03:11'),(78,'policeman',0,'Admin Case Alert','Admin marked a post as reported. Please review it in All Cases.',NULL,'warning',1,4,'2026-06-10 20:04:06'),(79,'contributor',2,'Admin: Stream Earnings','You earned BDT 20 from your live feed. Keep streaming to earn more.','{\"feed_id\":6,\"payout_count\":12,\"amount\":20}','success',1,NULL,'2026-06-10 20:31:20'),(80,'contributor',2,'Admin: Stream Earnings','You earned BDT 20 from your live feed. Keep streaming to earn more.','{\"feed_id\":8,\"payout_count\":1,\"amount\":20}','success',1,NULL,'2026-06-10 20:31:50'),(81,'users',1,'Verification Required','We found the person you are looking for! Please bring your ID card for verification. Handover ID: HO-CC78E7C2\n\nThey were successfully located by our team.',NULL,'warning',0,NULL,'2026-06-10 22:15:58'),(82,'admin',0,'Broadcast Request','Broadcast request from Najmul Hossain Nur (dddd). Reason: need  bro','{\"type\":\"broadcast_request\",\"police_id\":1,\"police_name\":\"Najmul Hossain Nur\",\"station\":\"dddd\",\"status\":\"approved\",\"request_reason\":\"need  bro\",\"actioned_at\":\"2026-06-11 00:34:49\"}','info',1,NULL,'2026-06-10 22:34:31'),(83,'police',1,'Broadcast Approval','Your broadcast request was approved. You can join the broadcast desk now.','{\"type\":\"broadcast_request\",\"status\":\"approved\",\"reason\":\"\",\"request_id\":82,\"police_id\":1,\"police_name\":\"Najmul Hossain Nur\",\"station\":\"dddd\"}','success',0,NULL,'2026-06-10 22:34:49'),(84,'contributor',2,'Admin: Stream Earnings','You earned BDT 360 from your live feed. Keep streaming to earn more.','{\"feed_id\":10,\"payout_count\":18,\"amount\":360}','success',1,NULL,'2026-06-11 07:23:23'),(85,'contributor',2,'Admin: Stream Earnings','You earned BDT 20 from your live feed. Keep streaming to earn more.','{\"feed_id\":10,\"payout_count\":19,\"amount\":20}','success',1,NULL,'2026-06-11 07:51:49'),(86,'contributor',2,'Admin: Stream Earnings','You earned BDT 20 from your live feed. Keep streaming to earn more.','{\"feed_id\":11,\"payout_count\":1,\"amount\":20}','success',1,NULL,'2026-06-11 07:54:50'),(87,'contributor',2,'Admin: Stream Earnings','You earned BDT 20 from your live feed. Keep streaming to earn more.','{\"feed_id\":12,\"payout_count\":1,\"amount\":20}','success',1,NULL,'2026-06-11 07:54:50'),(88,'contributor',2,'Admin: Stream Earnings','You earned BDT 20 from your live feed. Keep streaming to earn more.','{\"feed_id\":13,\"payout_count\":1,\"amount\":20}','success',1,NULL,'2026-06-11 07:55:47'),(89,'contributor',2,'Admin: Stream Earnings','You earned BDT 20 from your live feed. Keep streaming to earn more.','{\"feed_id\":10,\"payout_count\":20,\"amount\":20}','success',0,NULL,'2026-06-11 08:16:39'),(90,'contributor',2,'Admin: Withdrawal Approved','Your withdrawal request of aº¦200.00 has been approved.','{\"request_id\":1,\"amount\":\"200.00\",\"status\":\"approved\"}','success',0,NULL,'2026-06-11 08:23:51'),(91,'contributor',2,'Admin: Stream Earnings','You earned BDT 20 from your live feed. Keep streaming to earn more.','{\"feed_id\":11,\"payout_count\":2,\"amount\":20}','success',0,NULL,'2026-06-11 08:24:47'),(92,'contributor',2,'Admin: Stream Earnings','You earned BDT 20 from your live feed. Keep streaming to earn more.','{\"feed_id\":12,\"payout_count\":2,\"amount\":20}','success',0,NULL,'2026-06-11 08:24:47'),(93,'contributor',2,'Admin: Stream Earnings','You earned BDT 20 from your live feed. Keep streaming to earn more.','{\"feed_id\":13,\"payout_count\":2,\"amount\":20}','success',0,NULL,'2026-06-11 08:25:47'),(94,'contributor',2,'Admin: Withdrawal Approved','Your withdrawal request of aº¦200.00 has been approved.','{\"request_id\":2,\"amount\":\"200.00\",\"status\":\"approved\"}','success',0,NULL,'2026-06-11 08:26:45'),(95,'contributor',2,'Admin: Stream Earnings','You earned BDT 20 from your live feed. Keep streaming to earn more.','{\"feed_id\":10,\"payout_count\":21,\"amount\":20}','success',0,NULL,'2026-06-11 08:46:41'),(96,'contributor',2,'Admin: Stream Earnings','You earned BDT 20 from your live feed. Keep streaming to earn more.','{\"feed_id\":11,\"payout_count\":3,\"amount\":20}','success',0,NULL,'2026-06-11 08:54:47'),(97,'contributor',2,'Admin: Stream Earnings','You earned BDT 20 from your live feed. Keep streaming to earn more.','{\"feed_id\":12,\"payout_count\":3,\"amount\":20}','success',0,NULL,'2026-06-11 08:54:47'),(98,'contributor',2,'Admin: Stream Earnings','You earned BDT 20 from your live feed. Keep streaming to earn more.','{\"feed_id\":13,\"payout_count\":3,\"amount\":20}','success',0,NULL,'2026-06-11 08:55:47'),(99,'contributor',2,'Admin: Stream Earnings','You earned BDT 20 from your live feed. Keep streaming to earn more.','{\"feed_id\":10,\"payout_count\":22,\"amount\":20}','success',0,NULL,'2026-06-11 09:16:24'),(100,'contributor',2,'Admin: Stream Earnings','You earned BDT 20 from your live feed. Keep streaming to earn more.','{\"feed_id\":11,\"payout_count\":4,\"amount\":20}','success',0,NULL,'2026-06-11 09:24:47'),(101,'contributor',2,'Admin: Stream Earnings','You earned BDT 20 from your live feed. Keep streaming to earn more.','{\"feed_id\":12,\"payout_count\":4,\"amount\":20}','success',0,NULL,'2026-06-11 09:24:47'),(102,'contributor',2,'Admin: Stream Earnings','You earned BDT 20 from your live feed. Keep streaming to earn more.','{\"feed_id\":13,\"payout_count\":4,\"amount\":20}','success',0,NULL,'2026-06-11 09:25:47'),(103,'user',1,'Post submitted for review','We received your post. An admin will review it shortly.',NULL,'info',0,17,'2026-06-11 09:37:04'),(104,'user',0,'Post submitted for review','We received your post. An admin will review it shortly.',NULL,'info',0,18,'2026-06-11 09:42:20'),(105,'contributor',2,'Admin: Stream Earnings','You earned BDT 20 from your live feed. Keep streaming to earn more.','{\"feed_id\":10,\"payout_count\":23,\"amount\":20}','success',0,NULL,'2026-06-11 09:46:08'),(106,'contributor',2,'Admin: Stream Earnings','You earned BDT 20 from your live feed. Keep streaming to earn more.','{\"feed_id\":11,\"payout_count\":5,\"amount\":20}','success',0,NULL,'2026-06-11 09:54:38'),(107,'contributor',2,'Admin: Stream Earnings','You earned BDT 20 from your live feed. Keep streaming to earn more.','{\"feed_id\":12,\"payout_count\":5,\"amount\":20}','success',0,NULL,'2026-06-11 09:55:08'),(108,'contributor',2,'Admin: Stream Earnings','You earned BDT 20 from your live feed. Keep streaming to earn more.','{\"feed_id\":13,\"payout_count\":5,\"amount\":20}','success',0,NULL,'2026-06-11 09:55:08'),(109,'policeman',1,'Post submitted for review','We received your post. An admin will review it shortly.',NULL,'info',0,19,'2026-06-11 10:22:02');
CREATE TABLE `users` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `mobile` varchar(30) DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `cover_photo` varchar(255) DEFAULT NULL,
  `bio` varchar(500) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `street` varchar(255) DEFAULT NULL,
  `city` varchar(120) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(120) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_mobile` (`mobile`),
  KEY `idx_users_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `users` VALUES (1,'Rohim Isam','najmulhossainnur5@gmail.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'$2y$10$ZeNambscyr3XOm4SFGJkXe8.FpPIskfKVV7kDxK5LbyG9Nh.mCzXS','2026-06-04 16:55:21'),(2,'Najmul Hossain Nur','najmulhosainnur55@gmail.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'$2y$10$QQoqulpqTceTW0.eKdFVP.5CCv/meKeTb/MaTDjc3IX0LwBzDGkom','2026-06-06 17:46:47');
CREATE TABLE `volunteer_applications` (
  `application_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `mobile` varchar(30) DEFAULT NULL,
  `nid_number` varchar(100) DEFAULT NULL,
  `city` varchar(120) DEFAULT NULL,
  `country` varchar(120) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `reviewed_by` varchar(100) DEFAULT NULL,
  `review_note` varchar(255) DEFAULT NULL,
  `volunteer_id` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reviewed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`application_id`),
  UNIQUE KEY `uq_volunteer_application_user` (`user_id`),
  KEY `idx_volunteer_application_status` (`status`),
  KEY `idx_volunteer_application_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `volunteer_missions` (
  `mission_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `volunteer_id` int(10) unsigned NOT NULL,
  `mission_title` varchar(190) NOT NULL,
  `mission_details` text DEFAULT NULL,
  `mission_location` varchar(255) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'assigned',
  `response_status` varchar(30) NOT NULL DEFAULT 'pending',
  `case_ref` varchar(80) DEFAULT NULL,
  `source_notification_id` int(10) unsigned DEFAULT NULL,
  `proof_file` varchar(255) DEFAULT NULL,
  `proof_submitted_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `assigned_by` varchar(100) NOT NULL DEFAULT 'admin',
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`mission_id`),
  KEY `idx_vm_volunteer` (`volunteer_id`),
  KEY `idx_vm_status` (`status`),
  KEY `idx_vm_response` (`response_status`),
  KEY `idx_vm_source_notification` (`source_notification_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `volunteer_missions` VALUES (1,1,'Locate & Verify Alert','Locate and verify the alert spot, then send a quick ground update.','mission','assigned','pending','PT0002',7,NULL,NULL,'2026-06-08 15:53:09','admin','2026-06-05 15:51:51');
CREATE TABLE `volunteers` (
  `volunteer_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(255) NOT NULL,
  `mobile` varchar(30) NOT NULL,
  `nid_number` varchar(100) NOT NULL,
  `nid_photo` varchar(255) NOT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `cover_photo` varchar(255) DEFAULT NULL,
  `bio` varchar(500) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `street` varchar(255) DEFAULT NULL,
  `city` varchar(120) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(120) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `occupation` varchar(120) DEFAULT NULL,
  `availability` varchar(120) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `chat_status` enum('online','offline') DEFAULT 'offline',
  `chat_last_seen` datetime DEFAULT NULL,
  PRIMARY KEY (`volunteer_id`),
  UNIQUE KEY `uq_volunteers_email` (`email`),
  UNIQUE KEY `uq_volunteers_mobile` (`mobile`),
  UNIQUE KEY `uq_volunteers_nid` (`nid_number`),
  KEY `idx_volunteers_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `volunteers` VALUES (1,'Najmul Hossain Nur','najmulhosainnur5@gmail.com','01743094595','jhertjhfjkdhsj','nid__6a22a480f12fc2.77352238.jpg','profile__6a22a480f1fc53.68614782.jpg','cover__6a22a480f26e38.92366148.jpg',NULL,'2026-06-04','female','purbo vatara jame mosjid, Saind Nagar Auto stand, vaata thana, notun bazar,Dhaka','Dhaka',NULL,'Bangladesh',0.0000000,0.0000000,'$2y$10$lHMTypVKo4cfjK8X/FmIVOTzGIri7Q/oZVwbqQchtc.hIXpCta1AG','student','full_time','2026-06-05 10:27:12','offline',NULL);
CREATE TABLE `withdraw_requests` (
  `withdraw_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `requester_name` varchar(150) NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `request_date` datetime NOT NULL DEFAULT current_timestamp(),
  `request_note` varchar(120) DEFAULT NULL,
  `tx_id` varchar(100) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`withdraw_id`),
  KEY `idx_withdraw_status` (`status`),
  KEY `idx_withdraw_request_date` (`request_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `withdrawal_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contributor_id` int(10) unsigned NOT NULL,
  `method` varchar(60) NOT NULL,
  `account_number` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` datetime DEFAULT NULL,
  `tx_id` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `withdrawal_requests` VALUES (1,2,'bkash','01743094595',200.00,'approved','2026-06-11 08:16:48','2026-06-11 14:23:51',NULL),(2,2,'bkash','01743094595',200.00,'approved','2026-06-11 08:26:25','2026-06-11 14:26:45',NULL);
