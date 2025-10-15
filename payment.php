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

// Fetch payment history - FIXED: Changed p_id to payment_id
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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #1e3a8a;
            --primary-light: #3b82f6;
            --secondary-color: #1e293b;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --light-bg: #f8fafc;
            --white: #ffffff;
            --border-color: #e2e8f0;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc;
            min-height: 100vh;
            color: #0f172a;
            line-height: 1.6;
        }

        /* Header */
        .payment-header {
            background: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1.25rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 3px solid #1e3a8a;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header-left h1 {
            font-size: 1.75rem;
            color: #1e3a8a;
            margin-bottom: 0.25rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .college-name {
            color: #64748b;
            font-size: 0.95rem;
            font-weight: 500;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .user-name {
            color: #0f172a;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-logout {
            padding: 0.625rem 1.25rem;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-logout:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        /* Main Content */
        .payment-main {
            padding: 2.5rem 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        /* Student Info Card */
        .student-info-card {
            background: white;
            border-radius: 12px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
        }

        .card-header {
            display: flex;
            align-items: flex-start;
            gap: 2rem;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 2px solid #f8fafc;
        }

        .student-avatar {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, #1e3a8a, #3b82f6);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.25rem;
            font-weight: 700;
            flex-shrink: 0;
            box-shadow: 0 4px 6px -1px rgba(30, 58, 138, 0.3);
        }

        .student-details {
            flex: 1;
        }

        .student-details h2 {
            font-size: 2rem;
            color: #1e293b;
            margin-bottom: 0.75rem;
            font-weight: 700;
        }

        .student-details p {
            color: #64748b;
            margin: 0.5rem 0;
            font-size: 1rem;
        }

        .student-details strong {
            color: #0f172a;
            font-weight: 600;
            display: inline-block;
            min-width: 140px;
        }

        /* Fee Summary */
        .fee-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .fee-item {
            background: #f8fafc;
            padding: 1.75rem;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .fee-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .fee-label {
            color: #64748b;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: block;
            margin-bottom: 0.75rem;
        }

        .fee-value {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1;
            display: block;
        }

        .fee-value.total {
            color: #1e3a8a;
        }

        .fee-value.paid {
            color: #10b981;
        }

        .fee-value.due {
            color: #ef4444;
        }

        /* Progress Section */
        .progress-section {
            margin-top: 1.5rem;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
            font-weight: 600;
        }

        .progress-percentage {
            color: #1e3a8a;
            font-size: 1.25rem;
            font-weight: 700;
        }

        .progress-bar {
            height: 14px;
            background: #e2e8f0;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.06);
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981, #34d399);
            transition: width 0.8s ease;
            box-shadow: 0 2px 4px rgba(16, 185, 129, 0.4);
        }

        /* Installments Section */
        .installments-section {
            background: white;
            border-radius: 12px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
        }

        .installments-section h2 {
            color: #1e293b;
            margin-bottom: 0.5rem;
            font-weight: 700;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .installment-note {
            color: #64748b;
            margin-bottom: 2rem;
            font-size: 0.95rem;
            padding: 1rem;
            background: #f8fafc;
            border-left: 4px solid #1e3a8a;
            border-radius: 4px;
        }

        .installments-table-container {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .installments-table {
            width: 100%;
            border-collapse: collapse;
        }

        .installments-table thead {
            background: #1e3a8a;
        }

        .installments-table th {
            padding: 1.25rem 1.5rem;
            text-align: left;
            font-weight: 600;
            color: white;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .installments-table td {
            padding: 1.5rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        .installments-table tbody tr {
            background: white;
            transition: background 0.2s ease;
        }

        .installments-table tbody tr:hover {
            background: #f8fafc;
        }

        .installments-table tr.row-paid {
            background: #ecfdf5;
        }

        .amount-cell {
            font-weight: 700;
            color: #1e3a8a;
            font-size: 1.125rem;
        }

        /* Status Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .status-badge.status-paid {
            background: #d1fae5;
            color: #065f46;
        }

        .status-badge.status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        /* Buttons */
        .btn-pay, .btn-paid {
            padding: 0.75rem 1.75rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
        }

        .btn-pay {
            background: #1e3a8a;
            color: white;
            box-shadow: 0 4px 6px -1px rgba(30, 58, 138, 0.3);
        }

        .btn-pay:hover {
            background: #1e40af;
            transform: translateY(-2px);
        }

        .btn-paid {
            background: #10b981;
            color: white;
            cursor: default;
        }

        /* Payment History */
        .payment-history-section {
            background: white;
            border-radius: 12px;
            padding: 2.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
        }

        .payment-history-section h2 {
            color: #1e293b;
            margin-bottom: 2rem;
            font-weight: 700;
            font-size: 1.5rem;
        }

        .no-payments {
            text-align: center;
            padding: 4rem 1rem;
            color: #64748b;
        }

        .no-payments i {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            opacity: 0.2;
        }

        .history-table-container {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
        }

        .history-table thead {
            background: #f8fafc;
        }

        .history-table th {
            padding: 1.25rem 1.5rem;
            text-align: left;
            font-weight: 600;
            color: #1e293b;
            font-size: 0.875rem;
            text-transform: uppercase;
            border-bottom: 2px solid #e2e8f0;
        }

        .history-table td {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .history-table tbody tr:hover {
            background: #f8fafc;
        }

        .txn-id {
            font-family: 'Courier New', monospace;
            font-size: 0.875rem;
            color: #64748b;
            background: #f8fafc;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
        }

        /* Toast */
        .toast {
            position: fixed;
            top: 100px;
            right: 20px;
            padding: 1.25rem 1.75rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            transform: translateX(400px);
            transition: transform 0.4s ease;
            z-index: 1000;
            min-width: 350px;
            border: 1px solid #e2e8f0;
        }

        .toast.show {
            transform: translateX(0);
        }

        .toast.success {
            border-left: 5px solid #10b981;
        }

        .toast.error {
            border-left: 5px solid #ef4444;
        }

        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
        }

        .loading-spinner {
            background: white;
            padding: 3rem 4rem;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .loading-spinner i {
            font-size: 3.5rem;
            color: #1e3a8a;
            margin-bottom: 1.5rem;
        }

        .loading-spinner p {
            color: #0f172a;
            font-weight: 600;
            font-size: 1.125rem;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
            }
            
            .card-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            
            .fee-summary {
                grid-template-columns: 1fr;
            }
        }
    
    </style>

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
                        <p><strong>Student ID:</strong><?php echo $student['s_id']; ?></p>
                        <p><strong>Course:</strong><?php echo strtoupper(htmlspecialchars($student['coursename'])); ?> - <?php echo strtoupper(htmlspecialchars($student['currentyear'])); ?></p>
                        <p><strong>Email:</strong><?php echo htmlspecialchars($student['email']); ?></p>
                        <p><strong>Academic Year:</strong><?php echo htmlspecialchars($student['academicyear']); ?></p>
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