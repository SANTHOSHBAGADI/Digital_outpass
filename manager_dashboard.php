<?php
require_once 'db_connect.php';
session_start();

// Check if user is logged in as manager
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'manager') {
    header("Location: index.php");
    exit();
}

// Handle Add Admin/Security
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_user'])) {
    $type = mysqli_real_escape_string($conn, $_POST['user_type']);
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $department = ($type === 'admin') ? mysqli_real_escape_string($conn, $_POST['department']) : null;

    // Validation
    if (empty($id) || empty($name) || empty($email) || empty($password) || empty($phone)) {
        $_SESSION['error'] = "All fields are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format";
    } elseif (!preg_match("/^[0-9]{10}$/", $phone)) {
        $_SESSION['error'] = "Invalid phone number (10 digits required)";
    } elseif ($type === 'admin' && empty($department)) {
        $_SESSION['error'] = "Department is required for admins";
    } else {
        $table = ($type === 'security') ? 'security' : $type . 's';
        $id_field = $type . '_id';
        
        $check_query = "SELECT * FROM $table WHERE $id_field = ? OR email = ?";
        $stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($stmt, "ss", $id, $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $_SESSION['error'] = "ID or Email already exists";
        } else {
            if ($type === 'admin') {
                $query = "INSERT INTO $table ($id_field, name, email, password, phone, department) 
                          VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "ssssss", $id, $name, $email, $password, $phone, $department);
            } else {
                $query = "INSERT INTO $table ($id_field, name, email, password, phone) 
                          VALUES (?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "sssss", $id, $name, $email, $password, $phone);
            }
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success'] = ucfirst($type) . " added successfully!";
            } else {
                $_SESSION['error'] = "Failed to add " . $type . ". Please try again.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    header("Location: manager_dashboard.php");
    exit();
}

// Handle Delete User
if (isset($_GET['delete'])) {
    $type = mysqli_real_escape_string($conn, $_GET['type']);
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    
    $table = ($type === 'security') ? 'security' : $type . 's';
    $id_field = $type . '_id';
    
    $query = "DELETE FROM $table WHERE $id_field = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $id);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = ucfirst($type) . " deleted successfully!";
    } else {
        $_SESSION['error'] = "Failed to delete " . $type . ".";
    }
    mysqli_stmt_close($stmt);
    header("Location: manager_dashboard.php");
    exit();
}

// Handle Clear Log Book
if (isset($_GET['clear_logbook'])) {
    $query = "DELETE FROM log_book";
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Log book cleared successfully!";
    } else {
        $_SESSION['error'] = "Failed to clear log book.";
    }
    header("Location: manager_dashboard.php");
    exit();
}

// Fetch all admins and security personnel
$admins = [];
$security = [];

$admin_query = "SELECT admin_id, name, email, phone, department FROM admins";
$admin_result = mysqli_query($conn, $admin_query);
if ($admin_result) {
    $admins = mysqli_fetch_all($admin_result, MYSQLI_ASSOC);
}

$security_query = "SELECT security_id, name, email, phone FROM security";
$security_result = mysqli_query($conn, $security_query);
if ($security_result) {
    $security = mysqli_fetch_all($security_result, MYSQLI_ASSOC);
}

// Fetch log book entries with department filter
$selected_department = isset($_GET['department']) ? mysqli_real_escape_string($conn, $_GET['department']) : 'all';
$log_book = [];

if ($selected_department === 'all') {
    $log_query = "SELECT lb.id, lb.request_id, lb.student_id, lb.check_in_time, lb.check_out_time, lb.verify_time, s.department 
                  FROM log_book lb 
                  JOIN students s ON lb.student_id = s.student_id 
                  ORDER BY lb.id DESC";
    $log_result = mysqli_query($conn, $log_query);
} else {
    $log_query = "SELECT lb.id, lb.request_id, lb.student_id, lb.check_in_time, lb.check_out_time, lb.verify_time, s.department 
                  FROM log_book lb 
                  JOIN students s ON lb.student_id = s.student_id 
                  WHERE s.department = ? 
                  ORDER BY lb.id DESC";
    $stmt = mysqli_prepare($conn, $log_query);
    mysqli_stmt_bind_param($stmt, "s", $selected_department);
    mysqli_stmt_execute($stmt);
    $log_result = mysqli_stmt_get_result($stmt);
}

if ($log_result) {
    $log_book = mysqli_fetch_all($log_result, MYSQLI_ASSOC);
}

