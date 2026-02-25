<?php
require __DIR__ . '/Website/Php/db.php';
try {
    $exists = $pdo->query("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' LIMIT 1")->fetchColumn();
    if (!$exists) {
        echo "posts_table_exists=0\n";
        exit;
    }
    $count = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
    echo "posts_table_exists=1\n";
    echo "posts_count={$count}\n";

    $stmt = $pdo->query("SELECT id, author_name, category, created_at FROM posts ORDER BY id DESC LIMIT 5");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "sample_rows=" . json_encode($rows, JSON_UNESCAPED_SLASHES) . "\n";
} catch (Throwable $e) {
    echo "error=" . $e->getMessage() . "\n";
}
