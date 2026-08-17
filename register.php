<?php
include 'db.php';

$error = "";

// auto account number (00001)
$res = mysqli_query($conn, "SELECT COUNT(*) as total FROM users");
$row = mysqli_fetch_assoc($res);
$account = str_pad($row['total'] + 1, 5, "0", STR_PAD_LEFT);

// register
if (isset($_POST['register'])) {
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $uname = $_POST['uname'];
    $role = $_POST['role'];
    $pass = $_POST['pass'];
    $conf = $_POST['conf'];

    // PASSWORD VALIDATION
    if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,20}$/', $pass)) {
        $error = "Password must contain letters, numbers, and be 8-20 characters long.";
    } elseif ($pass != $conf) {
        $error = "Password inputs do not match.";
    } else {
        $check = mysqli_query($conn, "SELECT * FROM users WHERE username='$uname'");

        if (mysqli_num_rows($check) > 0) {
            $error = "Username already exists";
        } else {
            $pass = md5($pass);

            mysqli_query($conn, "INSERT INTO users 
            (account_number, first_name, last_name, username, password, role)
            VALUES ('$account','$fname','$lname','$uname','$pass','$role')");

            header("Location: login.php");
        }
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - DnD Company</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="auth-page">
    <div class="container">
        <div class="logo">
            <h1>DnD Company</h1>
            <p>Information & Security Management System</p>
        </div>
        
        <h2>Create Account</h2>
        
        <?php if($error) echo "<div class='alert alert-error'>$error</div>"; ?>

        <form method="POST">
            <div class="form-group">
                <label>Account Number</label>
                <input type="text" value="<?php echo $account; ?>" readonly>
            </div>

            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="fname" required>
            </div>

            <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="lname" required>
            </div>

            <div class="form-group">
                <label>Username</label>
                <input type="text" name="uname" required>
            </div>

            <div class="form-group">
                <label>Role</label>
                <select name="role" required>
                    <option value=""></option>
                    <option value="Admin">Admin</option>
                    <option value="Employee">Employee</option>
                </select>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="pass" required>
            </div>

            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="conf" required>
            </div>

            <div class="button-group">
                <button type="button" class="btn-back" onclick="window.location.href='login.php'">Back</button>
                <button type="submit" name="register">Register</button>
            </div>
        </form> 
    </div>
</body>
</html>