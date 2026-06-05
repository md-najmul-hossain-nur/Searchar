<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/admin_chat_common.php';

try {
    adminChatEnsureTable($pdo);
    $currentRole = adminChatCurrentRole();
    $currentId = adminChatCurrentUserId();

    if ($currentRole === 'admin') {
        $participantRole = adminChatNormalizeRole((string)($_GET['participant_role'] ?? ''));
        $participantId = (int)($_GET['participant_id'] ?? 0);
        if (!in_array($participantRole, ['volunteer', 'police'], true) || $participantId <= 0) {
            adminChatJson(['success' => false, 'error' => 'Participant missing'], 422);
        }
        $mark = $pdo->prepare("UPDATE admin_chat_messages
                               SET is_read_by_admin = 1
                               WHERE participant_role = :role AND participant_id = :id AND sender_role <> 'admin'");
        $mark->execute([':role' => $participantRole, ':id' => $participantId]);
    } else {
        [$participantRole, $participantId] = adminChatRequireParticipant();
        $mark = $pdo->prepare("UPDATE admin_chat_messages
                               SET is_read_by_participant = 1
                               WHERE participant_role = :role AND participant_id = :id AND sender_role = 'admin'");
        $mark->execute([':role' => $participantRole, ':id' => $participantId]);
    }

    $stmt = $pdo->prepare("SELECT message_id, participant_role, participant_id, sender_role, sender_id, message_text, created_at
                           FROM admin_chat_messages
                           WHERE participant_role = :role AND participant_id = :id
                           ORDER BY message_id ASC
                           LIMIT 300");
    $stmt->execute([':role' => $participantRole, ':id' => $participantId]);

    $messages = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $senderRole = adminChatNormalizeRole((string)$row['sender_role']);
        $messages[] = [
            'message_id' => (int)$row['message_id'],
            'sender_role' => $senderRole,
            'sender_id' => (int)$row['sender_id'],
            'message_text' => (string)$row['message_text'],
            'created_at' => (string)$row['created_at'],
            'is_mine' => $currentRole === 'admin' ? $senderRole === 'admin' : $senderRole === $currentRole && (int)$row['sender_id'] === $currentId,
        ];
    }

    adminChatJson(['success' => true, 'data' => $messages]);
} catch (Throwable $e) {
    error_log('admin_chat_messages error: ' . $e->getMessage());
    adminChatJson(['success' => false, 'error' => 'Failed to load messages'], 500);
}

