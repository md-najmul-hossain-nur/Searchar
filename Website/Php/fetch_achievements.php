<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

try {
    // Fetch recently resolved missing person reports
    $stmt = $pdo->prepare("
        SELECT report_id, full_name, photo_filename, last_seen_location, resolved_at 
        FROM missing_person_reports 
        WHERE LOWER(COALESCE(status, 'open')) IN ('resolved', 'found', 'closed', 'completed', 'reunited')
        ORDER BY resolved_at DESC 
        LIMIT 6
    ");
    $stmt->execute();
    $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $achievements = [];

    // Map database cases to achievement format
    foreach ($cases as $case) {
        $img = !empty($case['photo_filename']) ? '../Uploads/' . htmlspecialchars($case['photo_filename']) : '../Images/demo.jpg';
        $title = 'Case Solved: ' . htmlspecialchars($case['full_name']);
        
        $location = !empty($case['last_seen_location']) ? htmlspecialchars($case['last_seen_location']) : 'their last known location';
        $desc = "Successfully located after being reported missing from $location.";
        
        $dateStr = !empty($case['resolved_at']) ? date('M j, Y', strtotime($case['resolved_at'])) : 'Recently';
        $amount = "100% Completed - Resolved $dateStr";

        $achievements[] = [
            'img' => $img,
            'title' => $title,
            'desc' => $desc,
            'donated' => 100, // 100% progress since it's solved
            'amount' => $amount
        ];
    }

    // Add demo entries if not enough real data (fallback)
    $demoAchievements = [
        [
            'img' => '../Images/help.jpg',
            'title' => 'Rapid Response Milestone',
            'desc' => 'Average emergency verification time reduced by 41% through coordinated alerts.',
            'donated' => 64,
            'amount' => '41% faster response'
        ],
        [
            'img' => '../Images/together.jpg',
            'title' => 'Community Patrol Success',
            'desc' => 'Cross-zone volunteer coverage solved 9 high-priority incidents this month.',
            'donated' => 58,
            'amount' => '9 cases this month'
        ],
        [
            'img' => '../Images/missing.jpeg',
            'title' => 'Case #B-227 Reunited',
            'desc' => 'Anonymous CCTV evidence helped investigators close the case in 2 days.',
            'donated' => 83,
            'amount' => 'Closed in 48 hours'
        ],
        [
            'img' => '../Images/demo.jpg',
            'title' => 'Volunteer Achievement Badge',
            'desc' => 'Top field team received achievement badges after completing 120 verified actions.',
            'donated' => 76,
            'amount' => '120 verified actions'
        ],
        [
            'img' => '../Images/help.jpg',
            'title' => 'Camera Network Impact',
            'desc' => 'New camera contributors increased active evidence coverage in critical zones.',
            'donated' => 69,
            'amount' => 'Coverage up by 33%'
        ]
    ];

    // Ensure we have at least 6 items for a good carousel loop
    $demoIndex = 0;
    while (count($achievements) < 6 && $demoIndex < count($demoAchievements)) {
        $achievements[] = $demoAchievements[$demoIndex];
        $demoIndex++;
    }

    echo json_encode([
        'success' => true,
        'data' => $achievements
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch achievements'
    ]);
}
?>
