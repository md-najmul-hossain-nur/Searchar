<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/db.php';

function failRedirect(string $reason): void {
    $target = '../Html/User_Home.php?volunteer_switch=' . urlencode($reason);
    header('Location: ' . $target);
    exit;
}

try {
    if (empty($_SESSION['role']) || $_SESSION['role'] !== 'user' || empty($_SESSION['user_id'])) {
        failRedirect('unauthorized');
    }

    $userId = (int)$_SESSION['user_id'];
    if ($userId <= 0) {
        failRedirect('invalid_user');
    }

    $userStmt = $pdo->prepare('SELECT full_name, email, mobile, nid_number FROM users WHERE user_id = :id LIMIT 1');
    $userStmt->execute([':id' => $userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        failRedirect('user_not_found');
    }

    $email = trim((string)($user['email'] ?? ''));
    $mobile = trim((string)($user['mobile'] ?? ''));
    $nidNumber = trim((string)($user['nid_number'] ?? ''));

    if ($email === '' || $mobile === '' || $nidNumber === '') {
        failRedirect('profile_incomplete');
    }

    $volStmt = $pdo->prepare('SELECT volunteer_id, full_name, email FROM volunteers WHERE email = :email OR mobile = :mobile OR nid_number = :nid LIMIT 1');
    $volStmt->execute([
        ':email' => $email,
        ':mobile' => $mobile,
        ':nid' => $nidNumber,
    ]);
    $volunteer = $volStmt->fetch(PDO::FETCH_ASSOC);
    if (!$volunteer) {
        failRedirect('not_approved');
    }

    $_SESSION['role'] = 'volunteer';
    $_SESSION['user_id'] = (int)($volunteer['volunteer_id'] ?? 0);
    $_SESSION['email'] = (string)($volunteer['email'] ?? $email);

    header('Location: ../Html/Volunteer_Home.php?login=success&switched=1');
    exit;
} catch (Throwable $e) {
    failRedirect('error');
}
