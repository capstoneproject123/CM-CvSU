<?php
session_start();
include 'db.php';

// Check if user is logged in
if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}
            
$user = $_SESSION['user'];
$userRole = $user['role'];
$isAdmin = ($userRole == "Admin");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body class="dashboard <?php if(!$isAdmin) echo "employee-view"; ?>">

    <!-- ========== SIDEBAR (ADMIN ONLY) ========== -->
    <?php if($isAdmin){ ?>
        <div class="sidebar">
            <h3> SECURITY PANEL</h3>
            <a onclick="showAdminSection('dashboard-main')">Dashboard</a>
            <a onclick="showAdminSection('users')">Users</a>
            <a onclick="showAdminSection('reports')">Reports</a>
            <a onclick="showAdminSection('logs')">Logs</a>
        </div>
    <?php } ?>

    <!-- ========== MAIN CONTENT AREA ========== -->
    <div class="main">

        <!-- TOP BAR -->
        <div class="topbar">
            <div class="welcome">
                Welcome, <b><?php echo $user['first_name']; ?></b>
                <small>Information & Security Management System</small>
                Role: <span class="role <?php echo strtolower($userRole); ?>"><?php echo $userRole; ?></span>
            </div>
            <a class="logout" href="logout.php">Logout</a>
        </div>

        <!-- ========== ADMIN DASHBOARD ========== -->
        <?php if($isAdmin){ ?>

            <!-- Dashboard Main -->
            <div id="dashboard-main" class="admin-section active">
                <div class="section-content">
                    <h2>Dashboard Overview</h2>
                    <div class="cards">
                        <div class="card">
                            <h3>Total Users</h3>
                            <p>
                                <?php
                                $query = mysqli_query($conn, "SELECT COUNT(*) as total FROM users");
                                $result = mysqli_fetch_assoc($query);
                                echo $result['total'];
                                ?>
                            </p>
                        </div>
                        <div class="card">
                            <h3>Security Logs</h3>
                            <p>Active</p>
                        </div>
                        <div class="card">
                            <h3>System Status</h3>
                            <p>Online</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users Management -->
            <div id="users" class="admin-section">
                <a href="#" class="back-to-dashboard" onclick="showAdminSection('dashboard-main'); return false;">← Back to Dashboard</a>
                <div class="section-content">
                    <h2>User Management</h2>
                    <table>
                        <tr>
                            <th>Account No.</th>
                            <th>Full Name</th>
                            <th>Username</th>
                            <th>Password</th>
                            <th>Role</th>
                            <th>Status</th>
                        </tr>
                        <?php
                        $users_query = mysqli_query($conn, "SELECT * FROM users");
                        while($row = mysqli_fetch_assoc($users_query)){
                            $roleClass = ($row['role'] == 'Admin') ? 'admin' : 'employee';
                            echo "
                            <tr>
                                <td>" . $row['account_number'] . "</td>
                                <td>" . $row['first_name'] . " " . $row['last_name'] . "</td>
                                <td>" . $row['username'] . "</td>
                                <td><span style='font-family: monospace; color: #888;'>" . $row['password'] . "</span></td>
                                <td><span class='badge $roleClass'>" . $row['role'] . "</span></td>
                                <td><span style='color: #27ae60; font-weight: bold;'>✓ Active</span></td>
                            </tr>
                            ";
                        }
                        ?>
                    </table>
                </div>
            </div>

            <!-- Reports -->
            <div id="reports" class="admin-section">
                <a href="#" class="back-to-dashboard" onclick="showAdminSection('dashboard-main'); return false;">← Back to Dashboard</a>
                <div class="section-content">
                    <h2>System Reports</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <h4>Login Attempts</h4>
                            <div class="number">245</div>
                        </div>
                        <div class="stat-card">
                            <h4>Failed Logins</h4>
                            <div class="number">8</div>
                        </div>
                        <div class="stat-card">
                            <h4>Active Sessions</h4>
                            <div class="number">12</div>
                        </div>
                        <div class="stat-card">
                            <h4>Data Access</h4>
                            <div class="number">1,823</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Logs -->
            <div id="logs" class="admin-section">
                <a href="#" class="back-to-dashboard" onclick="showAdminSection('dashboard-main'); return false;">← Back to Dashboard</a>
                <div class="section-content">
                    <h2>Security Logs</h2>
                    <div style="max-height: 500px; overflow-y: auto;">
                        <div class="log-item">
                            <div class="timestamp">2026-04-22 10:45:32</div>
                            <div class="action">User Login</div>
                            <div class="user">User: Dherick (Account: 00001)</div>
                        </div>
                        <div class="log-item">
                            <div class="timestamp">2026-04-22 10:32:15</div>
                            <div class="action">User Logout</div>
                            <div class="user">User: Darl (Account: 00002)</div>
                        </div>
                        <div class="log-item">
                            <div class="timestamp">2026-04-22 09:58:47</div>
                            <div class="action">Failed Login Attempt</div>
                            <div class="user">Account: 00003 - Invalid Password</div>
                        </div>
                        <div class="log-item">
                            <div class="timestamp">2026-04-22 09:15:22</div>
                            <div class="action">Data Access</div>
                            <div class="user">User: admin_user accessed Reports</div>
                        </div>
                        <div class="log-item">
                            <div class="timestamp">2026-04-22 08:42:05</div>
                            <div class="action">User Registration</div>
                            <div class="user">New User Created: Michael_Jackson (Account: 00004)</div>
                        </div>
                        <div class="log-item">
                            <div class="timestamp">2026-04-22 08:10:33</div>
                            <div class="action">Settings Modified</div>
                            <div class="user">Admin modified system settings</div>
                        </div>
                    </div>
                </div>
            </div>

        <!-- ========== EMPLOYEE DASHBOARD ========== -->
        <?php } else { ?>

            <!-- Employee Profile -->
            <div class="employee-info">
                <h3>My Profile Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Account Number</label>
                        <span><?php echo $user['account_number']; ?></span>
                    </div>
                    <div class="info-item">
                        <label>Full Name</label>
                        <span><?php echo $user['first_name'] . " " . $user['last_name']; ?></span>
                    </div>
                    <div class="info-item">
                        <label>Username</label>
                        <span><?php echo $user['username']; ?></span>
                    </div>
                    <div class="info-item">
                        <label>Role</label>
                        <span><?php echo $user['role']; ?></span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h3>Quick Actions</h3>
                <div class="action-buttons">
                    <a href="#" class="action-btn" onclick="showSection('reports'); return false;">View Reports</a>
                    <a href="#" class="action-btn" onclick="showSection('messages'); return false;">Messages</a>
                    <a href="#" class="action-btn" onclick="showSection('settings'); return false;">Settings</a>
                </div>
            </div>

            <!-- Reports Section -->
            <div id="reports" class="content-section">
                <a href="#" class="back-btn" onclick="hideSection('reports'); return false;">← Back</a>
                <h2>My Reports</h2>
                <div class="reports-grid">
                    <div class="report-card">
                        <h4>Monthly Performance Report</h4>
                        <p>Generated on: <?php echo date('Y-m-d'); ?></p>
                        <p>Status: <strong>Completed</strong></p>
                    </div>
                    <div class="report-card">
                        <h4>Quarterly Summary</h4>
                        <p>Generated on: <?php echo date('Y-m-01'); ?></p>
                        <p>Status: <strong>Pending Review</strong></p>
                    </div>
                    <div class="report-card">
                        <h4>Annual Review</h4>
                        <p>Generated on: <?php echo date('Y-01-01'); ?></p>
                        <p>Status: <strong>Available for Download</strong></p>
                    </div>
                </div>
            </div>

            <!-- Messages Section -->
            <div id="messages" class="content-section">
                <a href="#" class="back-btn" onclick="hideSection('messages'); return false;">← Back</a>
                <h2>Messages</h2>
                <div class="messages-list">
                    <div class="message-item">
                        <div class="sender">Admin</div>
                        <p>Welcome to the system! You are now part of the company.</p>
                        <div class="time">Today at 10:30 AM</div>
                    </div>
                    <div class="message-item">
                        <div class="sender">System</div>
                        <p>Your account has been successfully created and activated.</p>
                        <div class="time">Yesterday at 3:45 PM</div>
                    </div>
                    <div class="message-item">
                        <div class="sender">Manager</div>
                        <p>Please review the updated guidelines in the Reports section.</p>
                        <div class="time">2 days ago</div>
                    </div>
                </div>
            </div>

            <!-- Settings Section -->
            <div id="settings" class="content-section">
                <a href="#" class="back-btn" onclick="hideSection('settings'); return false;">← Back</a>
                <h2>Settings</h2>
                <div class="settings-form">
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" placeholder="your.email@example.com" readonly>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" placeholder="(+63) 967-123-6734">
                    </div>
                    <div class="form-group">
                        <label>Department</label>
                        <input type="text" placeholder="Your Department" readonly>
                    </div>
                    <div class="form-group">
                        <label>Notification Preferences</label>
                        <select>
                            <option>Email Notifications</option>
                            <option>SMS Notifications</option>
                            <option>No Notifications</option>
                        </select>
                    </div>
                    <button class="save-btn">Save Changes</button>
                </div>
            </div>

        <?php } ?>

    </div>

    <!-- ========== JAVASCRIPT ========== -->
    <script>
        // Show admin sections (Dashboard, Users, Reports, Logs)
        function showAdminSection(sectionId){
            event.preventDefault();
            
            // Hide all admin sections
            var sections = document.querySelectorAll('.admin-section');
            sections.forEach(function(section){
                section.classList.remove('active');
            });
            
            // Show selected section
            document.getElementById(sectionId).classList.add('active');
        }

        // Show employee sections (Reports, Messages, Settings)
        function showSection(sectionId){
            event.preventDefault();
            
            // Hide all content sections
            var sections = document.querySelectorAll('.content-section');
            sections.forEach(function(section){
                section.classList.remove('active');
            });
            
            // Show selected section
            document.getElementById(sectionId).classList.add('active');
        }

        // Hide employee section
        function hideSection(sectionId){
            event.preventDefault();
            document.getElementById(sectionId).classList.remove('active');
        }
    </script>

</body>
</html>