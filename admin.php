<?php
/**
 * PayCampus Admin Dashboard (Enhanced with Payment Information)
 * College administrators can view and manage their students with payment details
 */

// Start session and check authentication
session_start();

// Check if college admin is logged in
if (!isset($_SESSION['email']) || !isset($_SESSION['college_name'])) {
    header('Location: cllglogin.html');
    exit();
}

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "form";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get logged-in college information
$college_email = $_SESSION['email'];
$college_name = $_SESSION['college_name'];
$admin_name = $_SESSION['admin_name'];

// Get college ID (c_id) from cllgsignup table
$stmt = $conn->prepare("SELECT c_id FROM cllgsignup WHERE email = ?");
if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->bind_param("s", $college_email);
$stmt->execute();
$result = $stmt->get_result();
$college_data = $result->fetch_assoc();

// Check if college exists
if (!$college_data) {
    die("College not found in database. Please contact administrator.");
}

$c_id = $college_data['c_id'];
$stmt->close();

// Fetch all students for this college with payment info
$sql = "SELECT 
            s.s_id, s.fullname, s.mothername, s.city, s.email, s.number, s.gender, 
            s.collegename, s.coursename, s.total_fees, s.due_fees, 
            s.currentyear, s.academicyear, s.username,
            COUNT(p.p_id) as payment_count,
            MAX(p.payment_date) as last_payment_date,
            MAX(p.installment_no) as last_installment
        FROM signup s
        LEFT JOIN payments p ON s.s_id = p.s_id AND p.status = 'Paid'
        WHERE s.c_id = ?
        GROUP BY s.s_id
        ORDER BY s.s_id DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->bind_param("i", $c_id);
$stmt->execute();
$result = $stmt->get_result();
$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}
$stmt->close();

// Get unique courses for filter
$stmt = $conn->prepare("SELECT DISTINCT coursename FROM signup WHERE c_id = ? ORDER BY coursename");
if ($stmt) {
    $stmt->bind_param("i", $c_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $courses = [];
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row['coursename'];
    }
    $stmt->close();
} else {
    $courses = [];
}

