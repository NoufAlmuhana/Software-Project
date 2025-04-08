<?php
session_start();

// إعداد الاتصال بقاعدة البيانات
$host = "localhost";
$user = "root";
$pass = "root";
$dbname = "wisaldb";

$conn = new mysqli($host, $user, $pass, $dbname);

// التحقق من الاتصال
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";

// تنفيذ التحقق فقط إذا تم إرسال النموذج
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        $stmt = $conn->prepare("SELECT username, password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($db_username, $db_password);
            $stmt->fetch();
            // إضافة طباعة للتصحيح
            error_log("DB Username: " . $db_username);
            error_log("DB Password: " . $db_password);
            if (password_verify($password, $db_password)) {
                $_SESSION['username'] = $db_username;
                header("Location: index.html");
                exit();
            } else {
                $message = "❌ Incorrect username or password!";
            }
        } else {
            $message = "❌ Incorrect username or password!";
        }

        $stmt->close();
    } else {
        $message = "❌ Please fill in all fields!";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Log In</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(to right, #d7ccc8, #bcaaa4);
        }
        .container {
            background-color: #3e2723;
            color: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            width: 400px;
            position: relative;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #795548;
            border-radius: 5px;
            background-color: #efebe9;
            color: #3e2723;
        }
        button {
            background-color: #795548;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 10px;
            cursor: pointer;
            width: 100%;
        }
        button:hover {
            background-color: #6d4c41;
        }
        .error {
            color: red;
            text-align: center;
        }
        .welcome-text {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 24px;
            font-weight: bold;
            color: #3e2723;
        }
        .signup-link {
            text-align: center;
            margin-top: 20px;
            color: #efebe9;
        }
        .signup-link a {
            color: #fff4d1;
            text-decoration: none;
        }
        .signup-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="welcome-text">Welcome to Wisal</div>
    <h2>Log In</h2>
    <form method="POST" action="">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Log In</button>
        <?php if (!empty($message)): ?>
            <p class="error"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>
    </form>
    <div class="signup-link">
        <p>If you don't have an account, <a href="signup.php">Sign up</a>.</p>
    </div>
</div>

</body>
</html>
