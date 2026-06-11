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
        $img = !empty($case['photo_filename']) ? '../uploads/missing_person/' . htmlspecialchars($case['photo_filename']) : '../Images/index_demo/download (13).jpg';
        $title = 'Case Solved: ' . htmlspecialchars($case['full_name']);
        
        $location = !empty($case['last_seen_location']) ? htmlspecialchars($case['last_seen_location']) : 'their last known location';
        
        $templates = [
            "Successfully located after being reported missing from %s.",
            "Found safe and reunited with family after going missing near %s.",
            "Our coordinated efforts led to a successful recovery near %s.",
            "Case resolved! Located safe after the initial report from %s.",
            "Thanks to community alerts, safely found after disappearing near %s."
        ];
        
        $reportId = (int)$case['report_id'];
        $template = $templates[$reportId % count($templates)];
        $desc = sprintf($template, $location);
        
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
            'img' => '../Images/index_demo/download (18).jpg',
            'title' => 'Rapid Response Milestone',
            'desc' => 'Average emergency verification time reduced by 41% through coordinated alerts.',
            'donated' => 64,
            'amount' => '41% faster response'
        ],
        [
            'img' => '../Images/index_demo/download (21).jpg',
            'title' => 'Community Patrol Success',
            'desc' => 'Cross-zone volunteer coverage solved 9 high-priority incidents this month.',
            'donated' => 58,
            'amount' => '9 cases this month'
        ],
        [
            'img' => '../Images/index_demo/download (3).jpg',
            'title' => 'Case #B-227 Reunited',
            'desc' => 'Anonymous CCTV evidence helped investigators close the case in 2 days.',
            'donated' => 83,
            'amount' => 'Closed in 48 hours'
        ],
        [
            'img' => '../Images/index_demo/download (4).jpg',
            'title' => 'Volunteer Achievement Badge',
            'desc' => 'Top field team received achievement badges after completing 120 verified actions.',
            'donated' => 76,
            'amount' => '120 verified actions'
        ],
        [
            'img' => '../Images/index_demo/download (5).jpg',
            'title' => 'Camera Network Impact',
            'desc' => 'New camera contributors increased active evidence coverage in critical zones.',
            'donated' => 69,
            'amount' => 'Coverage up by 33%'
        ],
        [
            'img' => '../Images/index_demo/download (8).jpg',
            'title' => 'Missing Child Found',
            'desc' => 'Quick response team successfully located the missing child within hours of the alert.',
            'donated' => 100,
            'amount' => 'Found in 3 hours'
        ],
        [
            'img' => '../Images/index_demo/download (9).jpg',
            'title' => 'Elderly Support Success',
            'desc' => 'Community effort helped guide a lost senior citizen back to their family.',
            'donated' => 95,
            'amount' => 'Reunited safely'
        ],
        [
            'img' => '../Images/index_demo/images (2).jpg',
            'title' => 'Live Tracking Efficiency',
            'desc' => 'Live updates from our volunteer network narrowed down search areas effectively.',
            'donated' => 78,
            'amount' => 'Faster tracking'
        ],
        [
            'img' => '../Images/index_demo/images (5).jpg',
            'title' => 'Neighborhood Watch',
            'desc' => 'Local watch groups prevented multiple incidents by utilizing early warning alerts.',
            'donated' => 88,
            'amount' => 'Prevention success'
        ],
        [
            'img' => '../Images/index_demo/images (6).jpg',
            'title' => 'Database Integration',
            'desc' => 'New facial recognition models sped up matching times against our reported databases.',
            'donated' => 60,
            'amount' => 'Processing time halved'
        ],
        [
            'img' => '../Images/index_demo/images (7).jpg',
            'title' => 'Public Awareness Campaign',
            'desc' => 'Massive outreach programs educated citizens on what to do when someone goes missing.',
            'donated' => 100,
            'amount' => 'Over 10k reached'
        ],
        [
            'img' => '../Images/index_demo/download (13).jpg',
            'title' => 'Cross-City Alliance',
            'desc' => 'Partnering with neighboring city networks allowed us to track movements seamlessly.',
            'donated' => 72,
            'amount' => 'Wider coverage area'
        ]
    ];

    // Append all demo achievements so the carousel is always full of content
    foreach ($demoAchievements as $demo) {
        $achievements[] = $demo;
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
