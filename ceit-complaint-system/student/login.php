<?php

session_start();

include '../config/database.php';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $email = trim($_POST['email']);
  $password = trim($_POST['password']);

  if(empty($email) || empty($password)) {
    echo "<script>alert('Please fill in all required field. ');</script>";
  }else{
    $checkUser = mysqli_query(
      $conn,
      "SELECT * FROM students WHERE email = '$email'"
    );
  
  if(mysqli_num_rows($checkUser) == 0) {
    echo "<script>alert('Email not found.');</script>";
  }else{
    $user = mysqli_fetch_assoc($checkUser);
    if(password_verify($password, $user['password'])){
      $_SESSION['student_id'] = $user['id'];
      $_SESSION['student_number'] = $user['student_number'];
      $_SESSION['first_name'] = $user['first_name'];
      $_SESSION['last_name'] = $user['last_name'];
      $_SESSION['email'] = $user['email'];

      header("Location: dashboard.php");
      exit();
    } else {
      echo"<script>alert('Incorrect Password.');</script>";
    }
  }
  }
}
?>


<!DOCTYPE html>
<html>
  <head>
    <title>Student Login</title>
  </head>

<body>
  <h2>Student Login</h2>

  <form method ="POST">

    <label>Email</label><br>
    <input type="email" name="email"><br><br>

    <label>Password</label><br>
    <input type="password" name="password"><br><br>

    <button type="submit">Login</button>
  </form>
</body>
</html>