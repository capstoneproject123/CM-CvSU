<?php

session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

include '../config/database.php';

$student_id = $_SESSION['student_id'];

$sql = "SELECT type, category, subject, description, status, response, created_at
        FROM concerns
        WHERE student_id = ?
        ORDER BY created_at DESC";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $student_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>
<html>

<head>
    <title>Track Concerns</title>
</head>

<body>

    <h1>Track My Complaints and Inquiries</h1>

    <?php if (mysqli_num_rows($result) > 0) { ?>

        <table border="1" cellpadding="10">

            <tr>
                <th>Type</th>
                <th>Category</th>
                <th>Subject</th>
                <th>Description</th>
                <th>Status</th>
                <th>Response</th>
                <th>Date Submitted</th>
            </tr>

            <?php while ($row = mysqli_fetch_assoc($result)) { ?>

                <tr>

                    <td>
                        <?php echo htmlspecialchars($row['type']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row['category']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row['subject']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row['description']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row['status']); ?>
                    </td>

                    <td>
                        <?php

                        if (!empty($row['response'])) {
                            echo htmlspecialchars($row['response']);
                        } else {
                            echo "No response yet.";
                        }

                        ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row['created_at']); ?>
                    </td>

                </tr>

            <?php } ?>

        </table>

    <?php } else { ?>

        <p>You have not submitted any complaints or inquiries yet.</p>

    <?php } ?>

    <br>

    <a href="report.php">Submit a Complaint or Inquiry</a>

    <br><br>

    <a href="dashboard.php">Back to Dashboard</a>

</body>

</html>