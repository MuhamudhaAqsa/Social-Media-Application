

<?php
// Show all PHP errors (for debugging)
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include "db.php"; // Make sure this file exists in the same folder

$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Prepare SQL statement
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result && password_verify($password, $result['password'])) {
        // Store user session
        $_SESSION['user_id'] = $result['id'];
        $message = "✅ Welcome! You are logged in.";
    } else {
        $message = "❌ Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>
    <h2>Login</h2>
    <form method="POST">
        <label>Email:</label>
        <input type="email" name="email" required><br><br>
        
        <label>Password:</label>
        <input type="password" name="password" required><br><br>
        
        <button type="submit">Login</button>
    </form>

    <!-- Show login message -->
    <p><?php echo $message; ?></p>

    <!-- Show welcome and logout link if logged in -->
    <?php if (isset($_SESSION['user_id'])): ?>
        <h3>Welcome, User ID: <?php echo $_SESSION['user_id']; ?></h3>
        <a href="logout.php">Logout</a>
    <?php endif; ?>
</body>
</html>
