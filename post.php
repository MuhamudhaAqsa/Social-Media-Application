

<?php
session_start();
include "db.php"; // Make sure db.php exists and connects to DB

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Please login first to post messages.");
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content']);
    $isPublic = isset($_POST['is_public']) ? 1 : 0;

    $stmt = $conn->prepare("INSERT INTO posts (user_id, content, is_public) VALUES (?, ?, ?)");
    $stmt->bind_param("isi", $userId, $content, $isPublic);

    if ($stmt->execute()) {
        echo "✅ Post added successfully!";
    } else {
        echo "❌ Error: " . $stmt->error;
    }
}
?>

<h2>Add a Post</h2>
<form method="POST">
    <label>Content:</label><br>
    <textarea name="content" required></textarea><br><br>
    
    <label>Public:</label>
    <input type="checkbox" name="is_public" checked><br><br>
    
    <button type="submit">Post</button>
</form>
