

<?php
session_start();
include "db.php"; // Make sure db.php exists and connects to DB

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Please login first.");
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST['old_password'];
    $new = $_POST['new_password'];

    // Get current password
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $current = $stmt->get_result()->fetch_assoc()['password'];

    // Verify old password
    if (!password_verify($old, $current)) die("Old password incorrect");

    // Check last 3 passwords
    $res = $conn->query("SELECT old_password FROM password_history WHERE user_id=$userId ORDER BY changed_at DESC LIMIT 3");
    while ($row = $res->fetch_assoc()) {
        if (password_verify($new, $row['old_password'])) die("New password cannot match last 3 passwords");
    }

    // Update password
    $newHash = password_hash($new, PASSWORD_BCRYPT);
    $conn->query("UPDATE users SET password='$newHash' WHERE id=$userId");
    $conn->query("INSERT INTO password_history (user_id, old_password) VALUES ($userId, '$newHash')");
    echo "✅ Password changed successfully";
}
?>

<form method="POST">
    Old Password: <input type="password" name="old_password" required><br><br>
    New Password: <input type="password" name="new_password" required><br><br>
    <button type="submit">Change Password</button>
</form>
