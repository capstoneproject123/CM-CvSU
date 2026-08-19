<?php
session_start();

if (!isset($_SESSION['student_id'])){

  header("Location: login.php");
  exit();
}
?>

<!DOCTYPE html>
<html>
  <head>
    <title>Student Dashboard</title>
  </head>

  <body>
    <h1>CEIT Complaint and Inquiry Management System</h1>

<hr>

<h2>
Welcome,
<?php echo $_SESSION['first_name']. " " . $_SESSION['last_name']; ?>
</h2>

<p><strong>Student Number:</strong>
<?php echo $_SESSION['student_number']; ?>
</p>

<p><strong>Email:</strong>
<?php echo $_SESSION['email']; ?>
</p>

<hr>

<h2>Student Menu</h2>

<ul>

    <li>
        <a href="report.php">Submit</a>
    </li>

    <li>
        <a href="track.php">Track</a>
    </li>

    <li>
        <a href="logout.php">Logout</a>
    </li>

</ul>
  </body>
</html>
