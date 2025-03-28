<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'connect.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: register.php");
    exit();
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $assessment_year = "2025-2026"; 
    $age_category = $_POST["age_category"] ?? "";
    $filing_status = $_POST["filing_status"] ?? "";
    $tax_regime = $_POST["tax_regime"] ?? "";
    $gross_salary = floatval($_POST["gross_salary"] ?? 0);
    $interest_income = floatval($_POST["interest_income"] ?? 0);
    $rental_income = floatval($_POST["rental_income"] ?? 0);
    $digital_assets_income = floatval($_POST["digital_assets_income"] ?? 0);
    $exempt_allowances = floatval($_POST["exempt_allowances"] ?? 0);
    $home_loan_interest_self = floatval($_POST["home_loan_interest_self"] ?? 0);
    $home_loan_interest_let = floatval($_POST["home_loan_interest_let"] ?? 0);
    $other_income = floatval($_POST["other_income"] ?? 0);
    $deductions_80c = floatval($_POST["deductions_80c"] ?? 0);
    $medical_insurance_80d = floatval($_POST["medical_insurance_80d"] ?? 0);
    $interest_deposits_80tta = floatval($_POST["interest_deposits_80tta"] ?? 0);
    $charity_donation_80g = floatval($_POST["charity_donation_80g"] ?? 0);
    $housing_loan_80eea = floatval($_POST["housing_loan_80eea"] ?? 0);
    $nps_employee_80ccd = floatval($_POST["nps_employee_80ccd"] ?? 0);
    $nps_employer_80ccd2 = floatval($_POST["nps_employer_80ccd2"] ?? 0);
    $other_deductions = floatval($_POST["other_deductions"] ?? 0);
    $standard_deduction = 50000; 
    $total_income = $gross_salary + $interest_income + $rental_income + $digital_assets_income + $other_income - $exempt_allowances - $home_loan_interest_self - $home_loan_interest_let;
    $total_deductions = $deductions_80c + $medical_insurance_80d + $interest_deposits_80tta + $charity_donation_80g + $housing_loan_80eea + $nps_employee_80ccd +$nps_employer_80ccd2 + $other_deductions + $standard_deduction;
    $taxable_income = max(0, $total_income - $total_deductions);
    $tax = 0;
    if ($tax_regime == "Old Regime") {
        if ($age_category == "Below 60") {
            $tax = calculateOldTaxSlab($taxable_income);
        } elseif ($age_category == "60 to Above 80") {
            $tax = calculateOldTaxSlabSenior($taxable_income);
        } else {
            $tax = calculateOldTaxSlabSuperSenior($taxable_income);
        }
    } else {
        $tax = calculateNewTaxSlab($taxable_income);
    }
    $sql = "INSERT INTO tax_calculations (
                user_id, assessment_year, age_category, filing_status, tax_regime,
                gross_salary, interest_income, rental_income, digital_assets_income, 
                exempt_allowances, home_loan_interest_self, home_loan_interest_let, other_income,
                deductions_80c, medical_insurance_80d, interest_deposits_80tta, charity_donation_80g,
                housing_loan_80eea, nps_employee_80ccd, nps_employer_80ccd2, other_deductions,
                total_income, total_deductions, taxable_income, tax_payable
            ) VALUES (
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?
            )";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("SQL Error: " . $conn->error); 
    }
    $stmt->bind_param(
        "issssdddddddddddddddddddd",
        $user_id, $assessment_year, $age_category, $filing_status, $tax_regime,
        $gross_salary, $interest_income, $rental_income, $digital_assets_income,
        $exempt_allowances, $home_loan_interest_self, $home_loan_interest_let, $other_income,
        $deductions_80c, $medical_insurance_80d, $interest_deposits_80tta, $charity_donation_80g,
        $housing_loan_80eea, $nps_employee_80ccd, $nps_employer_80ccd2, $other_deductions,
        $total_income, $total_deductions, $taxable_income, $tax
    );
    if ($stmt->execute()) {
        $calculation_id = $conn->insert_id;
        $_SESSION['calculation_id'] = $calculation_id;
        $_SESSION['tax_payable'] = $tax;
        $_SESSION['taxable_income'] = $taxable_income;
        $_SESSION['total_income'] = $total_income;
        $_SESSION['total_deductions'] = $total_deductions;
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            echo json_encode([
                'success' => true,
                'tax_payable' => $tax,
                'taxable_income' => $taxable_income,
                'total_income' => $total_income,
                'total_deductions' => $total_deductions
            ]);
            exit;
        } else {
            header("Location: output.php");
            exit();
        }
    } else {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
            exit;
        } else {
            $_SESSION['error'] = "Error saving calculation: " . $stmt->error;
            header("Location: incomedetails.php");
            exit();
        }
    }
}function calculateOldTaxSlab($income) {
    if ($income <= 250000) return 0;
    if ($income <= 500000) return ($income - 250000) * 0.05;
    if ($income <= 1000000) return ($income - 500000) * 0.2 + 12500;
    return ($income - 1000000) * 0.3 + 112500;
}function calculateOldTaxSlabSenior($income) {
    if ($income <= 300000) return 0;
    if ($income <= 500000) return ($income - 300000) * 0.05;
    if ($income <= 1000000) return ($income - 500000) * 0.2 + 10000;
    return ($income - 1000000) * 0.3 + 110000;
}function calculateOldTaxSlabSuperSenior($income) {
    if ($income <= 500000) return 0;
    if ($income <= 1000000) return ($income - 500000) * 0.2;
    return ($income - 1000000) * 0.3 + 100000;
}function calculateNewTaxSlab($income) {
    if ($income <= 250000) return 0;
    if ($income <= 500000) return ($income - 250000) * 0.05;
    if ($income <= 750000) return ($income - 500000) * 0.1 + 12500;
    if ($income <= 1000000) return ($income - 750000) * 0.15 + 37500;
    if ($income <= 1250000) return ($income - 1000000) * 0.2 + 75000;
    if ($income <= 1500000) return ($income - 1250000) * 0.25 + 125000;
    return ($income - 1500000) * 0.3 + 187500;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tax Mate</title>
    <link rel="icon" href=".\Images\Logo_dark.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href=".\CSS\incomedetails.css">
</head>
<body>
    <div class="container">
        <h1>Income Tax Calculator <i class="fa-solid fa-coins "></i></h1>
        <form id="taxCalculatorForm" method="post" action="incomedetails.php">
            <div class="tab-navigation">
                <div class="tab-item active" data-tab="basic-details">Basic details</div>
                <div class="tab-item" data-tab="income">Income</div>
                <div class="tab-item" data-tab="deductions">Deductions</div>
            </div>
            <div id="basic-details" class="tab-content active">
                <div class="personal_left">
                    <h4>Basic details</h4>
                    <div class="mb-3">
                        <label for="assessmentYear" class="form-label">Assessment year</label>
                        <input type="text" id="assessmentYear" name="assessment_year" class="form-control" value="2025 - 2026" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Age category</label>
                        <select class="form-select" name="age_category" required>
                            <option value="">Choose...</option>
                            <option value="Below 60">Below 60</option>
                            <option value="60 to Above 80">60 to Above 80</option>
                            <option value="Above 80">Above 80</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Filing Status</label>
                        <select class="form-select" name="filing_status" required>
                            <option value="">Choose...</option>
                            <option value="Individual">Individual</option>
                            <option value="Business">Business</option>
                            <option value="Self-Employed">Self-Employed</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Preferred Tax Regime</label>
                        <select class="form-select" name="tax_regime" required>
                            <option value="">Choose...</option>
                            <option value="Old Regime">Old Regime</option>
                            <option value="New Regime">New Regime</option>
                        </select>
                    </div>
                </div>
                <div class="btn-container">
                    <button type="button" class="next-btn">Next</button>
                </div>
            </div>
            <div id="income" class="tab-content">
                <div class="Personal1">
                    <h4>Income</h4>
                    <div class="mb-3">
                        <label for="gross_salary" class="form-label">Income from Salary</label>
                        <input type="number" id="gross_salary" name="gross_salary" class="form-control" placeholder="Enter Gross Salary Income">
                    </div>
                    <div class="mb-3">
                        <label for="interest_income" class="form-label">Income from interest</label>
                        <input type="number" id="interest_income" name="interest_income" class="form-control" placeholder="Enter Income from Interest">
                    </div>
                    <div class="mb-3">
                        <label for="rental_income" class="form-label">Rental income received</label>
                        <input type="number" id="rental_income" name="rental_income" class="form-control" placeholder="Enter Rental Income">
                    </div>
                    <div class="mb-3">
                        <label for="digital_assets_income" class="form-label">Income from digital assets</label>
                        <input type="number" id="digital_assets_income" name="digital_assets_income" class="form-control" placeholder="Enter Income from Digital Assets">
                    </div>
                    <div class="mb-3">
                        <label for="exempt_allowances" class="form-label">Exempt allowances</label>
                        <input type="number" id="exempt_allowances" name="exempt_allowances" class="form-control" placeholder="Enter Exempt Allowances">
                    </div>
                    <div class="mb-3">
                        <label for="home_loan_interest_self">Interest on home loan - Self occupied</label>
                        <input type="number" id="home_loan_interest_self" name="home_loan_interest_self" class="form-control" placeholder="Enter Home Loan Interest - Self Occupied">
                    </div>
                    <div class="mb-3">
                        <label for="home_loan_interest_let">Interest on Home Loan- Let Out</label>
                        <input type="number" id="home_loan_interest_let" name="home_loan_interest_let" class="form-control" placeholder="Enter Home Loan Interest - Let Out">
                    </div>
                    <div class="mb-3">
                        <label for="other_income">Other income</label>
                        <input type="number" id="other_income" name="other_income" class="form-control" placeholder="Enter Other Income">
                    </div>
                    <div class="btn-container">
                        <button type="button" class="next-btn">Next</button>
                    </div>
                </div>
            </div>
            <div id="deductions" class="tab-content">
                <div class="Personal1">
                    <h4>Deductions</h4>
                    <div class="mb-3">
                        <label for="deductions_80c" class="form-label">Basic deductions - 80C:</label>
                        <input type="number" id="deductions_80c" name="deductions_80c" class="form-control" placeholder="Enter Basic deductions - 80C">
                    </div>
                    <div class="mb-3">
                        <label for="medical_insurance_80d" class="form-label">Medical insurance - 80D:</label>
                        <input type="number" id="medical_insurance_80d" name="medical_insurance_80d" class="form-control" placeholder="Enter Medical insurance - 80D">
                    </div>
                    <div class="mb-3">
                        <label for="interest_deposits_80tta" class="form-label">Interest from deposits - 80TTA:</label>
                        <input type="number" id="interest_deposits_80tta" name="interest_deposits_80tta" class="form-control" placeholder="Enter Interest from deposits - 80TTA">
                    </div>
                    <div class="mb-3">
                        <label for="charity_donation_80g" class="form-label">Donations to charity - 80G:</label>
                        <input type="number" id="charity_donation_80g" name="charity_donation_80g" class="form-control" placeholder="Enter Donations to charity - 80G">
                    </div>
                    <div class="mb-3">
                        <label for="housing_loan_80eea" class="form-label">Interest on housing loan - 80EEA:</label>
                        <input type="number" id="housing_loan_80eea" name="housing_loan_80eea" class="form-control" placeholder="Enter Interest on housing loan - 80EEA">
                    </div>
                    <div class="mb-3">
                        <label for="nps_employee_80ccd" class="form-label">Employee's contribution to NPS - 80CCD:</label>
                        <input type="number" id="nps_employee_80ccd" name="nps_employee_80ccd" class="form-control" placeholder="Enter Employee's contribution to NPS - 80CCD">
                    </div>
                    <div class="mb-3">
                        <label for="nps_employer_80ccd2" class="form-label">Employer's contribution to NPS - 80CCD(2):</label>
                        <input type="number" id="nps_employer_80ccd2" name="nps_employer_80ccd2" class="form-control" placeholder="Enter Employer's contribution to NPS - 80CCD(2)">
                    </div>
                    <div class="mb-3">
                        <label for="other_deductions" class="form-label">Any other deduction:</label>
                        <input type="number" id="other_deductions" name="other_deductions" class="form-control" placeholder="Enter Any other deduction">
                    </div>
                    <div class="btn-container">
                        <button type="button" id="calculateTaxBtn">Calculate Tax</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tabs = document.querySelectorAll('.tab-item');
        const tabContents = document.querySelectorAll('.tab-content');
        const nextButtons = document.querySelectorAll('.next-btn');
        const calculateTaxBtn = document.getElementById('calculateTaxBtn');
        const taxForm = document.getElementById('taxCalculatorForm');
        function showTab(tabId) {
            tabContents.forEach(content => content.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
        }
        tabs.forEach(tab => {
            tab.addEventListener('click', function () {
                tabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                showTab(this.getAttribute('data-tab'));
            });
        });
        nextButtons.forEach((button, index) => {
            button.addEventListener('click', function () {
                if (index < tabs.length - 1) {
                    tabs[index + 1].click(); 
                }
            });
        });
        if (calculateTaxBtn) {
            calculateTaxBtn.addEventListener('click', function() {
                taxForm.submit();
            });
        }
    });
</script>
</body>
</html>