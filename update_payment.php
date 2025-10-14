<?php
/**
 * PayCampus Payment Processing Handler
 * Processes installment payments and updates database
 */

// Start session
session_start();

// Set JSON response header
header('Content-Type: application/json');

// Check if student is logged in
if (!isset($_SESSION['email']) || !isset($_SESSION['s_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access. Please login again.'
    ]);
    exit();
}

// Include database connection
require_once 'db_connect.php';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
    exit();
}

// Get and validate input
$student_id = $_SESSION['s_id'];
$installment_no = isset($_POST['installment_no']) ? intval($_POST['installment_no']) : 0;
$amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
$txn_id = isset($_POST['txn_id']) ? trim($_POST['txn_id']) : '';
$payment_method = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : 'Simulated';

// Validate inputs
if ($installment_no <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid installment number.']);
    exit();
}

if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment amount.']);
    exit();
}

if (empty($txn_id)) {
    echo json_encode(['success' => false, 'message' => 'Transaction ID is required.']);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Get student info including c_id
    $stmt = $conn->prepare("SELECT c_id, total_fees, due_fees FROM signup WHERE s_id = ?");
    if (!$stmt) throw new Exception("Database error: " . $conn->error);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();

    if (!$student) throw new Exception("Student record not found.");

    $c_id = $student['c_id'] ?? 0; // default to 0 if null
    $total_fees = $student['total_fees'];
    $current_due_fees = $student['due_fees'];

    // Check if this installment is already paid
    $stmt = $conn->prepare("SELECT p_id FROM payments WHERE s_id = ? AND installment_no = ? AND status = 'Paid'");
    if (!$stmt) throw new Exception("Database error: " . $conn->error);
    $stmt->bind_param("ii", $student_id, $installment_no);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $stmt->close();
        throw new Exception("This installment has already been paid.");
    }
    $stmt->close();

    // Check if amount exceeds due
    if ($amount > $current_due_fees) {
        throw new Exception("Payment amount (₹" . number_format($amount, 2) . ") exceeds due fees (₹" . number_format($current_due_fees, 2) . ")");
    }

    // Check duplicate txn_id
    $stmt = $conn->prepare("SELECT p_id FROM payments WHERE txn_id = ?");
    if (!$stmt) throw new Exception("Database error: " . $conn->error);
    $stmt->bind_param("s", $txn_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $stmt->close();
        throw new Exception("Duplicate transaction. Payment already processed.");
    }
    $stmt->close();

    // Insert payment
    $payment_date = date('Y-m-d H:i:s');
    $status = 'Paid';

    $stmt = $conn->prepare("INSERT INTO payments (s_id, c_id, installment_no, amount, payment_date, txn_id, payment_method, status) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) throw new Exception("Database error: " . $conn->error);
    $stmt->bind_param("iiidssss", $student_id, $c_id, $installment_no, $amount, $payment_date, $txn_id, $payment_method, $status);
    if (!$stmt->execute()) throw new Exception("Failed to record payment: " . $stmt->error);

    $payment_id = $conn->insert_id;
    $stmt->close();

    // Update student's due_fees
    $new_due_fees = $current_due_fees - $amount;
    $stmt = $conn->prepare("UPDATE signup SET due_fees = ? WHERE s_id = ?");
    if (!$stmt) throw new Exception("Database error: " . $conn->error);
    $stmt->bind_param("di", $new_due_fees, $student_id);
    if (!$stmt->execute()) throw new Exception("Failed to update student fees: " . $stmt->error);
    $stmt->close();

    // Commit
    $conn->commit();

    $paid_amount = $total_fees - $new_due_fees;
    $payment_progress = $total_fees > 0 ? (($paid_amount / $total_fees) * 100) : 0;
    $formatted_date = date('d M Y, h:i A', strtotime($payment_date));

    echo json_encode([
        'success' => true,
        'message' => 'Payment successful! Installment ' . $installment_no . ' has been paid.',
        'p_id' => $p_id,
        'new_due_fees' => $new_due_fees,
        'paid_amount' => $paid_amount,
        'payment_progress' => round($payment_progress, 2),
        'payment_details' => [
            'installment_no' => $installment_no,
            'amount' => $amount,
            'date' => $formatted_date,
            'txn_id' => $txn_id,
            'payment_method' => $payment_method,
            'status' => $status
        ]
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>
