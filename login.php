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

        // ✅ Redirect to feed.php after login
        header("Location: feed.php");
        exit();
    } else {
        $message = "❌ Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #ffe6f2; /* light pink */
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-box {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            width: 350px;
            text-align: center;
        }

        h2 {
            margin-bottom: 20px;
            color: #d63384; /* pink heading */
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            text-align: left;
            color: #555;
        }

        input {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 14px;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #d63384;
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #b82e70;
        }

        p {
            margin-top: 10px;
            font-size: 14px;
        }

        .message {
            margin: 15px 0;
            padding: 10px;
            border-radius: 6px;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
        }

        a {
            display: inline-block;
            margin-top: 10px;
            color: #d63384;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Login</h2>
        <form method="POST">
            <label>Email:</label>
            <input type="email" name="email" required>
            
            <label>Password:</label>
            <input type="password" name="password" required>
            
            <button type="submit">Login</button>
        </form>

        <!-- Show error message if login fails -->
        <?php if ($message): ?>
            <p class="message error"><?php echo $message; ?></p>
        <?php endif; ?>
    </div>
</body>
</html>
