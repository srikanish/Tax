<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: register.php");
    exit();
}
include 'connect.php';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}function imageExists($path) {
    if(empty($path)) return false;
    return file_exists($path) && is_file($path);
}
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = $user_id";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    echo "User not found";
    exit();
}
$sql = "SELECT * FROM tax_calculations WHERE user_id = $user_id ORDER BY created_at DESC";
$tax_result = $conn->query($sql);
if (isset($_POST['update_profile'])) {
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $dob = $conn->real_escape_string($_POST['dob']);
    $address = $conn->real_escape_string($_POST['address']);
    $aadhar_number = $conn->real_escape_string($_POST['aadhar_number']);
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $imageFileType = strtolower(pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION));
        $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $imageFileType;
        $target_file = $target_dir . $new_filename;
        $check = getimagesize($_FILES["profile_image"]["tmp_name"]);
        if($check !== false) {
            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                $profile_image = $new_filename;
                            $update_sql = "UPDATE users SET full_name='$full_name', email='$email', phone='$phone', 
                           dob='$dob', address='$address', aadhar_number='$aadhar_number', 
                           profile_image='$profile_image' WHERE id=$user_id";
                
                if ($conn->query($update_sql) === TRUE) {
                    $result = $conn->query("SELECT * FROM users WHERE id = $user_id");
                    $user = $result->fetch_assoc();
                    $update_success = true;
                } else {
                    $update_error = "Error updating profile: " . $conn->error;
                }
            } else {
                $update_error = "Sorry, there was an error uploading your file.";
            }
        } else {
            $update_error = "File is not an image.";
        }
    } else {
        $update_sql = "UPDATE users SET full_name='$full_name', email='$email', phone='$phone', 
                   dob='$dob', address='$address', aadhar_number='$aadhar_number' 
                   WHERE id=$user_id";
        if ($conn->query($update_sql) === TRUE) {
            $result = $conn->query("SELECT * FROM users WHERE id = $user_id");
            $user = $result->fetch_assoc();
            $update_success = true;
        } else {
            $update_error = "Error updating profile: " . $conn->error;
        }
    }
}
function formatCurrency($amount) {
    return '₹' . number_format($amount, 2);
}
$total_tax = 0;
$pending_returns = 0;
$last_calculation_date = "N/A";
if ($tax_result->num_rows > 0) {
    $tax_calculations = [];
    while($row = $tax_result->fetch_assoc()) {
        $tax_calculations[] = $row;
    }    
    if (!empty($tax_calculations)) {
        $last_calculation_date = date("F j, Y", strtotime($tax_calculations[0]['created_at']));
        $current_year = date("Y");
        foreach ($tax_calculations as $calc) {
            if (substr($calc['assessment_year'], 0, 4) == $current_year) {
                $total_tax += $calc['tax_payable'];
            }
            if ($calc['tax_payable'] > 0) {
                $pending_returns++;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tax Mate - Your Tax Dashboard</title>
    <link rel="stylesheet" href="./CSS/dashboard.css" />
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet"/>
    <style>
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: var(--border-radius);
        }
        .alert-success {
            background-color: var(--color-success);
            color: white;
        }
        .alert-danger {
            background-color: var(--color-danger);
            color: white;
        }
        .profile-pic-section {
            text-align: center;
            margin-bottom: 20px;
        }
        #profile-pic {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 15px;
            border: 3px solid var(--color-white);
        }
        .profile-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }
        @media (min-width: 768px) {
            .profile-container {
                grid-template-columns: 250px 1fr;
            }
        }
    </style>
</head>
<body>
    <?php if(isset($_GET['debug'])): ?>
    <div style="background: #f8f9fa; padding: 10px; margin: 10px; border: 1px solid #ddd;">
        <h4>Debug Info:</h4>
        <p>Profile Image Path: <?php echo htmlspecialchars($user['profile_image'] ?? 'Not set'); ?></p>
        <p>Image Exists: <?php echo !empty($user['profile_image']) && imageExists("uploads/" . $user['profile_image']) ? 'Yes' : 'No'; ?></p>
        <p>Default Path: ./Images/user_img.webp</p>
        <p>Default Exists: <?php echo file_exists('./Images/user_img.webp') ? 'Yes' : 'No'; ?></p>
    </div>
    <?php endif; ?>
    <section class="sidebar">
        <div class="nav-header">
            <p class="logo">Tax<span> Mate</span></p>
            <i class="bx bx-menu btn-menu"></i>
        </div>
        <ul class="nav-links">
            <li data-page="home" class="active">
                <a href="#">
                    <i class="bx bx-home-alt-2"></i>
                    <span class="title">Home</span>
                </a>
                <span class="tooltip">Home</span>
            </li>
            <li data-page="profile">
                <a href="#">
                    <i class='bx bxs-user-detail'></i>            
                    <span class="title">User Details</span>
                </a>
                <span class="tooltip">User Details</span>
            </li>
            <li data-page="history">
                <a href="#">
                    <i class='bx bxs-calculator'></i>
                    <span class="title">Previous Calculations</span>
                </a>
                <span class="tooltip">Previous Calculations</span>
            </li>
            <li data-page="calculator">
                <a href="./incomedetails.php">
                    <i class='bx bx-calculator'></i>
                    <span class="title">Calculate New Tax</span>
                </a>
                <span class="tooltip">Calculate New Tax</span>
            </li>
            <li data-page="reports">
                <a href="#">
                    <i class='bx bxs-report'></i>
                    <span class="title">Download Report</span>
                </a>
                <span class="tooltip">Download Report</span>
            </li>
            <li id="logout-btn">
                <a href="index.html">
                    <i class='bx bx-log-out'></i>
                    <span class="title">Logout</span>
                </a>
                <span class="tooltip">Logout</span>
            </li>
        </ul>
    </section>
        <main class="home">
        <section id="home-page" class="page-content active">
            <div class="welcome-banner">
                <h1>Welcome, <span id="user-name"><?php echo htmlspecialchars($user['full_name']); ?></span>!</h1>
                <p>Manage your tax calculations and reports in one place</p>
                <div class="user-img">
                    <?php 
                    $profileImagePath = !empty($user['profile_image']) ? "uploads/" . $user['profile_image'] : "./Images/user_img.webp";
                    ?>
                    <img src="<?php echo htmlspecialchars($profileImagePath); ?>" alt="user-profile">
                </div>
            </div>
            <div class="dashboard-cards">
                <div class="card" onclick="navigateTo('profile')">
                    <div class="card-icon">
                        <i class='bx bxs-user-detail'></i>
                    </div>
                    <div class="card-content">
                        <h3>Your Profile</h3>
                        <p>View and update your personal details</p>
                    </div>
                </div>
                <div class="card" onclick="navigateTo('history')">
                    <div class="card-icon">
                        <i class='bx bxs-calculator'></i>
                    </div>
                    <div class="card-content">
                        <h3>Tax History</h3>
                        <p>View your previous tax calculations</p>
                    </div>
                </div>
                <div class="card" onclick="window.location.href='incomedetails.php'">
                    <div class="card-icon">
                        <i class='bx bx-calculator'></i>
                    </div>
                    <div class="card-content">
                        <h3>Calculate Tax</h3>
                        <p>Calculate your new tax amount</p>
                    </div>
                </div>
            </div>
            <div class="summary-section">
                <div class="summary-card">
                    <h3>Quick Summary</h3>
                    <div class="summary-item">
                        <span>🧾 Tax Calculation and Filing System enables easy tax calculation, storage, and filing with a secure PHP-MySQL backend.</span>
                    </div>
                    <div class="summary-item">
                        <span>🧾 It features a user-friendly dashboard for managing personal details and viewing past tax calculations effortlessly.</span>
                    </div>

                    <button class="btn" onclick="navigateTo('history')">View Details</button>
                </div>
                <div class="summary-card">
                    <h3>Tax Tips</h3>
                    <ul class="tax-tips">
                        <li>Keep all receipts for business expenses organized</li>
                        <li>Consider maximizing retirement contributions</li>
                        <li>Check if you qualify for home office deductions</li>
                    </ul>
                    <button class="btn" onclick="window.location.href='incomedetails.php'">Calculate Now</button>
                </div>
            </div>
        </section>
        <!-- User Profile Page -->
        <section id="profile-page" class="page-content">
            <div class="page-header">
                <h2>Your Profile</h2>
                <button id="edit-profile-btn" class="btn">Edit Profile</button>
            </div>

            <?php if (isset($update_success)): ?>
                <div class="alert alert-success">Profile updated successfully!</div>
            <?php endif; ?>
            
            <?php if (isset($update_error)): ?>
                <div class="alert alert-danger"><?php echo $update_error; ?></div>
            <?php endif; ?>

            <div class="profile-container">
                <div class="profile-pic-section">
                    <?php 
                    $profileImagePath = !empty($user['profile_image']) ? "uploads/" . $user['profile_image'] : "./Images/user_img.webp";
                    ?>
                    <img id="profile-pic" src="<?php echo htmlspecialchars($profileImagePath); ?>" alt="Profile Picture">
                    <label for="profile-image" class="btn">Choose File</label>
                </div>
                <div class="profile-card">
                    <h3>Personal Details</h3>
                    <form id="personal-form" method="post" action="" enctype="multipart/form-data">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="full_name">Full Name</label>
                                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" disabled>
                            </div>                  
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                            </div>                  
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" disabled>
                            </div>                  
                            <div class="form-group">
                                <label for="dob">Date of Birth</label>
                                <input type="date" id="dob" name="dob" value="<?php echo htmlspecialchars($user['dob'] ?? ''); ?>" disabled>
                            </div>                  
                            <div class="form-group">
                                <label for="aadhar_number">Aadhaar Number</label>
                                <input type="text" id="aadhar_number" name="aadhar_number" value="<?php echo htmlspecialchars($user['aadhar_number'] ?? ''); ?>" disabled>
                            </div>
                            <div class="form-group">
                                <label for="pan_number">PAN Number</label>
                                <input type="text" id="pan_number" name="pan_number" value="<?php echo htmlspecialchars($user['pan_number']); ?>" disabled>
                            </div>
                            <div class="form-group full-width">
                                <label for="address">Address</label>
                                <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" disabled>
                            </div>
                            <input type="file" id="profile-image" name="profile_image" style="display: none;" accept="image/*">
                        </div>                
                        <button type="submit" id="save-profile-btn" name="update_profile" class="btn" style="display: none;">Save Changes</button>
                    </form>
                </div>
            </div>
        </section>
        <section id="history-page" class="page-content">
            <div class="page-header">
                <h2>Previous Tax Calculations</h2>
            </div>
            <div class="history-card">
                <div class="card-header">
                    <h3>Tax History</h3>
                    <p>View and manage your previous tax calculations.</p>
                </div>
                <div class="table-container">
                    <table id="history-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Assessment Year</th>
                                <th>Date</th>
                                <th>Income</th>
                                <th>Tax Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($tax_calculations) && !empty($tax_calculations)): ?>
                                <?php foreach ($tax_calculations as $calc): ?>
                                    <tr data-year="<?php echo substr($calc['assessment_year'], 0, 4); ?>">
                                        <td><?php echo $calc['id']; ?></td>
                                        <td><?php echo htmlspecialchars($calc['assessment_year']); ?></td>
                                        <td><?php echo date("M d, Y", strtotime($calc['created_at'])); ?></td>
                                        <td><?php echo formatCurrency($calc['total_income']); ?></td>
                                        <td><?php echo formatCurrency($calc['tax_payable']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center;">No tax calculations found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="table-footer">
                    <p id="results-count">Showing <?php echo isset($tax_calculations) ? count($tax_calculations) : 0; ?> calculations</p>
                </div>
            </div>
        </section>
        <section id="reports-page" class="page-content">
    <div class="page-header">
        <h2>Download Tax Reports</h2>
    </div>
    <div class="reports-card">
        <h3>Available Reports</h3>
        <p>Download detailed tax reports for your records</p>
        <div class="reports-list">
            <?php if (isset($tax_calculations) && !empty($tax_calculations)): ?>
                <?php foreach ($tax_calculations as $index => $calc): ?>
                    <div class="report-item">
                        <div class="report-info">
                            <i class='bx bxs-file-pdf'></i>
                            <div>
                                <h4>Tax Calculation Report - <?php echo htmlspecialchars($calc['assessment_year']); ?></h4>
                                <p>Created on <?php echo date("F j, Y", strtotime($calc['created_at'])); ?></p>
                            </div>
                        </div>
                        <button class="btn btn-sm download-report" onclick="window.location.href='dompdf/index.php?id=<?php echo $calc['id']; ?>'">
                            <i class='bx bx-download'></i> Download
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="report-item">
                    <div class="report-info">
                        <i class='bx bxs-file-pdf'></i>
                        <div>
                            <h4>No reports available</h4>
                            <p>Calculate your taxes to generate reports</p>
                        </div>
                    </div>
                    <button class="btn btn-sm" onclick="navigateTo('calculator')">
                        <i class='bx bx-calculator'></i> Calculate Now
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
    </main>
    <div id="calculation-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Tax Calculation Details</h3>
                <span class="close-modal">&times;</span>
            </div>
        </div>
    </div>
    <script>
        const sidebar = document.querySelector('.sidebar');
        const btnMenu = document.querySelector('.btn-menu');
        const main = document.querySelector('main');
        const navLinks = document.querySelectorAll('.nav-links li');
        const pages = document.querySelectorAll('.page-content');
        const logoutBtn = document.getElementById('logout-btn');
        const logoutModal = document.getElementById('logout-modal');
        const cancelLogoutBtn = document.getElementById('cancel-logout-btn');
        const confirmLogoutBtn = document.getElementById('confirm-logout-btn');
        const closeModalBtns = document.querySelectorAll('.close-modal');
        const calculationModal = document.getElementById('calculation-modal');
        const calculatorForm = document.getElementById('calculator-form');
        const incomeSlider = document.getElementById('income-slider');
        const incomeInput = document.getElementById('gross_salary');
        const incomeDisplay = document.getElementById('income-display');
        const tabBtns = document.querySelectorAll('.tab-btn');
        const tabContents = document.querySelectorAll('.tab-content');
        const reportTypeSelect = document.getElementById('report-type');
        const quarterGroup = document.getElementById('quarter-group');
        const historyTable = document.getElementById('history-table');
        const yearBtns = document.querySelectorAll('.year-btn');
        const historySearch = document.getElementById('history-search');
        const resultsCount = document.getElementById('results-count');
        const savePdfBtn = document.getElementById('save-pdf-btn');
        const downloadResultsBtn = document.getElementById('download-results-btn');
        const editProfileBtn = document.getElementById('edit-profile-btn');
        const saveProfileBtn = document.getElementById('save-profile-btn');
        const profilePic = document.getElementById('profile-pic');
        const profileImage = document.getElementById('profile-image');
        const viewDetailsBtns = document.querySelectorAll('.view-details');
        const downloadReportBtns = document.querySelectorAll('.download-report');
        btnMenu.addEventListener('click', function(e) {
            e.preventDefault();
            sidebar.classList.toggle('expand');
            if (window.innerWidth <= 576) {
                if (sidebar.classList.contains('expand')) {
                    sidebar.style.width = '260px';
                    sidebar.style.padding = '10px';
                } else {
                    sidebar.style.width = '78px';
                    sidebar.style.padding = '8px 16px';
                }
            }
        });
        function navigateTo(pageId) {
            pages.forEach(page => {
                page.classList.remove('active');
            });
            const selectedPage = document.getElementById(`${pageId}-page`);
            if (selectedPage) {
                selectedPage.classList.add('active');
            }
            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.dataset.page === pageId) {
                    link.classList.add('active');
                }
            });
            
            if (pageId === 'calculator' && document.getElementById('calculator-results-tab').classList.contains('active')) {
                savePdfBtn.style.display = 'none';
            } else {
                savePdfBtn.style.display = 'block';
            }
            if (window.innerWidth <= 576) {
                sidebar.classList.remove('expand');
            }
        }
        navLinks.forEach(link => {
            if (link.dataset.page) {
                link.addEventListener('click', () => {
                    const pageId = link.dataset.page;
                    navigateTo(pageId);
                });
            }
        });
        if (editProfileBtn) {
            editProfileBtn.addEventListener('click', function() {
                const inputs = document.querySelectorAll('#personal-form input');
                inputs.forEach(input => {
                    if (input.id !== 'profile-image') {
                        input.disabled = false;
                    }
                });
                if (saveProfileBtn) {
                    saveProfileBtn.style.display = 'block';
                }
            });
        }
        if (profileImage) {
            document.querySelector('label[for="profile-image"]').addEventListener('click', function() {
                profileImage.click();
            });
            profileImage.addEventListener('change', function(event) {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        profilePic.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                    saveProfileBtn.style.display = 'block';
                    document.querySelectorAll('#personal-form input').forEach(input => {
                        if (input.id !== 'profile-image') {
                            input.disabled = false;
                        }
                    });
                }
            });
        }
        function formatCurrency(value) {
            return '₹' + parseFloat(value).toLocaleString('en-US', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });
        }
        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                if (!btn.disabled) {
                    const tabId = btn.dataset.tab;
                    tabBtns.forEach(b => b.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    btn.classList.add('active');
                    document.getElementById(tabId + '-tab').classList.add('active');
                   if (tabId === 'calculator-results') {
                        savePdfBtn.style.display = 'block';
                    } else {
                        savePdfBtn.style.display = 'none';
                    }
                }
            });
        });
        if (reportTypeSelect && quarterGroup) {
            reportTypeSelect.addEventListener('change', () => {
                if (reportTypeSelect.value === 'quarterly') {
                    quarterGroup.style.display = 'block';
                } else {
                    quarterGroup.style.display = 'none';
                }
            });
        }
        yearBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                yearBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                const year = btn.dataset.year;
                const rows = historyTable.querySelectorAll('tbody tr');
                let visibleCount = 0;
                rows.forEach(row => {
                    if (year === 'all' || row.dataset.year === year) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });
                resultsCount.textContent = `Showing ${visibleCount} of ${rows.length} calculations`;
            });
        });
        if (historySearch) {
            historySearch.addEventListener('input', () => {
                const searchTerm = historySearch.value.toLowerCase();
                const rows = historyTable.querySelectorAll('tbody tr');
                let visibleCount = 0;
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(searchTerm)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });                
                resultsCount.textContent = `Showing ${visibleCount} of ${rows.length} calculations`;
            });
        }
        viewDetailsBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const calcId = this.dataset.id;
                calculationModal.style.display = 'block';
                document.getElementById('modal-income').textContent = `₹${data.income}`;
                document.getElementById('modal-income').textContent = '₹750,000';
                document.getElementById('modal-tax').textContent = '₹56,500';
                document.getElementById('modal-rate').textContent = '7.53%';
                document.getElementById('modal-year').textContent = '2025 - 2026';
            });
        });
        downloadReportBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const calcId = this.dataset.id;
                alert(`Downloading report for calculation ID: ${calcId}`);
            });
        });
        document.addEventListener('DOMContentLoaded', () => {
            if (window.innerWidth <= 576) {
                sidebar.classList.remove('expand');
            }
            if (incomeDisplay && incomeSlider) {
                incomeDisplay.textContent = formatCurrency(incomeSlider.value);
            }
            window.addEventListener('click', (e) => {
                if (e.target.classList.contains('modal')) {
                    e.target.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>