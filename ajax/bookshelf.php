<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    echo json_encode(['ok' => false, 'message' => 'Please log in to manage your bookshelf.']);
    exit;
}

$action = $_POST['action'] ?? '';
$book_id = (int) ($_POST['book_id'] ?? 0);
$status = $_POST['status'] ?? 'want_to_read';
$progress = min(100, max(0, (int) ($_POST['progress'] ?? 0)));

try {
    if ($action === 'set_status') {
        if (!in_array($status, ['want_to_read', 'reading', 'completed'], true)) {
            echo json_encode(['ok' => false, 'message' => 'Invalid reading status.']);
            exit;
        }

        if ($status === 'completed') {
            $progress = 100;
        }

        $stmt = $pdo->prepare('
            INSERT INTO user_bookshelf (user_id, book_id, status, progress_pct)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE status = VALUES(status), progress_pct = VALUES(progress_pct)
        ');
        $stmt->execute([$user_id, $book_id, $status, $progress]);

        $status_labels = [
            'want_to_read' => 'Marked as Want to Read 🔖',
            'reading' => 'Moved to Currently Reading 📖',
            'completed' => 'Marked as Finished! 🎉'
        ];

        echo json_encode([
            'ok' => true,
            'message' => $status_labels[$status] ?? 'Bookshelf updated!',
            'status' => $status,
            'progress' => $progress
        ]);
        exit;
    }

    if ($action === 'remove') {
        $stmt = $pdo->prepare('DELETE FROM user_bookshelf WHERE user_id = ? AND book_id = ?');
        $stmt->execute([$user_id, $book_id]);

        echo json_encode(['ok' => true, 'message' => 'Removed from your bookshelf.']);
        exit;
    }

    echo json_encode(['ok' => false, 'message' => 'Unknown action.']);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
