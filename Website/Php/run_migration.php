<?php
// Simple migration script to create database and posts table.
// Run from CLI: php run_migration.php

declare(strict_types=1);

$host = 'localhost';
$db   = 'searchar';
$user = 'root';
$pass = '';

try {
    // Connect to MySQL server (no default database)
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    echo "Database `{$db}` ensured.\n";

    // Use the database
    $pdo->exec("USE `{$db}`;");

    // Create posts table
    $create = <<<'SQL'
CREATE TABLE IF NOT EXISTS `posts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `case_id` INT NOT NULL DEFAULT 1,
  `author_role` VARCHAR(50) NOT NULL,
  `author_id` INT NOT NULL,
  `author_name` VARCHAR(255) DEFAULT NULL,
  `category` VARCHAR(50) DEFAULT 'general',
  `text` TEXT,
  `media_path` VARCHAR(512) DEFAULT NULL,
  `media_type` ENUM('image','video','file') DEFAULT NULL,
  `share_facebook` TINYINT(1) NOT NULL DEFAULT 0,
    `share_anonymous` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_case` (`case_id`),
  INDEX `idx_author` (`author_role`, `author_id`),
  INDEX `idx_category` (`category`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

    $pdo->exec($create);
    echo "Table `posts` created/updated.\n";

    echo "Migration completed successfully.\n";

} catch (PDOException $e) {
    fwrite(STDERR, "Migration failed: " . $e->getMessage() . "\n");
    exit(1);
}

?>