// Fetch distinct departments for filter
$dept_query = "SELECT DISTINCT department FROM students";
$dept_result = mysqli_query($conn, $dept_query);
$departments = $dept_result ? mysqli_fetch_all($dept_result, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manager Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        .card-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        tr:hover {
            background-color: #f8f9fa;
        }
        .btn {
            padding: 8px 12px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        .btn-primary {
            background-color:rgb(22, 254, 142);
            color: white;
        }
        .btn-primary:hover {
            background-color: #0069d9;
        }
        .btn-warning {
            background-color: #ffc107;
            color: #212529;
        }
        .btn-warning:hover {
            background-color: #e0a800;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
        }
        .tab.active {
            border-bottom: 3px solid #007bff;
            font-weight: 600;
            color: #007bff;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .filter-form {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user-shield"></i> ADMIN </h1>
            <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="message error animate__animated animate__shakeX">
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="message success animate__animated animate__fadeIn">
                <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <div class="tabs">
            <div class="tab active" onclick="openTab('admins')"><i class="fas fa-user-shield"></i> HOD</div>
            <div class="tab" onclick="openTab('security')"><i class="fas fa-shield-alt"></i> Security</div>
            <div class="tab" onclick="openTab('add-user')"><i class="fas fa-user-plus"></i> Add User</div>
            <div class="tab" onclick="openTab('logbook')"><i class="fas fa-book"></i> Log Book</div>
        </div>
        
        <div id="admins" class="tab-content active">
            <div class="card">
                <h2 class="card-title"><i class="fas fa-user-shield"></i> HOD List</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Department</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $admin): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($admin['admin_id']); ?></td>
                            <td><?php echo htmlspecialchars($admin['name']); ?></td>
                            <td><?php echo htmlspecialchars($admin['email']); ?></td>
                            <td><?php echo htmlspecialchars($admin['phone']); ?></td>
                            <td><?php echo htmlspecialchars($admin['department']); ?></td>
                            <td>
                                <a href="manager_dashboard.php?delete=1&type=admin&id=<?php echo urlencode($admin['admin_id']); ?>" 
                                   class="btn btn-danger" 
                                   onclick="return confirm('Are you sure you want to delete this admin?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($admins)): ?>
                        <tr><td colspan="6">No admins found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div id="security" class="tab-content">
            <div class="card">
                <h2 class="card-title"><i class="fas fa-shield-alt"></i> Security Personnel</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($security as $sec): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($sec['security_id']); ?></td>
                            <td><?php echo htmlspecialchars($sec['name']); ?></td>
                            <td><?php echo htmlspecialchars($sec['email']); ?></td>
                            <td><?php echo htmlspecialchars($sec['phone']); ?></td>
                            <td>
                                <a href="manager_dashboard.php?delete=1&type=security&id=<?php echo urlencode($sec['security_id']); ?>" 
                                   class="btn btn-danger" 
                                   onclick="return confirm('Are you sure you want to delete this security personnel?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($security)): ?>
                        <tr><td colspan="5">No security personnel found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div id="add-user" class="tab-content">
            <div class="card">
                <h2 class="card-title"><i class="fas fa-user-plus"></i> Add New User</h2>
                <form method="POST" action="manager_dashboard.php">
                    <div class="form-group">
                        <label for="user_type">User Type</label>
                        <select name="user_type" id="user_type" class="form-control" onchange="toggleDepartmentField()" required>
                            <option value="">Select User Type</option>
                            <option value="admin">HOD</option>
                            <option value="security">Security</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="id">ID</label>
                        <input type="text" name="id" id="id" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" name="name" id="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" name="email" id="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone (10 digits)</label>
                        <input type="text" name="phone" id="phone" class="form-control" required>
                    </div>
                    <div class="form-group" id="department-group" style="display: none;">
                        <label for="department">Department</label>
                        <select name="department" id="department" class="form-control">
                            <option value="">Select Department</option>
                            <option value="CSE">CSE</option>
                            <option value="ECE">ECE</option>
                            <option value="MECH">MECH</option>
                            <option value="CIVIL">CIVIL</option>
                            <option value="EEE">EEE</option>
                        </select>
                    </div>
                    <button type="submit" name="add_user" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Add User
                    </button>
                </form>
            </div>
        </div>
        
        <div id="logbook" class="tab-content">
            <div class="card">
                <h2 class="card-title"><i class="fas fa-book"></i> Log Book Entries</h2>
                <div class="filter-form">
                    <form method="GET" action="manager_dashboard.php">
                        <div class="form-group">
                            <label for="department">Filter by Department</label>
                            <select name="department" id="department" class="form-control" onchange="this.form.submit()">
                                <option value="all" <?php echo $selected_department === 'all' ? 'selected' : ''; ?>>All Departments</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept['department']); ?>" 
                                            <?php echo $selected_department === $dept['department'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['department']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Request ID</th>
                            <th>Student ID</th>
                            <th>Department</th>
                            <th>Check-In Time</th>
                            <th>Check-Out Time</th>
                            <th>Verify Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($log_book as $log): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($log['id']); ?></td>
                            <td><?php echo htmlspecialchars($log['request_id']); ?></td>
                            <td><?php echo htmlspecialchars($log['student_id']); ?></td>
                            <td><?php echo htmlspecialchars($log['department']); ?></td>
                            <td><?php echo $log['check_in_time'] ? htmlspecialchars($log['check_in_time']) : '-'; ?></td>
                            <td><?php echo $log['check_out_time'] ? htmlspecialchars($log['check_out_time']) : '-'; ?></td>
                            <td><?php echo $log['verify_time'] ? htmlspecialchars($log['verify_time']) : '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($log_book)): ?>
                        <tr><td colspan="7">No log book entries found for this department.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div style="margin-top: 20px;">
                    <a href="manager_dashboard.php?clear_logbook=1" 
                       class="btn btn-warning" 
                       onclick="return confirm('Are you sure you want to clear the entire log book? This action cannot be undone.')">
                        <i class="fas fa-trash-alt"></i> Clear Log Book
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function openTab(tabName) {
            const tabContents = document.getElementsByClassName('tab-content');
            for (let i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove('active');
            }
            const tabs = document.getElementsByClassName('tab');
            for (let i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove('active');
            }
            document.getElementById(tabName).classList.add('active');
            event.currentTarget.classList.add('active');
        }
        
        function toggleDepartmentField() {
            const userType = document.getElementById('user_type').value;
            const departmentGroup = document.getElementById('department-group');
            if (userType === 'admin') {
                departmentGroup.style.display = 'block';
                document.getElementById('department').required = true;
            } else {
                departmentGroup.style.display = 'none';
                document.getElementById('department').required = false;
            }
        }
    </script>
</body>
</html>