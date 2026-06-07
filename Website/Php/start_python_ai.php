<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

$batPath = realpath(__DIR__ . '/../../start_ai.bat');
if ($batPath && file_exists($batPath)) {
    pclose(popen("start /B \"\" \"cmd\" /c \"$batPath\" > NUL 2> NUL", "r"));
    echo json_encode(['success' => true, 'message' => 'AI Engine started']);
} else {
    echo json_encode(['success' => false, 'error' => 'Batch script not found']);
}
?>
