<?php
session_start();

// If already logged in → redirect to feed
if (isset($_SESSION['user_id'])) {
    header("Location: feed.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome - Social Media App</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: "Poppins", sans-serif;
            background: linear-gradient(135deg, #ff9a9e, #fad0c4);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            background: #fff;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            width: 400px;
            animation: fadeIn 1.2s ease;
        }

        h1 {
            margin-bottom: 10px;
            color: #e91e63;
            font-size: 2rem;
        }

        p {
            color: #555;
            margin-bottom: 25px;
            font-size: 1rem;
        }

        .buttons a {
            display: inline-block;
            margin: 10px;
            padding: 12px 25px;
            background: #e91e63;
            color: #fff;
            text-decoration: none;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 6px 12px rgba(233,30,99,0.3);
        }

        .buttons a:hover {
            background: #d81b60;
            transform: translateY(-3px);
            box-shadow: 0 10px 18px rgba(233,30,99,0.4);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1> Welcome </h1>
        <p>Your social world, beautifully connected</p>

        <div class="buttons">
            <a href="login.php">Login</a>
            <a href="signup.php">Sign Up</a>
        </div>
    </div>
</body>
</html>
