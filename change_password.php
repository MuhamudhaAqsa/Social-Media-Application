<?php
// change_password.php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

session_start();
require_once "db.php"; // expects $conn = new mysqli(...)

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId  = (int) $_SESSION['user_id'];
$message = "";
$ok      = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old     = $_POST['old_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    // Basic validations
    if ($new !== $confirm) {
        $message = "❌ New password and confirm password do not match.";
    } elseif (strlen($new) < 8) {
        $message = "❌ New password must be at least 8 characters.";
    } else {
        // 1) Fetch current password hash
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($currentHash);
        $found = $stmt->fetch();
        $stmt->close();

        if (!$found || empty($currentHash)) {
            $message = "❌ User not found.";
        } elseif (!password_verify($old, $currentHash)) {
            // IMPORTANT: compare typed old password with the HASH in DB
            $message = "❌ Old password is incorrect.";
        } elseif (password_verify($new, $currentHash)) {
            $message = "❌ New password cannot be the same as the current password.";
        } else {
            // 2) Check last 3 password hashes in history
            $hist = $conn->prepare("
                SELECT old_password 
                FROM password_history 
                WHERE user_id = ? 
                ORDER BY changed_at DESC 
                LIMIT 3
            ");
            $hist->bind_param("i", $userId);
            $hist->execute();
            $res = $hist->get_result();

            $reuse = false;
            while ($row = $res->fetch_assoc()) {
                if (password_verify($new, $row['old_password'])) {
                    $reuse = true;
                    break;
                }
            }
            $hist->close();

            if ($reuse) {
                $message = "❌ New password cannot match any of your last 3 passwords.";
            } else {
                // 3) Proceed with change (insert current into history BEFORE update)
                $conn->begin_transaction();
                try {
                    // Insert current (soon-to-be-old) hash into history
                    $ins = $conn->prepare("INSERT INTO password_history (user_id, old_password) VALUES (?, ?)");
                    $ins->bind_param("is", $userId, $currentHash);
                    $ins->execute();
                    $ins->close();

                    // Hash the new password
                    $newHash = password_hash($new, PASSWORD_BCRYPT);

                    // Update users table
                    $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $upd->bind_param("si", $newHash, $userId);
                    $upd->execute();
                    $upd->close();

                    // (Optional) Keep only the last 3 history entries
                    // This trims older rows beyond the most recent 3.
                    $conn->query("
                        DELETE ph FROM password_history ph
                        JOIN (
                          SELECT id FROM password_history 
                          WHERE user_id = {$userId}
                          ORDER BY changed_at DESC
                          LIMIT 18446744073709551615 OFFSET 3
                        ) old_rows ON ph.id = old_rows.id
                    ");

                    $conn->commit();
                    $ok = true;
                    $message = "✅ Password changed successfully.";
                } catch (Throwable $e) {
                    $conn->rollback();
                    $message = "❌ Error changing password. Please try again.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Change Password</title>
<style>
  body {
    background: #ffe6f2; /* light pink */
    font-family: Arial, sans-serif;
    min-height: 100vh;
    margin: 0;
    display: grid;
    place-items: center;
  }
  .card {
    width: 360px;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    padding: 28px;
    text-align: center;
  }
  h2 { margin: 0 0 12px; color: #d63384; }
  p.sub { margin: 0 0 18px; color: #666; font-size: 14px; }
  .row { text-align: left; margin: 12px 0; }
  label { display: block; font-weight: 600; margin-bottom: 6px; color: #444; }
  input[type="password"] {
    width: 100%; padding: 12px; border: 1px solid #e7c2d5; border-radius: 10px; font-size: 14px;
  }
  button {
    width: 100%; margin-top: 12px; padding: 12px 14px;
    background: #d63384; color: #fff; border: none; border-radius: 10px; cursor: pointer; font-weight: 600;
  }
  button:hover { background: #c02573; }
  .msg { margin-top: 14px; padding: 10px; border-radius: 8px; font-size: 14px; }
  .ok  { background: #e6ffed; color: #136b2a; border: 1px solid #b0f2c1; }
  .err { background: #ffe6ea; color: #8a1e36; border: 1px solid #ffbfd0; }
  .links { margin-top: 14px; font-size: 14px; }
  .links a { color: #d63384; text-decoration: none; font-weight: 600; }
  .links a:hover { text-decoration: underline; }
</style>
</head>
<body>
  <div class="card">
    <h2>Change Password</h2>
    <p class="sub">Use a strong password you haven’t used recently.</p>

    <form method="POST" action="">
      <div class="row">
        <label for="old_password">Old password</label>
        <input type="password" id="old_password" name="old_password" required />
      </div>
      <div class="row">
        <label for="new_password">New password</label>
        <input type="password" id="new_password" name="new_password" minlength="8" required />
      </div>
      <div class="row">
        <label for="confirm_password">Confirm new password</label>
        <input type="password" id="confirm_password" name="confirm_password" minlength="8" required />
      </div>
      <button type="submit">Update Password</button>
    </form>

    <?php if ($message): ?>
      <div class="msg <?= $ok ? 'ok' : 'err' ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="links">
      <a href="feed.php">← Back to Feed</a>
    </div>
  </div>
</body>
</html>
