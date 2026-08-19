<?php

session_start();

if (!isset($_SESSION['admin_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

?>

<!DOCTYPE html>
<html>

<head>
    <title>Admin Dashboard</title>
</head>

<body>

    <h1>Admin Dashboard</h1>

    <p>
        Welcome,
        <?php echo $_SESSION['first_name'] . " " . $_SESSION['last_name']; ?>!
    </p>

    <p>You are logged in as an administrator.</p>

    <hr>

    <h2>Admin Menu</h2>

    <ul>
        <li>View Complaints and Inquiries</li>
        <li>Manage Complaints and Inquiries</li>
        <li>Respond to Concerns</li>
        <li>Update Status</li>
        <li>Reports</li>
        <li><a href="../auth/logout.php">Logout</a></li>
    </ul>

</body>

</html>