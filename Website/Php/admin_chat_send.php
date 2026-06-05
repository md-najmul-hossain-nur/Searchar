<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/admin_chat_common.php';

try {
    adminChatEnsureTable($pdo);
    $raw = file_get_contents('php://input') ?: '';
    $payload = json_decode($raw, true);
    if (!is_array($payload)) {
        $payload = $_POST;
    }

    $text = adminChatCleanMessage((string)($payload['message'] ?? ''));
    $senderRole = adminChatCurrentRole();
    if ($senderRole === '' && adminChatIsAdminRequest()) {
        $senderRole = 'admin';
    }
    $senderId = adminChatCurrentUserId();

    if ($senderRole === 'admin') {
        $participantRole = adminChatNormalizeRole((string)($payload['participant_role'] ?? ''));
        $participantId = (int)($payload['participant_id'] ?? 0);
        if (!in_array($participantRole, ['volunteer', 'police'], true) || $participantId <= 0) {
            adminChatJson(['success' => false, 'error' => 'Participant missing'], 422);
        }
        $readByAdmin = 1;
        $readByParticipant = 0;
        $senderId = 0;
    } else {
        [$participantRole, $participantId] = adminChatRequireParticipant();
        $readByAdmin = 0;
        $readByParticipant = 1;
    }

    $stmt = $pdo->prepare("INSERT INTO admin_chat_messages
        (participant_role, participant_id, sender_role, sender_id, message_text, is_read_by_admin, is_read_by_participant)
        VALUES (:participant_role, :participant_id, :sender_role, :sender_id, :message_text, :read_admin, :read_participant)");
    $stmt->execute([
        ':participant_role' => $participantRole,
        ':participant_id' => $participantId,
        ':sender_role' => $senderRole,
        ':sender_id' => max(0, $senderId),
        ':message_text' => $text,
        ':read_admin' => $readByAdmin,
        ':read_participant' => $readByParticipant,
    ]);

    adminChatJson([
        'success' => true,
        'message' => [
            'message_id' => (int)$pdo->lastInsertId(),
            'sender_role' => $senderRole,
            'sender_id' => max(0, $senderId),
            'message_text' => $text,
            'created_at' => date('Y-m-d H:i:s'),
            'is_mine' => true,
        ],
    ]);
} catch (Throwable $e) {
    error_log('admin_chat_send error: ' . $e->getMessage());
    adminChatJson(['success' => false, 'error' => 'Failed to send message'], 500);
}
