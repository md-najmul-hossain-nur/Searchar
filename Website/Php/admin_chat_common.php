<?php
declare(strict_types=1);

function adminChatJson(array $payload, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

function adminChatEnsureTable(PDO $pdo): void {
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

function adminChatNormalizeRole(string $role): string {
    $role = strtolower(trim($role));
    if ($role === 'policeman' || $role === 'policemen') {
        return 'police';
    }
    if ($role === 'volunteers') {
        return 'volunteer';
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

    $referer = (string)($_SERVER['HTTP_REFERER'] ?? '');
    $path = strtolower((string)parse_url($referer, PHP_URL_PATH));
    return $path !== '' && basename($path) === 'admin.html';
}

function adminChatRequireParticipant(): array {
    $role = adminChatCurrentRole();
    $userId = adminChatCurrentUserId();
    if (!in_array($role, ['volunteer', 'police'], true) || $userId <= 0) {
        adminChatJson(['success' => false, 'error' => 'Only volunteer or police accounts can use this chat'], 403);
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
    $folder = $role === 'police' ? 'police' : 'volunteer';
    return '../uploads/' . $folder . '/' . rawurlencode($file);
}

function adminChatParticipantLabel(string $role): string {
    return $role === 'police' ? 'Police' : 'Volunteer';
}
