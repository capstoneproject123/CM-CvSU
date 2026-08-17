<?php
session_start();
include 'db.php';

$error = "";

if(isset($_POST['login'])){
    $acc = $_POST['account_number'];
    $pass = $_POST['password'];

    if($acc == "" || $pass == ""){
        $error = "Enter account number and password";
    } else {

        $res = mysqli_query($conn, "SELECT * FROM users WHERE account_number='$acc'");

        if(mysqli_num_rows($res) > 0){
            $user = mysqli_fetch_assoc($res);

            if(md5($pass) == $user['password']){
                $_SESSION['user'] = $user;
                header("Location: dashboard.php");
            } else {
                $error = "Wrong password";
            }

        } else {
            $error = "Account No. not found";
        }
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - DnD Company</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="auth-page">
    <div class="container">
        <div class="logo">
            <h1>DnD Company</h1>
            <p>Information & Security Management System</p>
        </div>
        
        <h2>Welcome Back!</h2>
        
        <?php if($error) echo "<div class='alert alert-error'>$error</div>"; ?>

        <form method="POST">
            <div class="form-group">
                <label>Account Number</label>
                <input type="text" name="account_number" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>

            <button type="submit" name="login">Login</button>
        </form>

        <div class="footer">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
    </div>
</body>
</html>