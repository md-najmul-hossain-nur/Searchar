<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_notifications (
        notification_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        recipient_entity VARCHAR(60) NOT NULL,
        recipient_id INT UNSIGNED NOT NULL,
        title VARCHAR(190) NOT NULL,
        message TEXT NOT NULL,
        level VARCHAR(30) NOT NULL DEFAULT 'info',
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        target_post_id INT UNSIGNED DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (notification_id),
        INDEX idx_recipient (recipient_entity, recipient_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $rows = [
        [
            'title' => 'New comment on your post',
            'message' => 'Rafid commented: "Great update, keep it up!"',
            'level' => 'info',
            'is_read' => 0,
            'target_post_id' => 1,
            'created_offset' => 'INTERVAL 2 MINUTE',
        ],
        [
            'title' => 'Someone liked your post',
            'message' => 'Nusrat liked your latest mission post.',
            'level' => 'info',
            'is_read' => 0,
            'target_post_id' => 1,
            'created_offset' => 'INTERVAL 5 MINUTE',
        ],
        [
            'title' => 'Admin Warning',
            'message' => 'Admin warning: Please complete your donation pledge for rescue support.',
            'level' => 'warning',
            'is_read' => 0,
            'target_post_id' => null,
            'created_offset' => 'INTERVAL 12 MINUTE',
        ],
        [
            'title' => 'Mission Update',
            'message' => 'Good news: Missing person has been found safely in Dhanmondi.',
            'level' => 'info',
            'is_read' => 0,
            'target_post_id' => null,
            'created_offset' => 'INTERVAL 25 MINUTE',
        ],
        [
            'title' => 'New comment on your post',
            'message' => 'Tanvir commented: "I can volunteer for this mission."',
            'level' => 'info',
            'is_read' => 0,
            'target_post_id' => 1,
            'created_offset' => 'INTERVAL 50 MINUTE',
        ],
    ];

    $hasTarget = $pdo->query("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_notifications' AND COLUMN_NAME = 'target_post_id' LIMIT 1")->fetchColumn();
    if (!$hasTarget) {
        $pdo->exec("ALTER TABLE user_notifications ADD COLUMN target_post_id INT UNSIGNED DEFAULT NULL AFTER is_read");
    }

    foreach ($rows as $row) {
        $sql = "INSERT INTO user_notifications
            (recipient_entity, recipient_id, title, message, level, is_read, target_post_id, created_at)
            VALUES ('broadcast', 0, :title, :message, :level, :is_read, :target_post_id, DATE_SUB(NOW(), {$row['created_offset']}))";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':title' => $row['title'],
            ':message' => $row['message'],
            ':level' => $row['level'],
            ':is_read' => $row['is_read'],
            ':target_post_id' => $row['target_post_id'],
        ]);
    }

    echo "Inserted 5 demo notifications successfully.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Failed to insert demo notifications: ' . $e->getMessage() . "\n";
}
