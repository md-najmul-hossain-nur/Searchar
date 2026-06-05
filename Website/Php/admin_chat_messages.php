<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/admin_chat_common.php';

try {
    adminChatEnsureTable($pdo);
    $storage = adminChatStorage($pdo);
    $currentRole = adminChatCurrentRole();
    $currentId = adminChatCurrentUserId();
    $isAdminRequest = $currentRole === 'admin' || adminChatIsAdminRequest();

    if ($isAdminRequest) {
        $participantRole = adminChatNormalizeRole((string)($_GET['participant_role'] ?? ''));
        $participantId = (int)($_GET['participant_id'] ?? 0);
        if (!in_array($participantRole, ['volunteer', 'police', 'contributor'], true) || $participantId <= 0) {
            adminChatJson(['success' => false, 'error' => 'Participant missing'], 422);
        }
    } else {
        [$participantRole, $participantId] = adminChatRequireParticipant();
    }

    $rows = [];
    if ($storage === 'messages_conversation') {
        $conversationId = adminChatGetConversationId($pdo, $participantRole, $participantId, false);
        if ($conversationId > 0) {
            if ($isAdminRequest) {
                $mark = $pdo->prepare("UPDATE messages
                                       SET is_read = 1
                                       WHERE conversation_id = :cid AND receiver_role = 'admin'");
                $mark->execute([':cid' => $conversationId]);
            } else {
                $mark = $pdo->prepare("UPDATE messages
                                       SET is_read = 1
                                       WHERE conversation_id = :cid AND receiver_role = :role AND receiver_id = :id");
                $mark->execute([':cid' => $conversationId, ':role' => $participantRole, ':id' => $participantId]);
            }

            $stmt = $pdo->prepare("SELECT id AS message_id, sender_role, sender_id, message AS message_text, created_at
                                   FROM messages
                                   WHERE conversation_id = :cid
                                   ORDER BY id ASC
                                   LIMIT 300");
            $stmt->execute([':cid' => $conversationId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } elseif ($storage === 'messages_legacy') {
        if ($isAdminRequest) {
            $mark = $pdo->prepare("UPDATE messages
                                   SET is_read = 1
                                   WHERE receiver_role = 'admin' AND sender_role = :role AND sender_id = :id");
            $mark->execute([':role' => $participantRole, ':id' => $participantId]);
        } else {
            $mark = $pdo->prepare("UPDATE messages
                                   SET is_read = 1
                                   WHERE receiver_role = :role AND receiver_id = :id AND sender_role = 'admin'");
            $mark->execute([':role' => $participantRole, ':id' => $participantId]);
        }

        $stmt = $pdo->prepare("SELECT id AS message_id, sender_role, sender_id, message AS message_text, created_at
                               FROM messages
                               WHERE (sender_role = :role AND sender_id = :id AND receiver_role = 'admin')
                                  OR (sender_role = 'admin' AND receiver_role = :role AND receiver_id = :id)
                               ORDER BY id ASC
                               LIMIT 300");
        $stmt->execute([':role' => $participantRole, ':id' => $participantId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        if ($isAdminRequest) {
            $mark = $pdo->prepare("UPDATE admin_chat_messages
                                   SET is_read_by_admin = 1
                                   WHERE participant_role = :role AND participant_id = :id AND sender_role <> 'admin'");
            $mark->execute([':role' => $participantRole, ':id' => $participantId]);
        } else {
            $mark = $pdo->prepare("UPDATE admin_chat_messages
                                   SET is_read_by_participant = 1
                                   WHERE participant_role = :role AND participant_id = :id AND sender_role = 'admin'");
            $mark->execute([':role' => $participantRole, ':id' => $participantId]);
        }

        $stmt = $pdo->prepare("SELECT message_id, sender_role, sender_id, message_text, created_at
                               FROM admin_chat_messages
                               WHERE participant_role = :role AND participant_id = :id
                               ORDER BY message_id ASC
                               LIMIT 300");
        $stmt->execute([':role' => $participantRole, ':id' => $participantId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $messages = [];
    foreach ($rows as $row) {
        $senderRole = adminChatNormalizeRole((string)$row['sender_role']);
        $messages[] = [
            'message_id' => (int)$row['message_id'],
            'sender_role' => $senderRole,
            'sender_id' => (int)$row['sender_id'],
            'message_text' => (string)$row['message_text'],
            'created_at' => (string)$row['created_at'],
            'is_mine' => $isAdminRequest ? $senderRole === 'admin' : $senderRole === $currentRole && (int)$row['sender_id'] === $currentId,
        ];
    }

    adminChatJson(['success' => true, 'data' => $messages]);
} catch (Throwable $e) {
    error_log('admin_chat_messages error: ' . $e->getMessage());
    adminChatJson(['success' => false, 'error' => 'Failed to load messages'], 500);
}
