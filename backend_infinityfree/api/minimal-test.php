<?php
// Minimal test - no ternary errors
header('Content-Type: application/json');
echo json_encode(['status' => 'ok', 'message' => 'No syntax errors']);