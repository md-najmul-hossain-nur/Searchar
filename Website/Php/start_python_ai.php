<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

$batPath = realpath(__DIR__ . '/../../start_ai.bat');
if ($batPath && file_exists($batPath)) {
    try {
        $WshShell = new COM("WScript.Shell");
        // 0 = hide window, false = don't wait for completion
        $WshShell->Run("cmd /c \"$batPath\"", 0, false);
        echo json_encode(['success' => true, 'message' => 'AI Engine started']);
    } catch (Throwable $e) {
        // Fallback if COM is disabled
        pclose(popen("start /B \"\" \"cmd\" /c \"$batPath\" > NUL 2> NUL", "r"));
        echo json_encode(['success' => true, 'message' => 'AI Engine started via popen']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Batch script not found']);
}
?>
