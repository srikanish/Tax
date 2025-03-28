<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: dashboard.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root"; // Change to your database username
$password = ""; // Change to your database password
$dbname = "tax_syst";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Get form data
$assessment_year = $conn->real_escape_string($_POST['assessment_year']);
$age_category = $conn->real_escape_string($_POST['age_category']);
$filing_status = $conn->real_escape_string($_POST['filing_status']);
$tax_regime = $conn->real_escape_string($_POST['tax_regime']);
$gross_salary = floatval($_POST['gross_salary']);
$interest_income = floatval($_POST['interest_income'] ?? 0);
$rental_income = floatval($_POST['rental_income'] ?? 0);
$other_income = floatval($_POST['other_income'] ?? 0);
$deductions_80c = floatval($_POST['deductions_80c'] ?? 0);
$medical_insurance_80d = floatval($_POST['medical_insurance_80d'] ?? 0);
$home_loan_interest_self = floatval($_POST['home_loan_interest_self'] ?? 0);

// Calculate total income
$total_income = $gross_salary + $interest_income + $rental_income + $other_income;

// Calculate total deductions
$total_deductions = $deductions_80c + $medical_insurance_80d + $home_loan_interest_self;

// Calculate taxable income
$taxable_income = max(0, $total_income - $total_deductions);

// Calculate tax based on regime and age category
$tax_payable = calculateTax($taxable_income, $tax_regime, $age_category);

// Insert calculation into database
$sql = "INSERT INTO tax_calculations (
            user_id, assessment_year, age_category, filing_status, tax_regime,
            gross_salary, interest_income, rental_income, other_income,
            deductions_80c, medical_insurance_80d, home_loan_interest_self,
            total_income, total_deductions, taxable_income, tax_payable
        ) VALUES (
            $user_id, '$assessment_year', '$age_category', '$filing_status', '$tax_regime',
            $gross_salary, $interest_income, $rental_income, $other_income,
            $deductions_80c, $medical_insurance_80d, $home_loan_interest_self,
            $total_income, $total_deductions, $taxable_income, $tax_payable
        )";

if ($conn->query($sql) === TRUE) {
    // Redirect back to dashboard with success message
    $_SESSION['calculation_success'] = true;
    $_SESSION['tax_result'] = [
        'total_income' => $total_income,
        'total_deductions' => $total_deductions,
        'taxable_income' => $taxable_income,
        'tax_payable' => $tax_payable,
        'effective_rate' => ($taxable_income > 0) ? ($tax_payable / $taxable_income) * 100 : 0
    ];
    header("Location: dashboard.php?page=calculator&result=success");
} else {
    // Redirect back with error
    $_SESSION['calculation_error'] = "Error: " . $conn->error;
    header("Location: dashboard.php?page=calculator&result=error");
}

$conn->close();

// Function to calculate tax based on regime and age
function calculateTax($income, $regime, $age) {
    if ($regime == 'new') {
        // New tax regime rates (simplified)
        if ($income <= 300000) {
            return 0;
        } elseif ($income <= 600000) {
            return ($income - 300000) * 0.05;
        } elseif ($income <= 900000) {
            return 15000 + ($income - 600000) * 0.10;
        } elseif ($income <= 1200000) {
            return 45000 + ($income - 900000) * 0.15;
        } elseif ($income <= 1500000) {
            return 90000 + ($income - 1200000) * 0.20;
        } else {
            return 150000 + ($income - 1500000) * 0.30;
        }
    } else {
        // Old tax regime rates based on age
        if ($age == 'Below 60') {
            if ($income <= 250000) {
                return 0;
            } elseif ($income <= 500000) {
                return ($income - 250000) * 0.05;
            } elseif ($income <= 1000000) {
                return 12500 + ($income - 500000) * 0.20;
            } else {
                return 112500 + ($income - 1000000) * 0.30;
            }
        } elseif ($age == '60-80') {
            if ($income <= 300000) {
                return 0;
            } elseif ($income <= 500000) {
                return ($income - 300000) * 0.05;
            } elseif ($income <= 1000000) {
                return 10000 + ($income - 500000) * 0.20;
            } else {
                return 110000 + ($income - 1000000) * 0.30;
            }
        } else { // Above 80
            if ($income <= 500000) {
                return 0;
            } elseif ($income <= 1000000) {
                return ($income - 500000) * 0.20;
            } else {
                return 100000 + ($income - 1000000) * 0.30;
            }
        }
    }
}
?>

