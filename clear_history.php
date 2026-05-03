<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require 'db.php';

if (!isset($_SESSION['username'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM search_history WHERE username = ?");
    $stmt->execute([$_SESSION['username']]);
    echo json_encode(['success' => true]);
} catch(PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>