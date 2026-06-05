<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/admin_chat_common.php';

try {
    adminChatRequireAdmin();
    adminChatEnsureTable($pdo);

    $roleFilter = adminChatNormalizeRole((string)($_GET['role'] ?? 'all'));
    $search = trim((string)($_GET['search'] ?? ''));

    $where = '';
    $params = [];
    if (in_array($roleFilter, ['volunteer', 'police'], true)) {
        $where = 'WHERE latest.participant_role = :role';
        $params[':role'] = $roleFilter;
    }

    $sql = "SELECT latest.participant_role,
                   latest.participant_id,
                   latest.message_id,
                   latest.sender_role,
                   latest.message_text,
                   latest.created_at,
                   COALESCE(v.full_name, p.full_name, CONCAT(UCASE(LEFT(latest.participant_role, 1)), SUBSTRING(latest.participant_role, 2), ' #', latest.participant_id)) AS participant_name,
                   COALESCE(v.profile_photo, p.profile_photo, '') AS profile_photo,
                   (
                     SELECT COUNT(*)
                     FROM admin_chat_messages unread
                     WHERE unread.participant_role = latest.participant_role
                       AND unread.participant_id = latest.participant_id
                       AND unread.sender_role <> 'admin'
                       AND unread.is_read_by_admin = 0
                   ) AS unread_count
            FROM admin_chat_messages latest
            INNER JOIN (
                SELECT participant_role, participant_id, MAX(message_id) AS latest_id
                FROM admin_chat_messages
                GROUP BY participant_role, participant_id
            ) grouped ON grouped.latest_id = latest.message_id
            LEFT JOIN volunteers v ON latest.participant_role = 'volunteer' AND latest.participant_id = v.volunteer_id
            LEFT JOIN policemen p ON latest.participant_role = 'police' AND latest.participant_id = p.police_id
            $where
            ORDER BY latest.message_id DESC
            LIMIT 200";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($rows as $row) {
        $role = adminChatNormalizeRole((string)$row['participant_role']);
        $name = (string)($row['participant_name'] ?? '');
        $haystack = strtolower($name . ' ' . $role . ' ' . (string)$row['message_text']);
        if ($search !== '' && strpos($haystack, strtolower($search)) === false) {
            continue;
        }

        $data[] = [
            'participant_role' => $role,
            'participant_id' => (int)$row['participant_id'],
            'participant_name' => $name,
            'participant_label' => adminChatParticipantLabel($role),
            'profile_photo' => adminChatProfilePhoto((string)($row['profile_photo'] ?? ''), $role),
            'last_message' => (string)$row['message_text'],
            'last_sender_role' => adminChatNormalizeRole((string)$row['sender_role']),
            'last_at' => (string)$row['created_at'],
            'unread_count' => (int)$row['unread_count'],
        ];
    }

    adminChatJson(['success' => true, 'data' => $data]);
} catch (Throwable $e) {
    error_log('admin_chat_conversations error: ' . $e->getMessage());
    adminChatJson(['success' => false, 'error' => 'Failed to load conversations'], 500);
}

