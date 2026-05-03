<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require 'db.php';

if (!isset($_SESSION['username'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$username    = $_SESSION['username'];
$track_name  = $data['track_name']  ?? '';
$artist_name = $data['artist_name'] ?? '';
$genre       = $data['genre']       ?? '';

if (!$track_name) {
    echo json_encode(['error' => 'No track provided']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO search_history (username, track_name, artist_name, genre)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$username, $track_name, $artist_name, $genre]);
    echo json_encode(['success' => true]);
} catch(PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>