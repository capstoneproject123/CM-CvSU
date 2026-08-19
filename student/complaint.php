<?php

session_start();

if(!isset($_SESSION['student_id'])) {
  header("Location: login.php");
  exit();
}

include '../config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

  $student_id = $_SESSION['student_id'];
  $category = $_POST['category'];
  $subject = $_POST['subject'];
  $description = $_POST['description'];

  if (empty($category) || empty($subject) || empty($description)) {
    echo "<script>alert('Please fill in all fields. ');</script>";
  } else {
    $sql = "INSERT INTO complaints
            (student_id, category, subject, description)
            VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
      $stmt, 
      "isss",
      $student_id,
      $category,
      $subject, 
      $description
    );

    if (mysqli_stmt_execute($stmt)) {
      echo "<script>alert('Complaint submitted successfully!');</script>";
    } else{
      echo "Error: " . mysqli_error($conn);

    }
    mysqli_stmt_close($stmt);
  }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Student Complaint</title>
</head>

<body>
    <h1>Submit Complaint</h1>

    <form method = "POST">

      <label>Category</label><br>
      <select name="category">
        <option value="">-- Select Category --</option>
        <option value="Academic">Academic</option>
        <option value="Faculty">Faculty</option>
        <option value="Facility">Facility</option>
        <option value="Student Services">Student Services</option>
        <option value="Other">Other</option>
      </select>

      <br><br>

      <label>Subject</label><br>
      <input type="text" name="subject">

      <br><br>
      <label>Description</label><br>
      <textarea name="description" rows="6" cols="50"></textarea>

      <br><br>

      <button type="submit">Submit Complaint</button>

    </form>

    <br>

    <a href="dashboard.php">Back to Dashboard</a>
    
</body>
</html>