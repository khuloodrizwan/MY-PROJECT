<?php
/**
 * PayCampus Payment Portal
 * Students can view fee details and make installment payments
 */

// Start session and check authentication
session_start();

// Check if student is logged in
if (!isset($_SESSION['email']) || !isset($_SESSION['s_id'])) {
    header('Location: login.html');
    exit();
}

// Include database connection
require_once 'db_connect.php';

$student_id = $_SESSION['s_id'];
$student_email = $_SESSION['email'];

// Fetch student information
$stmt = $conn->prepare("SELECT 
    s_id, fullname, mothername, city, email, number, gender, 
    collegename, coursename, total_fees, due_fees, 
    currentyear, academicyear, username, c_id
FROM signup 
WHERE s_id = ?");

if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if (!$student) {
    die("Student record not found.");
}

// Calculate paid amount
$paid_amount = $student['total_fees'] - $student['due_fees'];
$payment_progress = $student['total_fees'] > 0 ? 
    (($paid_amount / $student['total_fees']) * 100) : 0;

// Fetch payment history
$stmt = $conn->prepare("SELECT 
    p_id, installment_no, amount, payment_date, 
    txn_id, payment_method, status
FROM payments 
WHERE s_id = ?
ORDER BY payment_date DESC, installment_no DESC");

if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$payments = [];
while ($row = $result->fetch_assoc()) {
    $payments[] = $row;
}
$stmt->close();

// Calculate installment breakdown (assuming 4 installments)
$num_installments = 4;
$installment_amount = $student['total_fees'] / $num_installments;

// Get paid installment numbers
$paid_installments = [];
foreach ($payments as $payment) {
    if ($payment['status'] === 'Paid') {
        $paid_installments[] = $payment['installment_no'];
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.html');
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Portal - <?php echo htmlspecialchars($student['fullname']); ?></title>
    <link rel="stylesheet" href="payment.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Header Section -->
    <header class="payment-header">
        <div class="header-content">
            <div class="header-left">
                <h1><i class="fas fa-money-check-alt"></i> PayCampus Payment Portal</h1>
                <p class="college-name"><?php echo htmlspecialchars($student['collegename']); ?></p>
            </div>
            <div class="header-right">
                <span class="user-name">
                    <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($student['fullname']); ?>
                </span>
                <a href="?logout=true" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="payment-main">
        <div class="container">
            
            <!-- Student Info Card -->
            <div class="student-info-card">
                <div class="card-header">
                    <div class="student-avatar">
                        <?php echo strtoupper(substr($student['fullname'], 0, 2)); ?>
                    </div>
                    <div class="student-details">
                        <h2><?php echo htmlspecialchars($student['fullname']); ?></h2>
                        <p><strong>Student ID:</strong> <?php echo $student['s_id']; ?></p>
                        <p><strong>Course:</strong> <?php echo strtoupper(htmlspecialchars($student['coursename'])); ?> - <?php echo strtoupper(htmlspecialchars($student['currentyear'])); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
                        <p><strong>Academic Year:</strong> <?php echo htmlspecialchars($student['academicyear']); ?></p>
                    </div>
                </div>
                
                <div class="fee-summary">
                    <div class="fee-item">
                        <span class="fee-label">Total Fees</span>
                        <span class="fee-value total">₹<?php echo number_format($student['total_fees'], 2); ?></span>
                    </div>
                    <div class="fee-item">
                        <span class="fee-label">Paid Amount</span>
                        <span class="fee-value paid">₹<?php echo number_format($paid_amount, 2); ?></span>
                    </div>
                    <div class="fee-item">
                        <span class="fee-label">Due Amount</span>
                        <span class="fee-value due">₹<?php echo number_format($student['due_fees'], 2); ?></span>
                    </div>
                </div>
                
                <div class="progress-section">
                    <div class="progress-header">
                        <span>Payment Progress</span>
                        <span class="progress-percentage"><?php echo round($payment_progress, 1); ?>%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $payment_progress; ?>%"></div>
                    </div>
                </div>
            </div>

            <!-- Installments Section -->
            <div class="installments-section">
                <h2><i class="fas fa-receipt"></i> Installment Payments</h2>
                <p class="installment-note">Total fees divided into <?php echo $num_installments; ?> equal installments of ₹<?php echo number_format($installment_amount, 2); ?> each</p>
                
                <div class="installments-table-container">
                    <table class="installments-table">
                        <thead>
                            <tr>
                                <th>Installment</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for ($i = 1; $i <= $num_installments; $i++): 
                                $is_paid = in_array($i, $paid_installments);
                                $status_class = $is_paid ? 'status-paid' : 'status-pending';
                                $status_text = $is_paid ? 'Paid' : 'Pending';
                                $row_class = $is_paid ? 'row-paid' : '';
                            ?>
                            <tr class="<?php echo $row_class; ?>" data-installment="<?php echo $i; ?>">
                                <td>
                                    <i class="fas fa-file-invoice-dollar"></i> 
                                    Installment <?php echo $i; ?>
                                </td>
                                <td class="amount-cell">₹<?php echo number_format($installment_amount, 2); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <i class="fas <?php echo $is_paid ? 'fa-check-circle' : 'fa-clock'; ?>"></i>
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($is_paid): ?>
                                        <button class="btn-paid" disabled>
                                            <i class="fas fa-check"></i> Paid
                                        </button>
                                    <?php else: ?>
                                        <button class="btn-pay" 
                                                onclick="makePayment(<?php echo $i; ?>, <?php echo $installment_amount; ?>)"
                                                data-installment="<?php echo $i; ?>">
                                            <i class="fas fa-credit-card"></i> Pay Now
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Payment History -->
            <div class="payment-history-section">
                <h2><i class="fas fa-history"></i> Payment History</h2>
                
                <?php if (empty($payments)): ?>
                    <div class="no-payments">
                        <i class="fas fa-inbox"></i>
                        <p>No payment history available yet.</p>
                    </div>
                <?php else: ?>
                    <div class="history-table-container">
                        <table class="history-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Installment</th>
                                    <th>Amount</th>
                                    <th>Transaction ID</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): 
                                    $status_class = 'status-' . strtolower($payment['status']);
                                    $date = date('d M Y, h:i A', strtotime($payment['payment_date']));
                                ?>
                                <tr>
                                    <td><?php echo $date; ?></td>
                                    <td>Installment <?php echo $payment['installment_no']; ?></td>
                                    <td class="amount-cell">₹<?php echo number_format($payment['amount'], 2); ?></td>
                                    <td class="txn-id"><?php echo htmlspecialchars($payment['txn_id']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <?php echo $payment['status']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Toast Notification -->
    <div id="toast" class="toast"></div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay" style="display: none;">
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Processing Payment...</p>
        </div>
    </div>

    <!-- Hidden data for JavaScript -->
    <input type="hidden" id="studentId" value="<?php echo $student['s_id']; ?>">
    <input type="hidden" id="studentName" value="<?php echo htmlspecialchars($student['fullname']); ?>">
    <input type="hidden" id="studentEmail" value="<?php echo htmlspecialchars($student['email']); ?>">
    <input type="hidden" id="studentPhone" value="<?php echo htmlspecialchars($student['number']); ?>">

    <script src="payment.js"></script>
</body>
</html>