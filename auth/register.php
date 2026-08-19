<?php

include '../config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $account_type = $_POST['account_type'] ?? '';

    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password_input = trim($_POST['password'] ?? '');

    $student_number = trim($_POST['student_number'] ?? '');

    // Check common fields
    if (
        empty($account_type) ||
        empty($first_name) ||
        empty($last_name) ||
        empty($email) ||
        empty($password_input)
    ) {

        echo "<script>alert('Please fill in all required fields.');</script>";

    } else {

        // Hash the password
        $password = password_hash($password_input, PASSWORD_DEFAULT);


        // =========================
        // STUDENT REGISTRATION
        // =========================

        if ($account_type == "student") {

            // Student number is required
            if (empty($student_number)) {

                echo "<script>alert('Please enter your student number.');</script>";

            } else {

                // Check if student number already exists
                $stmt = mysqli_prepare(
                    $conn,
                    "SELECT id FROM students WHERE student_number = ?"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "s",
                    $student_number
                );

                mysqli_stmt_execute($stmt);

                $result = mysqli_stmt_get_result($stmt);

                if (mysqli_num_rows($result) > 0) {

                    echo "<script>alert('Student number already exists.');</script>";

                } else {

                    // Check email
                    $stmt = mysqli_prepare(
                        $conn,
                        "SELECT id FROM students WHERE email = ?"
                    );

                    mysqli_stmt_bind_param(
                        $stmt,
                        "s",
                        $email
                    );

                    mysqli_stmt_execute($stmt);

                    $result = mysqli_stmt_get_result($stmt);

                    if (mysqli_num_rows($result) > 0) {

                        echo "<script>alert('Email already exists.');</script>";

                    } else {

                        // Insert student
                        $stmt = mysqli_prepare(
                            $conn,
                            "INSERT INTO students
                            (student_number, first_name, last_name, email, password)
                            VALUES (?, ?, ?, ?, ?)"
                        );

                        mysqli_stmt_bind_param(
                            $stmt,
                            "sssss",
                            $student_number,
                            $first_name,
                            $last_name,
                            $email,
                            $password
                        );

                        if (mysqli_stmt_execute($stmt)) {

                            echo "<script>
                                alert('Student registration successful!');
                                window.location = 'login.php';
                            </script>";
                            exit();

                        } else {

                            echo "Error: " . mysqli_error($conn);
                        }
                    }
                }

                mysqli_stmt_close($stmt);
            }


        // =========================
        // ADMIN REGISTRATION
        // =========================

        } elseif ($account_type == "admin") {

            // Check admin email
            $stmt = mysqli_prepare(
                $conn,
                "SELECT id FROM admins WHERE email = ?"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "s",
                $email
            );

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($result) > 0) {

                echo "<script>alert('Email already exists.');</script>";

            } else {

                // Insert admin
                $stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO admins
                    (first_name, last_name, email, password)
                    VALUES (?, ?, ?, ?)"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "ssss",
                    $first_name,
                    $last_name,
                    $email,
                    $password
                );

                if (mysqli_stmt_execute($stmt)) {

                    echo "<script>
                        alert('Admin registration successful!');
                        window.location = 'login.php';
                    </script>";
                    exit();

                } else {

                    echo "Error: " . mysqli_error($conn);
                }
            }

            mysqli_stmt_close($stmt);

        } else {

            echo "<script>alert('Please select an account type.');</script>";
        }
    }
}

?>

<!DOCTYPE html>
<html>

<head>
    <title>Create an Account</title>
</head>

<body>

<h2>Create an Account</h2>

<form method="POST">

    <label>Account Type</label><br>

    <input
        type="radio"
        name="account_type"
        value="student"
        onclick="showStudentNumber()"
    >
    Student

    <input
        type="radio"
        name="account_type"
        value="admin"
        onclick="hideStudentNumber()"
    >
    Admin

    <br><br>


    <!-- Student Number -->
    <div id="studentNumberField" style="display:none;">

        <label>Student Number</label><br>

        <input
            type="text"
            name="student_number"
        >

        <br><br>

    </div>


    <label>First Name</label><br>

    <input
        type="text"
        name="first_name"
    >

    <br><br>


    <label>Last Name</label><br>

    <input
        type="text"
        name="last_name"
    >

    <br><br>


    <label>Email</label><br>

    <input
        type="email"
        name="email"
    >

    <br><br>


    <label>Password</label><br>

    <input
        type="password"
        name="password"
    >

    <br><br>


    <button type="submit">
        Register
    </button>

</form>


<br>

<p>
    Already have an account?
    <a href="login.php"><b>Log In Here</b></a>
</p>


<script>

function showStudentNumber() {

    document.getElementById("studentNumberField").style.display = "block";

}

function hideStudentNumber() {

    document.getElementById("studentNumberField").style.display = "none";

}

</script>

</body>

</html>