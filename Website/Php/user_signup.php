<?php
require_once "../Php/db.php";

function isValidEmail(string $value): bool {
    return (bool)filter_var($value, FILTER_VALIDATE_EMAIL);
}

function normalizePhone(string $value): string {
    $digits = preg_replace('/\D+/', '', $value);
    return $digits ?? '';
}

function generateUniqueValue(PDO $pdo, string $column, string $candidate, int $maxTries = 20): string {
    for ($i = 0; $i < $maxTries; $i += 1) {
        $value = $candidate . ($i > 0 ? ('_' . $i) : '');
        $stmt = $pdo->prepare("SELECT 1 FROM users WHERE {$column} = ? LIMIT 1");
        $stmt->execute([$value]);
        if (!$stmt->fetchColumn()) {
            return $value;
        }
    }

    throw new Exception('Could not generate unique value for ' . $column);
}

function ensureUsersContactColumnsAllowNull(PDO $pdo): void {
    $stmt = $pdo->prepare("SELECT COLUMN_NAME, IS_NULLABLE
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'users'
          AND COLUMN_NAME IN ('email', 'mobile')");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $nullable = [];
    foreach ($rows as $row) {
        $nullable[$row['COLUMN_NAME']] = strtoupper((string)$row['IS_NULLABLE']) === 'YES';
    }

    if (!($nullable['email'] ?? false)) {
        $pdo->exec("ALTER TABLE users MODIFY email VARCHAR(255) NULL");
    }

    if (!($nullable['mobile'] ?? false)) {
        $pdo->exec("ALTER TABLE users MODIFY mobile VARCHAR(30) NULL");
    }
}

function ensureNotificationsTable(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_notifications (
        notification_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        recipient_entity VARCHAR(60) NOT NULL,
        recipient_id INT UNSIGNED NOT NULL,
        title VARCHAR(190) NOT NULL,
        message TEXT NOT NULL,
        level VARCHAR(30) NOT NULL DEFAULT 'info',
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (notification_id),
        INDEX idx_recipient (recipient_entity, recipient_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

try {
    $fullName = trim((string)($_POST['fullname'] ?? ''));
    $contactInput = trim((string)($_POST['emailOrPhone'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if ($fullName === '' || $contactInput === '' || $password === '' || $confirmPassword === '') {
        throw new Exception('Please fill all required fields.');
    }

    if ($password !== $confirmPassword) {
        throw new Exception('Password and confirm password did not match.');
    }

    if (strlen($password) < 6) {
        throw new Exception('Password must be at least 6 characters long.');
    }

    $email = '';
    $mobile = '';

    if (isValidEmail($contactInput)) {
        $email = strtolower($contactInput);
    } else {
        $phone = normalizePhone($contactInput);
        if ($phone === '') {
            throw new Exception('Enter a valid email or phone number.');
        }
        $mobile = $phone;
    }

    ensureUsersContactColumnsAllowNull($pdo);

    $emailValue = $email !== '' ? $email : null;
    $mobileValue = $mobile !== '' ? $mobile : null;

    $blkExists = $pdo->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'signup_blacklist' LIMIT 1");
    $blkExists->execute();
    if ($blkExists->fetchColumn()) {
        if ($emailValue !== null) {
            $blk = $pdo->prepare("SELECT 1 FROM signup_blacklist WHERE email = ? LIMIT 1");
            $blk->execute([$emailValue]);
        } else {
            $blk = $pdo->prepare("SELECT 1 FROM signup_blacklist WHERE mobile = ? LIMIT 1");
            $blk->execute([$mobileValue]);
        }
        if ($blk->fetch()) {
            throw new Exception('This Email/Mobile has been blocked by admin.');
        }
    }

    if (isDuplicateContact($pdo, $emailValue, $mobileValue)) {
        throw new Exception('Email or mobile already registered. Please sign in.');
    }

    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("INSERT INTO users
        (full_name, email, mobile, profile_photo, cover_photo, date_of_birth, gender, street, city, postal_code, country, latitude, longitude, password_hash)
        VALUES (?, ?, ?, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, ?)");

    $stmt->execute([
        $fullName,
        $emailValue,
        $mobileValue,
        $passwordHash,
    ]);

    $newUserId = (int)$pdo->lastInsertId();

    ensureNotificationsTable($pdo);
    $notify = $pdo->prepare('INSERT INTO user_notifications (recipient_entity, recipient_id, title, message, level) VALUES (:entity, :recipient_id, :title, :message, :level)');
    $notify->execute([
        ':entity' => 'user',
        ':recipient_id' => $newUserId,
        ':title' => 'Admin Reminder',
        ':message' => 'Welcome to SEARCHAR. Please complete your profile (photo, cover, date of birth, gender and address) from Edit Profile.',
        ':level' => 'warning',
    ]);

    echo "<script>
        alert('Signup successful. Please sign in now.');
        window.location.href = '../Html/login.html';
    </script>";
    exit;
} catch (Exception $ex) {
    echo "<script>
        alert('" . addslashes($ex->getMessage()) . "');
        window.history.back();
    </script>";
    exit;
}
?>
