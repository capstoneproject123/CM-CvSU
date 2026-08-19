<?php

session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

include '../config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $student_id = $_SESSION['student_id'];
    $type = $_POST['type'];
    $category = $_POST['category'];
    $subject = $_POST['subject'];
    $description = $_POST['description'];

    // Check if fields are empty
    if (empty($type) || empty($category) || empty($subject) || empty($description)) {

        echo "<script>alert('Please fill in all fields.');</script>";

    } else {

        // Insert concern into database
        $sql = "INSERT INTO concerns
                (student_id, type, category, subject, description)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param(
            $stmt,
            "issss",
            $student_id,
            $type,
            $category,
            $subject,
            $description
        );

        if (mysqli_stmt_execute($stmt)) {

            echo "<script>
                    alert('Your $type has been submitted successfully!');
                    window.location.href='track.php';
                  </script>";

        } else {

            echo "Error: " . mysqli_error($conn);
        }

        mysqli_stmt_close($stmt);
    }
}

?>

<!DOCTYPE html>
<html>

<head>
    <title>Submit Complaint or Inquiry</title>
</head>

<body>

    <h1>Submit a Complaint or Inquiry</h1>

    <form method="POST">

        <label>Type</label><br>

        <input type="radio" name="type" value="Complaint" id="complaint">
        <label for="complaint">Complaint</label>

        <input type="radio" name="type" value="Inquiry" id="inquiry">
        <label for="inquiry">Inquiry</label>

        <br><br>


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

        <textarea name="description" rows="8" cols="60"></textarea>

        <br><br>


        <button type="submit">Submit</button>

    </form>

    <br>

    <a href="dashboard.php">Back to Dashboard</a>

</body>

</html>