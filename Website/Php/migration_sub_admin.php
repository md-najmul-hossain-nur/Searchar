<?php
require_once __DIR__ . '/db.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `admins` (
            `admin_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `full_name` VARCHAR(150) NOT NULL,
            `email` VARCHAR(255) NOT NULL,
            `mobile` VARCHAR(30) NOT NULL,
            `password_hash` VARCHAR(255) NOT NULL,
            `role` ENUM('main_admin', 'sub_admin') NOT NULL DEFAULT 'sub_admin',
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`admin_id`),
            UNIQUE KEY `uq_admins_email` (`email`),
            UNIQUE KEY `uq_admins_mobile` (`mobile`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Created admins table.\n";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `admin_logs` (
            `log_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `admin_id` INT UNSIGNED NOT NULL,
            `action_type` VARCHAR(100) NOT NULL,
            `details` TEXT,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`log_id`),
            KEY `idx_admin_logs_admin_id` (`admin_id`),
            FOREIGN KEY (`admin_id`) REFERENCES `admins`(`admin_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Created admin_logs table.\n";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `password_resets` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `email` VARCHAR(255) NOT NULL,
            `code` VARCHAR(10) NOT NULL,
            `expires_at` DATETIME NOT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_password_resets_email` (`email`),
            KEY `idx_password_resets_code` (`code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Created password_resets table.\n";

    // Insert default main admin
    $adminEmail = 'mnajmulhossainnur@gmail.com';
    $adminPhone = '01743094595';
    $adminPassword = password_hash('12345678', PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `admins` WHERE email = ?");
    $stmt->execute([$adminEmail]);
    if ($stmt->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO `admins` (full_name, email, mobile, password_hash, role) VALUES (?, ?, ?, ?, 'main_admin')");
        $stmt->execute(['Main Admin', $adminEmail, $adminPhone, $adminPassword]);
        echo "Inserted main admin.\n";
    } else {
        echo "Main admin already exists.\n";
    }

    echo "Migration completed successfully.\n";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
