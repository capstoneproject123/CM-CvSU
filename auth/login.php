<?php

session_start();

include '../config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Check if fields are empty
    if (empty($email) || empty($password)) {

        echo "<script>
                alert('Please fill in all required fields.');
              </script>";

    } else {

        // ==================================
        // CHECK STUDENT ACCOUNT
        // ==================================

        $stmt = mysqli_prepare(
            $conn,
            "SELECT * FROM students WHERE email = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "s",
            $email
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {

            $user = mysqli_fetch_assoc($result);

            // Verify student password
            if (password_verify($password, $user['password'])) {

                // Store student information in session
                $_SESSION['role'] = 'student';

                $_SESSION['student_id'] = $user['id'];
                $_SESSION['student_number'] = $user['student_number'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['email'] = $user['email'];

                // Go to student dashboard
                header("Location: ../student/dashboard.php");
                exit();

            } else {

                echo "<script>
                        alert('Incorrect Password.');
                      </script>";
            }

        } else {

            // ==================================
            // CHECK ADMIN ACCOUNT
            // ==================================

            $stmt = mysqli_prepare(
                $conn,
                "SELECT * FROM admins WHERE email = ?"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "s",
                $email
            );

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($result) > 0) {

                $user = mysqli_fetch_assoc($result);

                // Verify admin password
                if (password_verify($password, $user['password'])) {

                    // Store admin information in session
                    $_SESSION['role'] = 'admin';

                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];
                    $_SESSION['email'] = $user['email'];

                    // Go to admin dashboard
                    header("Location: ../admin/dashboard.php");
                    exit();

                } else {

                    echo "<script>
                            alert('Incorrect Password.');
                          </script>";
                }

            } else {

                echo "<script>
                        alert('Email not found.');
                      </script>";
            }
        }

        mysqli_stmt_close($stmt);
    }
}

?>

<!DOCTYPE html>
<html>

<head>
    <title>Login</title>
</head>

<body>

    <h2>Login</h2>

    <form method="POST">

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
            Login
        </button>

    </form>

    <br>

    <p>
        Don't have an account?
        <a href="register.php">
            <b>Create One Here</b>
        </a>
    </p>

</body>

</html>