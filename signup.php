<?php
session_start(); // بدء الجلسة


$host = "localhost"; 
$user = "root"; 
$password = "root"; 
$database = "wisaldb"; 

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $phone = $_POST['phone'];

  
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);


    $sql = "INSERT INTO users (userName, PASSWORD, phone) VALUES ('$username', '$hashed_password', '$phone')";

    if ($conn->query($sql) === TRUE) {
    
        $userID = $conn->insert_id;

       
        $_SESSION['userID'] = $userID;
        $_SESSION['userName'] = $username;

      
        header("Location: index.html");
        exit();
    } else {
        $message = "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
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
        .success {
            color: green;
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
    </style>
</head>
<body>

<div class="container">
    <div class="welcome-text">Welcome to Wisal</div>
    <h2>Sign Up</h2>
    <form action="" method="POST">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <input type="text" name="phone" placeholder="Phone Number" required>
        <button type="submit">Sign Up</button>
        <?php if (!empty($message)): ?>
            <p class="<?= strpos($message, 'successfully') !== false ? 'success' : 'error' ?>"><?= $message ?></p>
        <?php endif; ?>
    </form>
</div>

</body>
</html>
