-- Searchar full schema
-- Generated from project code usage

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `searchar`
	CHARACTER SET utf8mb4
	COLLATE utf8mb4_unicode_ci;

USE `searchar`;

DROP TABLE IF EXISTS `traffic_logs`;
DROP TABLE IF EXISTS `camera_cctv_feeds`;
DROP TABLE IF EXISTS `withdraw_requests`;
DROP TABLE IF EXISTS `donations`;
DROP TABLE IF EXISTS `auth_users`;
DROP TABLE IF EXISTS `signup_blacklist`;
DROP TABLE IF EXISTS `volunteer_missions`;
DROP TABLE IF EXISTS `user_notifications`;
DROP TABLE IF EXISTS `missing_person_reports`;
DROP TABLE IF EXISTS `comment_reports`;
DROP TABLE IF EXISTS `post_reports`;
DROP TABLE IF EXISTS `post_comments`;
DROP TABLE IF EXISTS `post_likes`;
DROP TABLE IF EXISTS `posts`;
DROP TABLE IF EXISTS `camera_contributors`;
DROP TABLE IF EXISTS `volunteers`;
DROP TABLE IF EXISTS `policemen`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
	`user_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`full_name` VARCHAR(150) NOT NULL,
	`email` VARCHAR(255) NOT NULL,
	`mobile` VARCHAR(30) NOT NULL,
	`nid_number` VARCHAR(100) NOT NULL,
	`nid_photo` VARCHAR(255) DEFAULT NULL,
	`profile_photo` VARCHAR(255) DEFAULT NULL,
	`cover_photo` VARCHAR(255) DEFAULT NULL,
	`bio` VARCHAR(500) DEFAULT NULL,
	`date_of_birth` DATE DEFAULT NULL,
	`gender` VARCHAR(20) DEFAULT NULL,
	`street` VARCHAR(255) DEFAULT NULL,
	`city` VARCHAR(120) DEFAULT NULL,
	`postal_code` VARCHAR(20) DEFAULT NULL,
	`country` VARCHAR(120) DEFAULT NULL,
	`latitude` DECIMAL(10,7) DEFAULT NULL,
	`longitude` DECIMAL(10,7) DEFAULT NULL,
	`password_hash` VARCHAR(255) NOT NULL,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`user_id`),
	UNIQUE KEY `uq_users_email` (`email`),
	UNIQUE KEY `uq_users_mobile` (`mobile`),
	UNIQUE KEY `uq_users_nid` (`nid_number`),
	KEY `idx_users_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `policemen` (
	`police_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`full_name` VARCHAR(150) NOT NULL,
	`email` VARCHAR(255) NOT NULL,
	`mobile` VARCHAR(30) NOT NULL,
	`nid_number` VARCHAR(100) NOT NULL,
	`nid_photo` VARCHAR(255) NOT NULL,
	`profile_photo` VARCHAR(255) DEFAULT NULL,
	`cover_photo` VARCHAR(255) DEFAULT NULL,
	`bio` VARCHAR(500) DEFAULT NULL,
	`date_of_birth` DATE DEFAULT NULL,
	`gender` VARCHAR(20) DEFAULT NULL,
	`street` VARCHAR(255) DEFAULT NULL,
	`city` VARCHAR(120) DEFAULT NULL,
	`postal_code` VARCHAR(20) DEFAULT NULL,
	`country` VARCHAR(120) DEFAULT NULL,
	`latitude` DECIMAL(10,7) DEFAULT NULL,
	`longitude` DECIMAL(10,7) DEFAULT NULL,
	`password_hash` VARCHAR(255) NOT NULL,
	`badge_id` VARCHAR(100) NOT NULL,
	`designation` VARCHAR(120) NOT NULL,
	`station` VARCHAR(120) NOT NULL,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`police_id`),
	UNIQUE KEY `uq_policemen_email` (`email`),
	UNIQUE KEY `uq_policemen_mobile` (`mobile`),
	UNIQUE KEY `uq_policemen_nid` (`nid_number`),
	UNIQUE KEY `uq_policemen_badge` (`badge_id`),
	KEY `idx_policemen_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `volunteers` (
	`volunteer_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`full_name` VARCHAR(150) NOT NULL,
	`email` VARCHAR(255) NOT NULL,
	`mobile` VARCHAR(30) NOT NULL,
	`nid_number` VARCHAR(100) NOT NULL,
	`nid_photo` VARCHAR(255) NOT NULL,
	`profile_photo` VARCHAR(255) DEFAULT NULL,
	`cover_photo` VARCHAR(255) DEFAULT NULL,
	`bio` VARCHAR(500) DEFAULT NULL,
	`date_of_birth` DATE DEFAULT NULL,
	`gender` VARCHAR(20) DEFAULT NULL,
	`street` VARCHAR(255) DEFAULT NULL,
	`city` VARCHAR(120) DEFAULT NULL,
	`postal_code` VARCHAR(20) DEFAULT NULL,
	`country` VARCHAR(120) DEFAULT NULL,
	`latitude` DECIMAL(10,7) DEFAULT NULL,
	`longitude` DECIMAL(10,7) DEFAULT NULL,
	`password_hash` VARCHAR(255) NOT NULL,
	`occupation` VARCHAR(120) DEFAULT NULL,
	`availability` VARCHAR(120) DEFAULT NULL,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`volunteer_id`),
	UNIQUE KEY `uq_volunteers_email` (`email`),
	UNIQUE KEY `uq_volunteers_mobile` (`mobile`),
	UNIQUE KEY `uq_volunteers_nid` (`nid_number`),
	KEY `idx_volunteers_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `camera_contributors` (
	`camera_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`full_name` VARCHAR(150) NOT NULL,
	`email` VARCHAR(255) NOT NULL,
	`mobile` VARCHAR(30) NOT NULL,
	`nid_number` VARCHAR(100) NOT NULL,
	`nid_photo` VARCHAR(255) NOT NULL,
	`profile_photo` VARCHAR(255) DEFAULT NULL,
	`cover_photo` VARCHAR(255) DEFAULT NULL,
	`bio` VARCHAR(500) DEFAULT NULL,
	`date_of_birth` DATE DEFAULT NULL,
	`gender` VARCHAR(20) DEFAULT NULL,
	`street` VARCHAR(255) DEFAULT NULL,
	`city` VARCHAR(120) DEFAULT NULL,
	`postal_code` VARCHAR(20) DEFAULT NULL,
	`country` VARCHAR(120) DEFAULT NULL,
	`latitude` DECIMAL(10,7) DEFAULT NULL,
	`longitude` DECIMAL(10,7) DEFAULT NULL,
	`password_hash` VARCHAR(255) NOT NULL,
	`camera_type` VARCHAR(120) NOT NULL,
	`payment_number` VARCHAR(40) NOT NULL,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`camera_id`),
	UNIQUE KEY `uq_camera_email` (`email`),
	UNIQUE KEY `uq_camera_mobile` (`mobile`),
	UNIQUE KEY `uq_camera_nid` (`nid_number`),
	KEY `idx_camera_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `posts` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`case_id` INT NOT NULL DEFAULT 1,
	`author_role` VARCHAR(50) NOT NULL,
	`author_id` INT NOT NULL,
	`author_name` VARCHAR(255) DEFAULT NULL,
	`category` VARCHAR(50) DEFAULT 'general',
	`text` TEXT,
	`media_path` VARCHAR(512) DEFAULT NULL,
	`media_json` TEXT DEFAULT NULL,
	`media_type` ENUM('image','video','file') DEFAULT NULL,
	`share_facebook` TINYINT(1) NOT NULL DEFAULT 0,
	`share_anonymous` TINYINT(1) NOT NULL DEFAULT 0,
	`status` VARCHAR(20) DEFAULT 'pending',
	`report_status` VARCHAR(20) DEFAULT 'not_reported',
	`reported_at` DATETIME DEFAULT NULL,
	`report_closed_at` DATETIME DEFAULT NULL,
	`is_share` TINYINT(1) NOT NULL DEFAULT 0,
	`shared_post_id` INT UNSIGNED DEFAULT NULL,
	`shared_payload` LONGTEXT DEFAULT NULL,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	KEY `idx_posts_case` (`case_id`),
	KEY `idx_posts_author` (`author_role`, `author_id`),
	KEY `idx_posts_category` (`category`),
	KEY `idx_posts_status` (`status`),
	KEY `idx_posts_report_status` (`report_status`),
	KEY `idx_posts_report_closed_at` (`report_closed_at`),
	KEY `idx_posts_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `post_likes` (
	`like_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`post_id` INT UNSIGNED NOT NULL,
	`actor_role` VARCHAR(50) NOT NULL,
	`actor_id` INT UNSIGNED NOT NULL,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`like_id`),
	UNIQUE KEY `uq_post_actor` (`post_id`, `actor_role`, `actor_id`),
	KEY `idx_post_likes_post` (`post_id`),
	KEY `idx_post_likes_actor` (`actor_role`, `actor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `post_comments` (
	`comment_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`post_id` INT UNSIGNED NOT NULL,
	`parent_comment_id` BIGINT UNSIGNED DEFAULT NULL,
	`actor_role` VARCHAR(50) NOT NULL,
	`actor_id` INT UNSIGNED NOT NULL,
	`comment_text` TEXT NOT NULL,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`comment_id`),
	KEY `idx_post_comments_post` (`post_id`),
	KEY `idx_post_comments_parent` (`parent_comment_id`),
	KEY `idx_post_comments_actor` (`actor_role`, `actor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `post_reports` (
	`report_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`post_id` INT UNSIGNED NOT NULL,
	`post_author_role` VARCHAR(50) DEFAULT NULL,
	`post_author_id` INT UNSIGNED DEFAULT NULL,
	`post_author_name` VARCHAR(255) DEFAULT NULL,
	`reporter_role` VARCHAR(50) NOT NULL,
	`reporter_id` INT UNSIGNED NOT NULL,
	`reporter_name` VARCHAR(255) NOT NULL,
	`report_category` VARCHAR(80) NOT NULL,
	`report_details` TEXT DEFAULT NULL,
	`status` VARCHAR(30) NOT NULL DEFAULT 'pending',
	`admin_action_note` VARCHAR(255) DEFAULT NULL,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`actioned_at` DATETIME DEFAULT NULL,
	PRIMARY KEY (`report_id`),
	KEY `idx_post_reports_post` (`post_id`),
	KEY `idx_post_reports_reporter` (`reporter_role`, `reporter_id`),
	KEY `idx_post_reports_status` (`status`),
	KEY `idx_post_reports_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `comment_reports` (
	`report_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`comment_id` BIGINT UNSIGNED NOT NULL,
	`post_id` INT UNSIGNED NOT NULL,
	`comment_author_role` VARCHAR(50) DEFAULT NULL,
	`comment_author_id` INT UNSIGNED DEFAULT NULL,
	`comment_author_name` VARCHAR(255) DEFAULT NULL,
	`comment_text` TEXT DEFAULT NULL,
	`reporter_role` VARCHAR(50) NOT NULL,
	`reporter_id` INT UNSIGNED NOT NULL,
	`reporter_name` VARCHAR(255) NOT NULL,
	`report_category` VARCHAR(80) NOT NULL,
	`report_details` TEXT DEFAULT NULL,
	`status` VARCHAR(30) NOT NULL DEFAULT 'pending',
	`admin_action_note` VARCHAR(255) DEFAULT NULL,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`actioned_at` DATETIME DEFAULT NULL,
	PRIMARY KEY (`report_id`),
	KEY `idx_comment_reports_comment` (`comment_id`),
	KEY `idx_comment_reports_post` (`post_id`),
	KEY `idx_comment_reports_status` (`status`),
	KEY `idx_comment_reports_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `camera_cctv_feeds` (
	`feed_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`camera_id` INT UNSIGNED NOT NULL,
	`feed_label` VARCHAR(150) NOT NULL,
	`feed_type` VARCHAR(20) NOT NULL DEFAULT 'live',
	`stream_scope` VARCHAR(20) NOT NULL DEFAULT 'private',
	`live_url` VARCHAR(1200) DEFAULT NULL,
	`video_path` VARCHAR(600) DEFAULT NULL,
	`camera_location` VARCHAR(255) DEFAULT NULL,
	`streaming_hours` VARCHAR(80) NOT NULL DEFAULT 'continuous',
	`allow_ai_detection` TINYINT(1) NOT NULL DEFAULT 0,
	`allow_public_viewing` TINYINT(1) NOT NULL DEFAULT 0,
	`ai_alerts_to_volunteers` TINYINT(1) NOT NULL DEFAULT 0,
	`accumulated_seconds` BIGINT UNSIGNED NOT NULL DEFAULT 0,
	`active_started_at` DATETIME DEFAULT NULL,
	`is_active` TINYINT(1) NOT NULL DEFAULT 1,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`feed_id`),
	KEY `idx_camera_active` (`camera_id`, `is_active`),
	KEY `idx_camera_created` (`camera_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `missing_person_reports` (
	`report_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`reporter_user_id` INT UNSIGNED DEFAULT NULL,
	`full_name` VARCHAR(150) NOT NULL,
	`nickname` VARCHAR(150) DEFAULT NULL,
	`gender` VARCHAR(20) NOT NULL,
	`age` INT UNSIGNED NOT NULL,
	`physical_description` VARCHAR(500) DEFAULT NULL,
	`photo_filename` VARCHAR(255) NOT NULL,
	`last_seen_date` DATE NOT NULL,
	`last_seen_location` VARCHAR(255) NOT NULL,
	`last_seen_time` VARCHAR(60) DEFAULT NULL,
	`mental_condition` VARCHAR(120) DEFAULT NULL,
	`medical_notes` VARCHAR(500) DEFAULT NULL,
	`reporter_name` VARCHAR(150) NOT NULL,
	`reporter_mobile` VARCHAR(30) NOT NULL,
	`relationship` VARCHAR(120) DEFAULT NULL,
	`consent` TINYINT(1) NOT NULL DEFAULT 1,
	`status` VARCHAR(40) NOT NULL DEFAULT 'open',
	`resolved_at` DATETIME DEFAULT NULL,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`report_id`),
	KEY `idx_missing_status` (`status`),
	KEY `idx_missing_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_notifications` (
	`notification_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`recipient_entity` VARCHAR(60) NOT NULL,
	`recipient_id` INT UNSIGNED NOT NULL,
	`title` VARCHAR(190) NOT NULL,
	`message` TEXT NOT NULL,
	`meta_json` TEXT DEFAULT NULL,
	`level` VARCHAR(30) NOT NULL DEFAULT 'info',
	`is_read` TINYINT(1) NOT NULL DEFAULT 0,
	`target_post_id` INT UNSIGNED DEFAULT NULL,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`notification_id`),
	KEY `idx_recipient` (`recipient_entity`, `recipient_id`),
	KEY `idx_notification_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `volunteer_missions` (
	`mission_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`volunteer_id` INT UNSIGNED NOT NULL,
	`mission_title` VARCHAR(190) NOT NULL,
	`mission_details` TEXT DEFAULT NULL,
	`mission_location` VARCHAR(255) DEFAULT NULL,
	`status` VARCHAR(30) NOT NULL DEFAULT 'assigned',
	`response_status` VARCHAR(30) NOT NULL DEFAULT 'pending',
	`case_ref` VARCHAR(80) DEFAULT NULL,
	`source_notification_id` INT UNSIGNED DEFAULT NULL,
	`proof_file` VARCHAR(255) DEFAULT NULL,
	`proof_submitted_at` DATETIME DEFAULT NULL,
	`completed_at` DATETIME DEFAULT NULL,
	`assigned_by` VARCHAR(100) NOT NULL DEFAULT 'admin',
	`assigned_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`mission_id`),
	KEY `idx_vm_volunteer` (`volunteer_id`),
	KEY `idx_vm_status` (`status`),
	KEY `idx_vm_response` (`response_status`),
	KEY `idx_vm_source_notification` (`source_notification_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `signup_blacklist` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`entity` VARCHAR(60) NOT NULL,
	`email` VARCHAR(255) DEFAULT NULL,
	`mobile` VARCHAR(30) DEFAULT NULL,
	`nid_number` VARCHAR(100) DEFAULT NULL,
	`reason` VARCHAR(255) DEFAULT NULL,
	`blocked_by` VARCHAR(100) DEFAULT 'admin',
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	KEY `idx_blacklist_email` (`email`),
	KEY `idx_blacklist_mobile` (`mobile`),
	KEY `idx_blacklist_nid` (`nid_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `auth_users` (
	`auth_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`email` VARCHAR(255) DEFAULT NULL,
	`full_name` VARCHAR(100) DEFAULT NULL,
	`provider` ENUM('local','google','facebook') NOT NULL DEFAULT 'local',
	`role` ENUM('user','policeman','volunteer','camera_contributor') NOT NULL DEFAULT 'user',
	`provider_id` VARCHAR(255) DEFAULT NULL,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`auth_id`),
	UNIQUE KEY `uq_auth_email` (`email`),
	KEY `idx_auth_provider_id` (`provider_id`),
	KEY `idx_auth_provider` (`provider`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `donations` (
	`donation_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`donor_name` VARCHAR(150) DEFAULT NULL,
	`amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
	`date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`anonymous` TINYINT(1) NOT NULL DEFAULT 0,
	`message` TEXT DEFAULT NULL,
	PRIMARY KEY (`donation_id`),
	KEY `idx_donations_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `withdraw_requests` (
	`withdraw_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`requester_name` VARCHAR(150) NOT NULL,
	`amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
	`status` VARCHAR(30) NOT NULL DEFAULT 'pending',
	`request_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`request_note` VARCHAR(120) DEFAULT NULL,
	`updated_at` DATETIME DEFAULT NULL,
	PRIMARY KEY (`withdraw_id`),
	KEY `idx_withdraw_status` (`status`),
	KEY `idx_withdraw_request_date` (`request_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `traffic_logs` (
	`traffic_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`referrer` VARCHAR(255) DEFAULT NULL,
	`source` VARCHAR(255) DEFAULT NULL,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`traffic_id`),
	KEY `idx_traffic_created` (`created_at`),
	KEY `idx_traffic_referrer` (`referrer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
