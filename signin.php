<?php
$servername = "localhost";
$username = "root"; // Default MySQL username
$password = ""; // Default MySQL password (empty in XAMPP)
$dbname = "paycampus"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST["fullname"];
    $email = $_POST["email"];
    $mobile_number = $_POST["mobilenumber"];
    $dob = date("Y-m-d", strtotime($_POST["dob"]));


    $gender = $_POST["gender"];
    $mother_name = $_POST["mothername"];
    $college_name = $_POST["collegename"];
    $course_name = $_POST["Coursename"];
    $current_year = $_POST["Currentyear"];
    $academic_year = $_POST["Academicyear"];
    $student_id = $_POST["studentid"];
    $college_id = $_POST["cllgid"];
    $address_type = $_POST["addresstype"];
    $district = $_POST["district"];
    $pin_number = $_POST["pin_number"];
    $nationality = $_POST["nationality"];
    $state = $_POST["state"];
    $nearest_landmark = $_POST["landmark"];
    $username = $_POST["username"];
    $password = $_POST["password"];
    $confirm_password = $_POST["confirmpassword"];
    $bank_name = $_POST["bankname"];
    $payment_type = $_POST["paymenttype"];
    $account_creation_date = date("Y-m-d", strtotime($_POST["Creationdate"]));

    // Check if passwords match
    if ($password !== $confirm_password) {
        die("Error: Passwords do not match!");
    }

    // Hash the password before storing
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    $sql = "INSERT INTO users (full_name, email, mobile_number, dob, gender, mother_name, college_name, course_name, current_year, academic_year, student_id, college_id, address_type, district, pin_number, nationality, state, nearest_landmark, username, password, bank_name, payment_type, account_creation_date) 
            VALUES ('$full_name', '$email', '$mobile_number', '$dob', '$gender', '$mother_name', '$college_name', '$course_name', '$current_year', '$academic_year', '$student_id', '$college_id', '$address_type', '$district', '$pin_number', '$nationality', '$state', '$nearest_landmark', '$username', '$hashed_password', '$bank_name', '$payment_type', '$account_creation_date')";

    if ($conn->query($sql) === TRUE) {
        echo "Registration successful!";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

$conn->close();
?>
