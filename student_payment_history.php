<?php
/**
 * Student Payment History - Admin View
 * Allows admins to view detailed payment history for a specific student
 */

// Start session and check authentication
session_start();

// Check if college admin is logged in
if (!isset($_SESSION['email']) || !isset($_SESSION['college_name'])) {
    header('Location: cllglogin.html');
    exit();
}

// Include database connection
require_once 'db_connect.php';

// Get student ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: admin.php');
    exit();
}

$student_id = intval($_GET['id']);
$college_email = $_SESSION['email'];
$admin_name = $_SESSION['admin_name'];

// Get college ID
$stmt = $conn->prepare("SELECT c_id FROM cllgsignup WHERE email = ?");
$stmt->bind_param("s", $college_email);
$stmt->execute();
$result = $stmt->get_result();
$college_data = $result->fetch_assoc();
$stmt->close();

if (!$college_data) {
    die("College not found.");
}

$c_id = $college_data['c_id'];

// Fetch student information (ensure they belong to this college)
$stmt = $conn->prepare("SELECT 
    s_id, fullname, email, number, coursename, currentyear, 
    academicyear, total_fees, due_fees, collegename
FROM signup 
WHERE s_id = ? AND c_id = ?");

$stmt->bind_param("ii", $student_id, $c_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if (!$student) {
    die("Student not found or access denied.");
}

// Calculate paid amount
$paid_amount = $student['total_fees'] - $student['due_fees'];

// Fetch payment history
$stmt = $conn->prepare("SELECT 
    payment_id, installment_no, amount, payment_date, 
    txn_id, payment_method, status
FROM payments 
WHERE s_id = ?
ORDER BY payment_date DESC, installment_no DESC");

$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$payments = [];
while ($row = $result->fetch_assoc()) {
    $payments[] = $row;
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History - <?php echo htmlspecialchars($student['fullname']); ?></title>
    <link rel="stylesheet" href="payment.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            background: var(--primary-color);
            color: var(--white);
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-bottom: 2rem;
        }
        
        .back-button:hover {
            background: #357abd;
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        .print-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            background: var(--success-color);
            color: var(--white);
            border: none;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-left: 1rem;
        }
        
        .print-button:hover {
            background: #229954;
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            
            .payment-header,
            .back-button,
            .print-button {
                display: none !important;
            }
            
            body {
                background: white;
            }
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <header class="payment-header no-print">
        <div class="header-content">
            <div class="header-left">
                <h1><i class="fas fa-history"></i> Payment History</h1>
                <p class="college-name"><?php echo htmlspecialchars($student['collegename']); ?></p>
            </div>
            <div class="header-right">
                <span class="user-name">
                    <i class="fas fa-user-shield"></i> Admin: <?php echo htmlspecialchars($admin_name); ?>
                </span>
                <a href="admin.php" class="btn-logout">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="payment-main">
        <div class="container">
            
            <div class="no-print">
                <a href="admin.php" class="back-button">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <button onclick="window.print()" class="print-button">
                    <i class="fas fa-print"></i> Print History
                </button>
            </div>
            
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
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($student['number']); ?></p>
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
                    <div class="fee-item">
                        <span class="fee-label">Total Payments</span>
                        <span class="fee-value" style="color: var(--primary-color);">
                            <?php echo count($payments); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Payment History -->
            <div class="payment-history-section">
                <h2><i class="fas fa-receipt"></i> Complete Payment History</h2>
                
                <?php if (empty($payments)): ?>
                    <div class="no-payments">
                        <i class="fas fa-inbox"></i>
                        <p>No payment history available for this student.</p>
                    </div>
                <?php else: ?>
                    <div class="history-table-container">
                        <table class="history-table">
                            <thead>
                                <tr>
                                    <th>Payment ID</th>
                                    <th>Date & Time</th>
                                    <th>Installment</th>
                                    <th>Amount</th>
                                    <th>Transaction ID</th>
                                    <th>Payment Method</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): 
                                    $status_class = 'status-' . strtolower($payment['status']);
                                    $date = date('d M Y', strtotime($payment['payment_date']));
                                    $time = date('h:i A', strtotime($payment['payment_date']));
                                ?>
                                <tr>
                                    <td><strong>#<?php echo $payment['payment_id']; ?></strong></td>
                                    <td>
                                        <strong><?php echo $date; ?></strong><br>
                                        <small><?php echo $time; ?></small>
                                    </td>
                                    <td><strong>Installment <?php echo $payment['installment_no']; ?></strong></td>
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
                            <tfoot>
                                <tr style="background: var(--light-bg); font-weight: bold;">
                                    <td colspan="3" style="text-align: right; padding: 1rem;">Total Paid:</td>
                                    <td class="amount-cell" style="font-size: 1.2rem;">
                                        ₹<?php echo number_format($paid_amount, 2); ?>
                                    </td>
                                    <td colspan="3"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>