-- Database schema dump generated on 2026-06-08 10:38:12
-- Database: searchar

-- ----------------------------
-- Table structure for `admin_chat_messages`
-- ----------------------------
DROP TABLE IF EXISTS `admin_chat_messages`;
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

-- ----------------------------
-- Table structure for `admin_logs`
-- ----------------------------
DROP TABLE IF EXISTS `admin_logs`;
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

-- ----------------------------
-- Table structure for `admins`
-- ----------------------------
DROP TABLE IF EXISTS `admins`;
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

-- ----------------------------
-- Table structure for `auth_users`
-- ----------------------------
DROP TABLE IF EXISTS `auth_users`;
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

-- ----------------------------
-- Table structure for `camera_cctv_feeds`
-- ----------------------------
DROP TABLE IF EXISTS `camera_cctv_feeds`;
CREATE TABLE `camera_cctv_feeds` (
  `feed_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `camera_id` int(10) unsigned NOT NULL,
  `feed_label` varchar(150) NOT NULL,
  `feed_type` varchar(20) NOT NULL DEFAULT 'live',
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`feed_id`),
  KEY `idx_camera_active` (`camera_id`,`is_active`),
  KEY `idx_camera_created` (`camera_id`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table structure for `camera_contributors`
-- ----------------------------
DROP TABLE IF EXISTS `camera_contributors`;
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
  `camera_type` varchar(120) NOT NULL,
  `payment_number` varchar(40) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`camera_id`),
  UNIQUE KEY `uq_camera_email` (`email`),
  UNIQUE KEY `uq_camera_mobile` (`mobile`),
  UNIQUE KEY `uq_camera_nid` (`nid_number`),
  KEY `idx_camera_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table structure for `chat_broadcasts`
-- ----------------------------
DROP TABLE IF EXISTS `chat_broadcasts`;
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

-- ----------------------------
-- Table structure for `chat_messages`
-- ----------------------------
DROP TABLE IF EXISTS `chat_messages`;
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

-- ----------------------------
-- Table structure for `chatbot_admin_replies`
-- ----------------------------
DROP TABLE IF EXISTS `chatbot_admin_replies`;
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table structure for `chatbot_comment_templates`
-- ----------------------------
DROP TABLE IF EXISTS `chatbot_comment_templates`;
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

-- ----------------------------
-- Table structure for `chatbot_logs`
-- ----------------------------
DROP TABLE IF EXISTS `chatbot_logs`;
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table structure for `comment_reports`
-- ----------------------------
DROP TABLE IF EXISTS `comment_reports`;
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

-- ----------------------------
-- Table structure for `conversations`
-- ----------------------------
DROP TABLE IF EXISTS `conversations`;
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

-- ----------------------------
-- Table structure for `crime_reports`
-- ----------------------------
DROP TABLE IF EXISTS `crime_reports`;
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table structure for `donations`
-- ----------------------------
DROP TABLE IF EXISTS `donations`;
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

-- ----------------------------
-- Table structure for `messages`
-- ----------------------------
DROP TABLE IF EXISTS `messages`;
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

-- ----------------------------
-- Table structure for `missing_person_reports`
-- ----------------------------
DROP TABLE IF EXISTS `missing_person_reports`;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table structure for `password_resets`
-- ----------------------------
DROP TABLE IF EXISTS `password_resets`;
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

-- ----------------------------
-- Table structure for `policemen`
-- ----------------------------
DROP TABLE IF EXISTS `policemen`;
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

-- ----------------------------
-- Table structure for `post_comments`
-- ----------------------------
DROP TABLE IF EXISTS `post_comments`;
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

-- ----------------------------
-- Table structure for `post_likes`
-- ----------------------------
DROP TABLE IF EXISTS `post_likes`;
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

-- ----------------------------
-- Table structure for `post_reports`
-- ----------------------------
DROP TABLE IF EXISTS `post_reports`;
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

-- ----------------------------
-- Table structure for `posts`
-- ----------------------------
DROP TABLE IF EXISTS `posts`;
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table structure for `signup_blacklist`
-- ----------------------------
DROP TABLE IF EXISTS `signup_blacklist`;
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

-- ----------------------------
-- Table structure for `traffic_logs`
-- ----------------------------
DROP TABLE IF EXISTS `traffic_logs`;
CREATE TABLE `traffic_logs` (
  `traffic_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `referrer` varchar(255) DEFAULT NULL,
  `source` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`traffic_id`),
  KEY `idx_traffic_created` (`created_at`),
  KEY `idx_traffic_referrer` (`referrer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table structure for `user_combo_roles`
-- ----------------------------
DROP TABLE IF EXISTS `user_combo_roles`;
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

-- ----------------------------
-- Table structure for `user_notifications`
-- ----------------------------
DROP TABLE IF EXISTS `user_notifications`;
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
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table structure for `users`
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `mobile` varchar(30) DEFAULT NULL,
  `nid_number` varchar(100) NOT NULL,
  `nid_photo` varchar(255) DEFAULT NULL,
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
  UNIQUE KEY `uq_users_nid` (`nid_number`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_mobile` (`mobile`),
  KEY `idx_users_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table structure for `volunteer_applications`
-- ----------------------------
DROP TABLE IF EXISTS `volunteer_applications`;
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

-- ----------------------------
-- Table structure for `volunteer_missions`
-- ----------------------------
DROP TABLE IF EXISTS `volunteer_missions`;
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

-- ----------------------------
-- Table structure for `volunteers`
-- ----------------------------
DROP TABLE IF EXISTS `volunteers`;
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

-- ----------------------------
-- Table structure for `withdraw_requests`
-- ----------------------------
DROP TABLE IF EXISTS `withdraw_requests`;
CREATE TABLE `withdraw_requests` (
  `withdraw_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `requester_name` varchar(150) NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `request_date` datetime NOT NULL DEFAULT current_timestamp(),
  `request_note` varchar(120) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`withdraw_id`),
  KEY `idx_withdraw_status` (`status`),
  KEY `idx_withdraw_request_date` (`request_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
