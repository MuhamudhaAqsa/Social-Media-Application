<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include "db.php"; // Make sure db.php exists and $conn is defined

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Please login first.");
}

$userId = $_SESSION['user_id'];
$message = "";

// Handle new post submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content']);
    $isPublic = isset($_POST['is_public']) ? 1 : 0;
    $imageName = NULL;

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== 4) { // 4 = No file uploaded
        $uploadDir = "uploads/";
        $imageName = time() . "_" . basename($_FILES['image']['name']);
        $targetFile = $uploadDir . $imageName;

        // Validate file type
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        $fileExt = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        if (!in_array($fileExt, $allowedTypes)) {
            die("❌ Only JPG, PNG, GIF files are allowed.");
        }

        // Check for upload errors
        if ($_FILES['image']['error'] !== 0) {
            die("❌ Upload failed with error code: " . $_FILES['image']['error']);
        }

        // Resize image if too large
        list($width, $height) = getimagesize($_FILES['image']['tmp_name']);
        $maxDim = 600; // max width or height
        if ($width > $maxDim || $height > $maxDim) {
            $ratio = $width/$height;
            if ($ratio > 1) {
                $newWidth = $maxDim;
                $newHeight = $maxDim / $ratio;
            } else {
                $newWidth = $maxDim * $ratio;
                $newHeight = $maxDim;
            }

            $src = null;
            switch($fileExt){
                case "jpg":
                case "jpeg":
                    $src = imagecreatefromjpeg($_FILES['image']['tmp_name']);
                    break;
                case "png":
                    $src = imagecreatefrompng($_FILES['image']['tmp_name']);
                    break;
                case "gif":
                    $src = imagecreatefromgif($_FILES['image']['tmp_name']);
                    break;
            }

            $dst = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            // Save resized image
            switch($fileExt){
                case "jpg":
                case "jpeg":
                    imagejpeg($dst, $targetFile);
                    break;
                case "png":
                    imagepng($dst, $targetFile);
                    break;
                case "gif":
                    imagegif($dst, $targetFile);
                    break;
            }

            imagedestroy($src);
            imagedestroy($dst);
        } else {
            move_uploaded_file($_FILES['image']['tmp_name'], $targetFile);
        }
    }

    // Insert post into database
    $stmt = $conn->prepare("INSERT INTO posts (user_id, content, is_public, image) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isis", $userId, $content, $isPublic, $imageName);

    if ($stmt->execute()) {
        $message = "✅ Post added successfully!";
    } else {
        $message = "❌ Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Social Feed</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2>Add a Post</h2>
    <?php if ($message != "") echo "<p>$message</p>"; ?>
    <form method="POST" enctype="multipart/form-data">
        <textarea name="content" placeholder="Write your post here..." required></textarea><br><br>
        Upload Image: <input type="file" name="image"><br><br>
        Public: <input type="checkbox" name="is_public" checked><br><br>
        <button type="submit">Post</button>
    </form>

    <hr>
    <h2>Public Posts</h2>
    <?php
    $publicPosts = $conn->query("
        SELECT p.content, p.image, u.username, p.created_at 
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.is_public = 1 
        ORDER BY p.created_at DESC
    ");

    if ($publicPosts->num_rows > 0) {
        while ($row = $publicPosts->fetch_assoc()) {
            echo "<div class='post'>";
            echo "<div class='post-header'><b>".htmlspecialchars($row['username'])."</b> <span class='timestamp'>". $row['created_at'] ."</span></div>";
            echo "<div class='post-content'>".nl2br(htmlspecialchars($row['content']))."</div>";
            if (!empty($row['image'])) {
                echo "<div class='post-image'><img src='uploads/".htmlspecialchars($row['image'])."' alt='Post Image'></div>";
            }
            echo "</div>";
        }
    } else {
        echo "<p>No public posts yet.</p>";
    }
    ?>

    <h2>My Private Posts</h2>
    <?php
    $privatePosts = $conn->query("
        SELECT content, image, created_at 
        FROM posts 
        WHERE user_id=$userId AND is_public=0 
        ORDER BY created_at DESC
    ");

    if ($privatePosts->num_rows > 0) {
        while ($row = $privatePosts->fetch_assoc()) {
            echo "<div class='post private'>";
            echo "<div class='post-header'><span class='timestamp'>". $row['created_at'] ."</span></div>";
            echo "<div class='post-content'>".nl2br(htmlspecialchars($row['content']))."</div>";
            if (!empty($row['image'])) {
                echo "<div class='post-image'><img src='uploads/".htmlspecialchars($row['image'])."' alt='Post Image'></div>";
            }
            echo "</div>";
        }
    } else {
        echo "<p>No private posts yet.</p>";
    }
    ?>

    <hr>
    <a href="change_password.php">Change Password</a> | 
    <a href="logout.php">Logout</a>
</body>
</html>