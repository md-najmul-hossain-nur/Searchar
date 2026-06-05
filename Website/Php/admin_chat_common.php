<?php
declare(strict_types=1);

function adminChatJson(array $payload, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

function adminChatTableExists(PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table LIMIT 1');
    $stmt->execute([':table' => $table]);
    return (bool)$stmt->fetchColumn();
}

function adminChatColumnExists(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare('SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column LIMIT 1');
    $stmt->execute([':table' => $table, ':column' => $column]);
    return (bool)$stmt->fetchColumn();
}

function adminChatEnsureConversationTables(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS conversations (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id INT UNSIGNED NOT NULL,
        role VARCHAR(80) NOT NULL,
        last_message_at TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_conversation_user_role (user_id, role),
        KEY idx_conversations_user_role (user_id, role),
        KEY idx_conversations_last_message (last_message_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        conversation_id BIGINT UNSIGNED NOT NULL,
        sender_role VARCHAR(80) NOT NULL,
        sender_id INT UNSIGNED NOT NULL,
        receiver_role VARCHAR(80) NOT NULL DEFAULT 'admin',
        receiver_id INT UNSIGNED NOT NULL DEFAULT 0,
        message TEXT DEFAULT NULL,
        content TEXT DEFAULT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_messages_conversation (conversation_id),
        KEY idx_messages_sender (sender_id),
        KEY idx_messages_receiver (receiver_role, receiver_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function adminChatEnsureLegacyTable(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_chat_messages (
        message_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        participant_role VARCHAR(20) NOT NULL,
        participant_id INT UNSIGNED NOT NULL,
        sender_role VARCHAR(20) NOT NULL,
        sender_id INT UNSIGNED NOT NULL DEFAULT 0,
        message_text TEXT NOT NULL,
        is_read_by_admin TINYINT(1) NOT NULL DEFAULT 0,
        is_read_by_participant TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (message_id),
        INDEX idx_admin_chat_participant (participant_role, participant_id, message_id),
        INDEX idx_admin_chat_created (created_at),
        INDEX idx_admin_chat_admin_unread (is_read_by_admin, participant_role, participant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function adminChatStorage(PDO $pdo): string {
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    if (adminChatTableExists($pdo, 'messages')) {
        if (adminChatColumnExists($pdo, 'messages', 'conversation_id')) {
            adminChatEnsureConversationTables($pdo);
            return $cached = 'messages_conversation';
        }
        return $cached = 'messages_legacy';
    }

    if (adminChatTableExists($pdo, 'admin_chat_messages')) {
        return $cached = 'admin_chat_messages';
    }

    adminChatEnsureLegacyTable($pdo);
    return $cached = 'admin_chat_messages';
}

function adminChatEnsureTable(PDO $pdo): void {
    adminChatStorage($pdo);
}

function adminChatGetConversationId(PDO $pdo, string $role, int $userId, bool $createIfMissing = true): int {
    $stmt = $pdo->prepare('SELECT id FROM conversations WHERE user_id = :id AND role = :role LIMIT 1');
    $stmt->execute([':id' => $userId, ':role' => $role]);
    $id = (int)($stmt->fetchColumn() ?: 0);
    if ($id > 0 || !$createIfMissing) {
        return $id;
    }

    $insert = $pdo->prepare('INSERT INTO conversations (user_id, role, last_message_at) VALUES (:id, :role, NOW())');
    $insert->execute([':id' => $userId, ':role' => $role]);
    return (int)$pdo->lastInsertId();
}

function adminChatNormalizeRole(string $role): string {
    $role = strtolower(trim($role));
    if ($role === 'policeman' || $role === 'policemen') {
        return 'police';
    }
    if ($role === 'volunteers') {
        return 'volunteer';
    }
    if ($role === 'camera' || $role === 'camera_contributor' || $role === 'camera_contributors') {
        return 'contributor';
    }
    return $role;
}

function adminChatCurrentRole(): string {
    return adminChatNormalizeRole((string)($_SESSION['role'] ?? ''));
}

function adminChatCurrentUserId(): int {
    return (int)($_SESSION['user_id'] ?? 0);
}

function adminChatIsAdminRequest(): bool {
    if (adminChatCurrentRole() === 'admin') {
        return true;
    }

    $explicit = (string)($_GET['as_admin'] ?? $_POST['as_admin'] ?? $_SERVER['HTTP_X_ADMIN_REQUEST'] ?? '');
    if ($explicit === '1' || strtolower($explicit) === 'true') {
        return true;
    }

    $referer = (string)($_SERVER['HTTP_REFERER'] ?? '');
    $path = strtolower((string)parse_url($referer, PHP_URL_PATH));
    return $path !== '' && basename($path) === 'admin.html';
}

function adminChatRequireParticipant(): array {
    $role = adminChatCurrentRole();
    $userId = adminChatCurrentUserId();
    if (!in_array($role, ['volunteer', 'police', 'contributor'], true) || $userId <= 0) {
        adminChatJson(['success' => false, 'error' => 'Only volunteer, police, or contributor accounts can use this chat'], 403);
    }
    return [$role, $userId];
}

function adminChatRequireAdmin(): void {
    if (!adminChatIsAdminRequest()) {
        adminChatJson(['success' => false, 'error' => 'Admin login required'], 403);
    }
}

function adminChatCleanMessage(string $text): string {
    $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');
    if ($text === '') {
        adminChatJson(['success' => false, 'error' => 'Message is empty'], 422);
    }
    if (strlen($text) > 8000) {
        adminChatJson(['success' => false, 'error' => 'Message is too long'], 422);
    }
    return $text;
}

function adminChatProfilePhoto(?string $file, string $role): string {
    $file = trim((string)$file);
    if ($file === '') {
        return '../Images/default-profile.gif';
    }
    $folderMap = [
        'police' => 'police',
        'volunteer' => 'volunteer',
        'contributor' => 'camera',
    ];
    $folder = $folderMap[$role] ?? 'user';
    return '../uploads/' . $folder . '/' . rawurlencode($file);
}

function adminChatParticipantLabel(string $role): string {
    return match ($role) {
        'police' => 'Police',
        'contributor' => 'Contributor',
        default => 'Volunteer',
    };
}
