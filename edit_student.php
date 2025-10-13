<?php
/**
 * Edit Student Page
 * Allows college admins to update student information
 */

session_start();

// Check authentication
if (!isset($_SESSION['email']) || !isset($_SESSION['college_name'])) {
    header('Location: cllglogin.html');
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "form";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$college_email = $_SESSION['email'];
$college_name = $_SESSION['college_name'];

// Get college ID
$stmt = $conn->prepare("SELECT c_id FROM cllgsignup WHERE email = ?");
$stmt->bind_param("s", $college_email);
$stmt->execute();
$result = $stmt->get_result();
$college_data = $result->fetch_assoc();
$c_id = $college_data['c_id'];
$stmt->close();

// Get student ID from URL
$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $mothername = trim($_POST['mothername']);
    $city = trim($_POST['city']);
    $email = trim($_POST['email']);
    $number = trim($_POST['number']);
    $gender = trim($_POST['gender']);
    $coursename = trim($_POST['coursename']);
    $currentyear = trim($_POST['currentyear']);
    $academicyear = trim($_POST['academicyear']);
    $total_fees = intval($_POST['total_fees']);
    $due_fees = intval($_POST['due_fees']);
    
    // Update student record (ensure student belongs to this college)
    $stmt = $conn->prepare("UPDATE signup SET 
        fullname = ?, mothername = ?, city = ?, email = ?, number = ?, 
        gender = ?, coursename = ?, currentyear = ?, academicyear = ?, 
        total_fees = ?, due_fees = ?
        WHERE s_id = ? AND c_id = ?");
    
    $stmt->bind_param("sssssssssddii", 
        $fullname, $mothername, $city, $email, $number, 
        $gender, $coursename, $currentyear, $academicyear, 
        $total_fees, $due_fees, $student_id, $c_id
    );
    
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header('Location: admin.php?updated=success');
        exit();
    } else {
        $error_message = "Error updating student: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch student data
$stmt = $conn->prepare("SELECT * FROM signup WHERE s_id = ? AND c_id = ?");
$stmt->bind_param("ii", $student_id, $c_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: admin.php');
    exit();
}

$student = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student - <?php echo htmlspecialchars($student['fullname']); ?></title>
    <link rel="stylesheet" href="signup.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 1rem;
        }
        
        .back-button {
            max-width: 900px;
            margin: 0 auto 1rem;
            display: inline-block;
            padding: 0.7rem 1.5rem;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .back-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .container header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div style="max-width: 900px; margin: 0 auto;">
        <a href="admin.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
    
    <div class="container">
        <header>Edit Student Information</header>
        
        <?php if (isset($error_message)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" autocomplete="off">
            <div class="fields">
                <div class="input-field">
                    <label>Full Name</label>
                    <input type="text" name="fullname" value="<?php echo htmlspecialchars($student['fullname']); ?>" required>
                </div>
                
                <div class="input-field">
                    <label>Mother's Name</label>
                    <input type="text" name="mothername" value="<?php echo htmlspecialchars($student['mothername']); ?>" required>
                </div>
                
                <div class="input-field">
                    <label>City</label>
                    <input type="text" name="city" value="<?php echo htmlspecialchars($student['city']); ?>" required>
                </div>
                
                <div class="input-field">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" required>
                </div>
                
                <div class="input-field">
                    <label>Mobile Number</label>
                    <input type="tel" name="number" value="<?php echo htmlspecialchars($student['number']); ?>" pattern="[0-9]{10}" required>
                </div>
                
                <div class="input-field">
                    <label>Gender</label>
                    <select name="gender" required>
                        <option value="male" <?php echo $student['gender'] === 'male' ? 'selected' : ''; ?>>Male</option>
                        <option value="female" <?php echo $student['gender'] === 'female' ? 'selected' : ''; ?>>Female</option>
                        <option value="others" <?php echo $student['gender'] === 'others' ? 'selected' : ''; ?>>Others</option>
                    </select>
                </div>
                
                <div class="input-field">
                    <label>Course Name</label>
                    <select name="coursename" id="courseSelect" required>
                        <option value="bba(ca)" <?php echo $student['coursename'] === 'bba(ca)' ? 'selected' : ''; ?>>BBA (CA)</option>
                        <option value="bcs" <?php echo $student['coursename'] === 'bcs' ? 'selected' : ''; ?>>BCS</option>
                        <option value="bba" <?php echo $student['coursename'] === 'bba' ? 'selected' : ''; ?>>BBA</option>
                        <option value="bca" <?php echo $student['coursename'] === 'bca' ? 'selected' : ''; ?>>BCA</option>
                        <option value="mba" <?php echo $student['coursename'] === 'mba' ? 'selected' : ''; ?>>MBA</option>
                        <option value="ba" <?php echo $student['coursename'] === 'ba' ? 'selected' : ''; ?>>BA</option>
                        <option value="bba(ib)" <?php echo $student['coursename'] === 'bba(ib)' ? 'selected' : ''; ?>>BBA (IB)</option>
                        <option value="mca" <?php echo $student['coursename'] === 'mca' ? 'selected' : ''; ?>>MCA</option>
                        <option value="mcs" <?php echo $student['coursename'] === 'mcs' ? 'selected' : ''; ?>>MCS</option>
                    </select>
                </div>
                
                <div class="input-field">
                    <label>Current Year</label>
                    <select name="currentyear" required>
                        <option value="fy" <?php echo $student['currentyear'] === 'fy' ? 'selected' : ''; ?>>First Year</option>
                        <option value="sy" <?php echo $student['currentyear'] === 'sy' ? 'selected' : ''; ?>>Second Year</option>
                        <option value="ty" <?php echo $student['currentyear'] === 'ty' ? 'selected' : ''; ?>>Third Year</option>
                        <option value="ly" <?php echo $student['currentyear'] === 'ly' ? 'selected' : ''; ?>>Last Year</option>
                    </select>
                </div>
                
                <div class="input-field">
                    <label>Academic Year</label>
                    <input type="text" name="academicyear" value="<?php echo htmlspecialchars($student['academicyear']); ?>" pattern="\d{4}-\d{4}" required>
                </div>
                
                <div class="input-field">
                    <label>Total Fees (₹)</label>
                    <input type="number" name="total_fees" id="total_fees" value="<?php echo $student['total_fees']; ?>" required readonly>
                </div>
                
                <div class="input-field">
                    <label>Due Fees (₹)</label>
                    <input type="number" name="due_fees" value="<?php echo $student['due_fees']; ?>" min="0" max="<?php echo $student['total_fees']; ?>" required>
                </div>
            </div>
            
            <div style="display: flex; gap: 1rem;">
                <button type="submit" class="submit" style="flex: 1;">
                    <span class="btnText">Update Student</span>
                    <i class="fas fa-save"></i>
                </button>
                <a href="admin.php" class="submit" style="flex: 1; background: #6c757d; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center;">
                    <span class="btnText">Cancel</span>
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>
    
    <script>
        // Auto-update total fees when course changes
        const courseSelect = document.getElementById('courseSelect');
        const totalFeesInput = document.getElementById('total_fees');
        
        const feesMap = {
            "bba(ca)": 60000,
            "bca": 60000,
            "bcs": 65000,
            "bba": 55000,
            "mba": 90000,
            "ba": 50000,
            "bba(ib)": 58000,
            "mca": 80000,
            "mcs": 75000
        };
        
        courseSelect.addEventListener('change', function() {
            const selectedCourse = this.value;
            const fees = feesMap[selectedCourse] || 0;
            totalFeesInput.value = fees;
        });
    </script>
</body>
</html>