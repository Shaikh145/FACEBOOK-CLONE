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

// Process registration form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $dob = $conn->real_escape_string($_POST['dob']);
    $gender = $conn->real_escape_string($_POST['gender']);
    
    // Check if email already exists
    $check_sql = "SELECT id FROM users WHERE email = '$email'";
    $check_result = $conn->query($check_sql);
    
    if ($check_result->num_rows > 0) {
        $error = "Email already registered";
    } else {
        // Insert new user
        $sql = "INSERT INTO users (name, email, password, dob, gender, created_at) 
                VALUES ('$name', '$email', '$password', '$dob', '$gender', NOW())";
        
        if ($conn->query($sql) === TRUE) {
            $user_id = $conn->insert_id;
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $name;
            echo "<script>window.location.href = 'home.php';</script>";
            exit;
        } else {
            $error = "Error: " . $sql . "<br>" . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up for Facebook</title>
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
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 432px;
            width: 100%;
        }
        
        .header {
            text-align: center;
            margin-bottom: 10px;
        }
        
        .logo {
            color: #1877f2;
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .signup-form {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1), 0 8px 16px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .form-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        .form-subtitle {
            color: #606770;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }
        
        .divider {
            border-bottom: 1px solid #dadde1;
            margin: 20px 0;
        }
        
        input[type="text"], input[type="password"], input[type="email"], input[type="date"] {
            width: 100%;
            padding: 11px;
            margin-bottom: 12px;
            border: 1px solid #dddfe2;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        
        .name-inputs {
            display: flex;
            gap: 10px;
            margin-bottom: 12px;
        }
        
        .name-inputs input {
            margin-bottom: 0;
        }
        
        .gender-options {
            display: flex;
            gap: 10px;
            margin-bottom: 12px;
        }
        
        .gender-option {
            flex: 1;
            border: 1px solid #ccd0d5;
            border-radius: 5px;
            padding: 8px;
            display: flex;
            align-items: center;
        }
        
        .gender-option label {
            margin-left: 5px;
            font-size: 0.9rem;
        }
        
        .terms {
            font-size: 0.7rem;
            color: #777;
            margin: 15px 0;
        }
        
        .terms a {
            color: #385898;
            text-decoration: none;
        }
        
        .signup-btn {
            background-color: #00a400;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1.1rem;
            font-weight: bold;
            padding: 10px 16px;
            cursor: pointer;
            display: block;
            margin: 20px auto 0;
            width: 50%;
        }
        
        .signup-btn:hover {
            background-color: #008f00;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .login-link a {
            color: #1877f2;
            text-decoration: none;
            font-weight: bold;
        }
        
        .error-message {
            color: #ff0000;
            margin-bottom: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="logo">facebook</h1>
        </div>
        
        <div class="signup-form">
            <h2 class="form-title">Create a new account</h2>
            <p class="form-subtitle">It's quick and easy.</p>
            
            <div class="divider"></div>
            
            <?php if(isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="post" action="">
                <div class="name-inputs">
                    <input type="text" name="name" placeholder="Full name" required>
                </div>
                
                <input type="email" name="email" placeholder="Email address" required>
                <input type="password" name="password" placeholder="New password" required>
                
                <div class="dob-section">
                    <p style="font-size: 0.8rem; color: #606770; margin-bottom: 5px;">Date of birth</p>
                    <input type="date" name="dob" required>
                </div>
                
                <div class="gender-section">
                    <p style="font-size: 0.8rem; color: #606770; margin-bottom: 5px;">Gender</p>
                    <div class="gender-options">
                        <div class="gender-option">
                            <input type="radio" id="female" name="gender" value="female" required>
                            <label for="female">Female</label>
                        </div>
                        <div class="gender-option">
                            <input type="radio" id="male" name="gender" value="male" required>
                            <label for="male">Male</label>
                        </div>
                        <div class="gender-option">
                            <input type="radio" id="custom" name="gender" value="custom" required>
                            <label for="custom">Custom</label>
                        </div>
                    </div>
                </div>
                
                <p class="terms">
                    By clicking Sign Up, you agree to our <a href="#">Terms</a>, <a href="#">Privacy Policy</a> and <a href="#">Cookies Policy</a>. You may receive SMS notifications from us and can opt out at any time.
                </p>
                
                <button type="submit" class="signup-btn">Sign Up</button>
            </form>
        </div>
        
        <div class="login-link">
            <a href="index.php">Already have an account?</a>
        </div>
    </div>
</body>
</html>
