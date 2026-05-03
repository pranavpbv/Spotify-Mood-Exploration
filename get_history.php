<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require 'db.php';

if (!isset($_SESSION['username'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$username = $_SESSION['username'];

try {
    // Get last 20 unique searches, newest first
    $stmt = $pdo->prepare("
        SELECT track_name, artist_name, genre,
               MAX(searched_at) as searched_at,
               COUNT(*) as search_count
        FROM search_history
        WHERE username = ?
        GROUP BY track_name, artist_name, genre
        ORDER BY searched_at DESC
        LIMIT 20
    ");
    $stmt->execute([$username]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format date
    foreach ($results as &$row) {
        $row['searched_at'] = date('M d, Y g:i A', strtotime($row['searched_at']));
    }

    echo json_encode($results);
} catch(PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>