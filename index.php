<?php
session_start();
// Database connection
$conn = new mysqli("localhost", "uklz9ew3hrop3", "zyrbspyjlzjb", "dbvf88fgdguzl1");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is already logged in
if(isset($_SESSION['user_id'])) {
    echo "<script>window.location.href = 'home.php';</script>";
    exit;
}

// Process login form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    
    $sql = "SELECT id, name, password FROM users WHERE email = '$email'";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_name'] = $row['name'];
            echo "<script>window.location.href = 'home.php';</script>";
            exit;
        } else {
            $error = "Invalid password";
        }
    } else {
        $error = "User not found";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facebook - Log In or Sign Up</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        
        .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1000px;
            width: 100%;
            padding: 20px;
        }
        
        .left-section {
            flex: 1;
            padding-right: 32px;
        }
        
        .logo {
            color: #1877f2;
            font-size: 4rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .tagline {
            font-size: 1.5rem;
            color: #1c1e21;
            line-height: 1.3;
        }
        
        .right-section {
            flex: 0 0 396px;
        }
        
        .login-form {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1), 0 8px 16px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        input[type="text"], input[type="password"], input[type="email"] {
            width: 100%;
            padding: 14px 16px;
            margin-bottom: 12px;
            border: 1px solid #dddfe2;
            border-radius: 6px;
            font-size: 1rem;
        }
        
        input[type="text"]:focus, input[type="password"]:focus, input[type="email"]:focus {
            outline: none;
            border-color: #1877f2;
            box-shadow: 0 0 0 2px #e7f3ff;
        }
        
        .login-btn {
            background-color: #1877f2;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1.25rem;
            font-weight: bold;
            padding: 12px;
            width: 100%;
            cursor: pointer;
            margin-bottom: 16px;
        }
        
        .login-btn:hover {
            background-color: #166fe5;
        }
        
        .forgot-password {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .forgot-password a {
            color: #1877f2;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .divider {
            border-bottom: 1px solid #dadde1;
            margin: 20px 0;
        }
        
        .create-account-btn {
            background-color: #42b72a;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1.1rem;
            font-weight: bold;
            padding: 12px 16px;
            cursor: pointer;
            display: block;
            margin: 0 auto;
        }
        
        .create-account-btn:hover {
            background-color: #36a420;
        }
        
        .error-message {
            color: #ff0000;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .create-page {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
        }
        
        .create-page a {
            font-weight: bold;
            color: #1c1e21;
            text-decoration: none;
        }
        
        @media (max-width: 900px) {
            .container {
                flex-direction: column;
                padding: 20px;
            }
            
            .left-section {
                text-align: center;
                margin-bottom: 40px;
                padding-right: 0;
            }
            
            .right-section {
                width: 100%;
                max-width: 396px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="left-section">
            <h1 class="logo">facebook</h1>
            <h2 class="tagline">Facebook helps you connect and share with the people in your life.</h2>
        </div>
        
        <div class="right-section">
            <form class="login-form" method="post" action="">
                <?php if(isset($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <input type="email" name="email" placeholder="Email address" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" class="login-btn">Log In</button>
                
                <div class="forgot-password">
                    <a href="#">Forgotten password?</a>
                </div>
                
                <div class="divider"></div>
                
                <button type="button" class="create-account-btn" onclick="window.location.href='signup.php'">Create New Account</button>
            </form>
            
            <div class="create-page">
                <a href="#">Create a Page</a> for a celebrity, brand or business.
            </div>
        </div>
    </div>
</body>
</html>