// Get unique academic years for filter
$stmt = $conn->prepare("SELECT DISTINCT academicyear FROM signup WHERE c_id = ? ORDER BY academicyear DESC");
if ($stmt) {
    $stmt->bind_param("i", $c_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $academic_years = [];
    while ($row = $result->fetch_assoc()) {
        $academic_years[] = $row['academicyear'];
    }
    $stmt->close();
} else {
    $academic_years = [];
}

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $student_id = $_POST['student_id'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete payments first (foreign key)
        $stmt = $conn->prepare("DELETE FROM payments WHERE s_id = ?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $stmt->close();
        
        // Delete student (ensure they belong to this college)
        $stmt = $conn->prepare("DELETE FROM signup WHERE s_id = ? AND c_id = ?");
        $stmt->bind_param("ii", $student_id, $c_id);
        $stmt->execute();
        $stmt->close();
        
        $conn->commit();
        header('Location: admin.php?deleted=success');
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Error deleting student: " . $e->getMessage();
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: cllglogin.html');
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo htmlspecialchars($college_name); ?></title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Header Section -->
    <header class="dashboard-header">
        <div class="header-content">
            <div class="header-left">
                <h1><i class="fas fa-graduation-cap"></i> PayCampus Dashboard</h1>
                <p class="college-name"><?php echo htmlspecialchars($college_name); ?></p>
                <p class="admin-name">Admin: <?php echo htmlspecialchars($admin_name); ?></p>
            </div>
            <div class="header-right">
                <a href="?logout=true" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </header>

    <!-- Main Dashboard Content -->
    <main class="dashboard-main">
        <div class="container">
            
            <!-- Statistics Overview -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3 id="total-students"><?php echo count($students); ?></h3>
                        <p>Total Students</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3 id="paid-students">
                            <?php 
                            $paid = 0;
                            foreach ($students as $s) {
                                if ($s['due_fees'] == 0) $paid++;
                            }
                            echo $paid;
                            ?>
                        </h3>
                        <p>Fees Paid</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3 id="pending-students">
                            <?php 
                            $pending = 0;
                            foreach ($students as $s) {
                                if ($s['due_fees'] > 0) $pending++;
                            }
                            echo $pending;
                            ?>
                        </h3>
                        <p>Fees Pending</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon red">
                        <i class="fas fa-rupee-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3 id="total-due">
                            ₹<?php 
                            $total_due = 0;
                            foreach ($students as $s) {
                                $total_due += $s['due_fees'];
                            }
                            echo number_format($total_due);
                            ?>
                        </h3>
                        <p>Total Due Amount</p>
                    </div>
                </div>
            </div>

            <!-- Controls Section -->
            <div class="controls-section">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search by name, email, or course...">
                </div>
                
                <div class="filter-controls">
                    <select id="courseFilter" class="filter-select">
                        <option value="">All Courses</option>
                        <?php foreach($courses as $course): ?>
                            <option value="<?php echo htmlspecialchars($course); ?>">
                                <?php echo strtoupper(htmlspecialchars($course)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select id="yearFilter" class="filter-select">
                        <option value="">All Academic Years</option>
                        <?php foreach($academic_years as $year): ?>
                            <option value="<?php echo htmlspecialchars($year); ?>">
                                <?php echo htmlspecialchars($year); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select id="statusFilter" class="filter-select">
                        <option value="">All Payment Status</option>
                        <option value="paid">Paid</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
                
                <div class="view-controls">
                    <button class="btn-view active" id="tableViewBtn" title="Table View">
                        <i class="fas fa-table"></i>
                    </button>
                    <button class="btn-view" id="cardViewBtn" title="Card View">
                        <i class="fas fa-th-large"></i>
                    </button>
                </div>
            </div>

            <!-- Table View -->
            <div class="table-container" id="tableView">
                <table class="students-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th data-sort="fullname">Full Name <i class="fas fa-sort"></i></th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th data-sort="coursename">Course <i class="fas fa-sort"></i></th>
                            <th>Current Year</th>
                            <th data-sort="total_fees">Total Fees <i class="fas fa-sort"></i></th>
                            <th data-sort="due_fees">Due Fees <i class="fas fa-sort"></i></th>
                            <th data-sort="status">Status <i class="fas fa-sort"></i></th>
                            <th>Payments</th>
                            <th>Last Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="studentsTableBody">
                        <?php foreach($students as $student): 
                            $status = $student['due_fees'] == 0 ? 'paid' : 'pending';
                            $status_class = $status === 'paid' ? 'status-paid' : 'status-pending';
                            $status_text = $status === 'paid' ? 'Paid' : 'Pending';
                            $last_payment_info = '';
                            if ($student['last_payment_date']) {
                                $last_payment_info = date('d M Y', strtotime($student['last_payment_date']));
                            }
                        ?>
                        <tr data-id="<?php echo $student['s_id']; ?>" 
                            data-fullname="<?php echo htmlspecialchars($student['fullname']); ?>"
                            data-email="<?php echo htmlspecialchars($student['email']); ?>"
                            data-course="<?php echo htmlspecialchars($student['coursename']); ?>"
                            data-year="<?php echo htmlspecialchars($student['academicyear']); ?>"
                            data-status="<?php echo $status; ?>"
                            data-total-fees="<?php echo $student['total_fees']; ?>"
                            data-due-fees="<?php echo $student['due_fees']; ?>">
                            <td><?php echo $student['s_id']; ?></td>
                            <td><?php echo htmlspecialchars($student['fullname']); ?></td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td><?php echo htmlspecialchars($student['number']); ?></td>
                            <td><?php echo strtoupper(htmlspecialchars($student['coursename'])); ?></td>
                            <td><?php echo strtoupper(htmlspecialchars($student['currentyear'])); ?></td>
                            <td>₹<?php echo number_format($student['total_fees']); ?></td>
                            <td>₹<?php echo number_format($student['due_fees']); ?></td>
                            <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                            <td>
                                <span class="payment-count" title="<?php echo $student['payment_count']; ?> payment(s) made">
                                    <i class="fas fa-receipt"></i> <?php echo $student['payment_count']; ?>
                                    <?php if ($student['last_installment']): ?>
                                        <small>(Last: #<?php echo $student['last_installment']; ?>)</small>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($last_payment_info): ?>
                                    <span class="last-payment"><?php echo $last_payment_info; ?></span>
                                <?php else: ?>
                                    <span class="no-payment">No payments</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="edit_student.php?id=<?php echo $student['s_id']; ?>" class="btn-action btn-edit" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="student_payment_history.php?id=<?php echo $student['s_id']; ?>" class="btn-action btn-history" title="Payment History">
                                        <i class="fas fa-history"></i>
                                    </a>
                                    <button onclick="deleteStudent(<?php echo $student['s_id']; ?>, '<?php echo htmlspecialchars($student['fullname']); ?>')" 
                                            class="btn-action btn-delete" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div id="noResults" class="no-results" style="display: none;">
                    <i class="fas fa-search"></i>
                    <p>No students found matching your criteria.</p>
                </div>
            </div>

            <!-- Card View -->
            <div class="cards-container" id="cardView" style="display: none;">
                <?php foreach($students as $student): 
                    $status = $student['due_fees'] == 0 ? 'paid' : 'pending';
                    $status_class = $status === 'paid' ? 'status-paid' : 'status-pending';
                    $status_text = $status === 'paid' ? 'Paid' : 'Pending';
                    $progress = $student['total_fees'] > 0 ? 
                        (($student['total_fees'] - $student['due_fees']) / $student['total_fees']) * 100 : 0;
                    $last_payment_info = '';
                    if ($student['last_payment_date']) {
                        $last_payment_info = date('d M Y', strtotime($student['last_payment_date']));
                    }
                ?>
                <div class="student-card" 
                     data-id="<?php echo $student['s_id']; ?>"
                     data-fullname="<?php echo htmlspecialchars($student['fullname']); ?>"
                     data-email="<?php echo htmlspecialchars($student['email']); ?>"
                     data-course="<?php echo htmlspecialchars($student['coursename']); ?>"
                     data-year="<?php echo htmlspecialchars($student['academicyear']); ?>"
                     data-status="<?php echo $status; ?>"
                     data-total-fees="<?php echo $student['total_fees']; ?>"
                     data-due-fees="<?php echo $student['due_fees']; ?>">
                    <div class="card-header">
                        <div class="student-avatar">
                            <?php echo strtoupper(substr($student['fullname'], 0, 2)); ?>
                        </div>
                        <div class="student-info">
                            <h3><?php echo htmlspecialchars($student['fullname']); ?></h3>
                            <p class="student-id">ID: <?php echo $student['s_id']; ?> | <?php echo strtoupper(htmlspecialchars($student['currentyear'])); ?></p>
                        </div>
                        <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                    </div>
                    
                    <div class="card-body">
                        <div class="info-row">
                            <i class="fas fa-envelope"></i>
                            <span><?php echo htmlspecialchars($student['email']); ?></span>
                        </div>
                        <div class="info-row">
                            <i class="fas fa-phone"></i>
                            <span><?php echo htmlspecialchars($student['number']); ?></span>
                        </div>
                        <div class="info-row">
                            <i class="fas fa-book"></i>
                            <span><?php echo strtoupper(htmlspecialchars($student['coursename'])); ?></span>
                        </div>
                        <div class="info-row">
                            <i class="fas fa-calendar"></i>
                            <span><?php echo htmlspecialchars($student['academicyear']); ?></span>
                        </div>
                        <div class="info-row">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo htmlspecialchars($student['city']); ?></span>
                        </div>
                        
                        <!-- Payment Info -->
                        <div class="payment-info-card">
                            <div class="payment-summary">
                                <div class="payment-stat">
                                    <i class="fas fa-receipt"></i>
                                    <div>
                                        <strong><?php echo $student['payment_count']; ?></strong>
                                        <span>Payments</span>
                                    </div>
                                </div>
                                <?php if ($last_payment_info): ?>
                                <div class="payment-stat">
                                    <i class="fas fa-clock"></i>
                                    <div>
                                        <strong><?php echo $last_payment_info; ?></strong>
                                        <span>Last Payment</span>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="fee-details">
                            <div class="fee-row">
                                <span>Total Fees:</span>
                                <strong>₹<?php echo number_format($student['total_fees']); ?></strong>
                            </div>
                            <div class="fee-row">
                                <span>Due Fees:</span>
                                <strong class="<?php echo $status === 'paid' ? 'text-success' : 'text-danger'; ?>">
                                    ₹<?php echo number_format($student['due_fees']); ?>
                                </strong>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                            </div>
                            <p class="progress-text"><?php echo round($progress); ?>% Paid</p>
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <a href="edit_student.php?id=<?php echo $student['s_id']; ?>" class="btn-card btn-edit">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="student_payment_history.php?id=<?php echo $student['s_id']; ?>" class="btn-card btn-history">
                            <i class="fas fa-history"></i> History
                        </a>
                        <button onclick="deleteStudent(<?php echo $student['s_id']; ?>, '<?php echo htmlspecialchars($student['fullname']); ?>')" 
                                class="btn-card btn-delete">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <div id="noResultsCard" class="no-results" style="display: none;">
                    <i class="fas fa-search"></i>
                    <p>No students found matching your criteria.</p>
                </div>
            </div>
        </div>
    </main>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Confirm Delete</h2>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <i class="fas fa-exclamation-triangle warning-icon"></i>
                <p>Are you sure you want to delete <strong id="studentNameModal"></strong>?</p>
                <p class="warning-text">This action will delete the student and all associated payment records. This cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" id="cancelDelete">Cancel</button>
                <form method="POST" id="deleteForm" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="student_id" id="deleteStudentId">
                    <button type="submit" class="btn-confirm-delete">Delete</button>
                </form>
            </div>
        </div>
    </div>

    <script src="admin.js"></script>
</body>
</html>