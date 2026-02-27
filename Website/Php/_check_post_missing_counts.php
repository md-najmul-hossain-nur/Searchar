<?php
require __DIR__ . '/db.php';

echo 'posts=' . $pdo->query('SELECT COUNT(*) FROM posts')->fetchColumn() . PHP_EOL;
echo 'missing_person_reports=' . $pdo->query('SELECT COUNT(*) FROM missing_person_reports')->fetchColumn() . PHP_EOL;
