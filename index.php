<?php
require 'vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;
require '../connect.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    die("User not logged in. Please log in first.");
}
$user_id = $_SESSION['user_id']; 
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Tax calculation ID is required.");
}
$tax_calc_id = intval($_GET['id']);
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
$user_query = $conn->prepare("SELECT full_name, email, pan_number, profile_image FROM users WHERE id = ?");
if (!$user_query) {
    die("Prepare failed for user_query: " . $conn->error);
}
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_result = $user_query->get_result();
$user = $user_result->fetch_assoc();
$user_query->close();
if (!$user) {
    die("User details not found.");
}
$tax_query = $conn->prepare("SELECT * FROM tax_calculations WHERE id = ? AND user_id = ?");
if (!$tax_query) {
    die("Prepare failed for tax_query: " . $conn->error);
}
$tax_query->bind_param("ii", $tax_calc_id, $user_id);
$tax_query->execute();
$tax_result = $tax_query->get_result();
$tax_data = $tax_result->fetch_assoc();
$tax_query->close();
if (!$tax_data) {
    die("Tax calculation details not found or you don't have permission to access this data.");
}
function encodeImageToBase64($imagePath) {
    if (file_exists($imagePath)) {
        $imageData = file_get_contents($imagePath);
        return 'data:image/png;base64,' . base64_encode($imageData);
    }
    return ''; 
}
$profilePath = $_SERVER['DOCUMENT_ROOT'] . "/tax/uploads/" . $user['profile_image'];
$profileBase64 = encodeImageToBase64($profilePath);
function formatCurrency($amount) {
    return '₹ ' . number_format($amount, 0);
}
function formatDate($date) {
    return date("M d, Y", strtotime($date));
}
$financial_year = $tax_data['assessment_year'];
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true); 
$options->set('defaultFont', 'DejaVu Sans');
$dompdf = new Dompdf($options);
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Tax Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }        
        body { font-family: "DejaVu Sans", sans-serif; line-height: 1.6; color: #333; padding: 20px; margin: 0 auto; }
        .image { text-align: center; margin: 20px 0; }
        .image img { margin-top: -3%; width: 1500px; max-width: 100%; height: auto; }
        hr { border: none; height: 1px; background-color: #ddd; margin: 15px 0; }
        .personal { position: relative; padding: 15px; background-color: #f8f8f8; border: 1px solid #e0e0e0; border-radius: 5px; margin: 15px 0; min-height: 150px; } 
        .personal_info { width: 70%; }
        .personal_info table { width: 100%; border-collapse: collapse; }
        .personal_info td { padding: 6px 0; }
        .personal_info td:first-child { font-weight: bold; color: #555; width: 150px; }
        .profile_img { position: absolute; top: 15px; right: 15px; }
        .profile_img img { width: 120px; height: 120px; border-radius: 50%; border: 2px solid #e0e0e0; }
        .tax { margin: 20px 0; }
        .tax table { width: 100%; border-collapse: collapse; background-color: #fff; border: 1px solid #d0d0d0; }
        .tax table tr:nth-child(even) { background-color: #f5f5f5; }
        .tax table tr:last-child { border-top: 2px solid #d0d0d0; font-weight: bold; }
        .tax table td { padding: 10px 15px; border: 1px solid #d0d0d0; }
        .tax table td:first-child { font-weight: bold; color: #444; width: 40%; }
        .tax table td:last-child { text-align: right; font-family: "DejaVu Sans Mono", monospace; }
        footer { text-align: center; padding: 15px 0; color: #777; font-size: 12px; margin-top: 20px; }
        .footer-bottom p { margin: 0; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <section class="image">
        <img src="http://localhost/tax/Images/Roport_logo.png" alt="Tax Mate">
    </section>
    <hr>
    <section class="personal">
        <div class="personal_info">
            <table>
                <tr><td>Name:</td><td>' . htmlspecialchars($user['full_name']) . '</td></tr>
                <tr><td>Email:</td><td>' . htmlspecialchars($user['email']) . '</td></tr>
                <tr><td>Pan No:</td><td>' . htmlspecialchars($user['pan_number']) . '</td></tr>
            </table>
        </div>
        <div class="profile_img">
            <img src="' . $profileBase64 . '" alt="Profile Photo">
        </div>
    </section>
    <hr>
    <section class="tax">
        <table>
            <tr><td>Financial Year:</td><td>' . htmlspecialchars($financial_year) . '</td></tr>
            <tr><td>Income</td><td>' . formatCurrency($tax_data['total_income']) . '</td></tr>
            <tr><td>Deductions</td><td>' . formatCurrency($tax_data['total_deductions']) . '</td></tr>
            <tr><td>Age Category</td><td>' . htmlspecialchars($tax_data['age_category'] ?? 'Below 60') . '</td></tr>
            <tr><td>Filing Status</td><td>' . htmlspecialchars($tax_data['filing_status'] ?? 'Self-Employed') . '</td></tr>
            <tr><td>Taxable Income</td><td>' . formatCurrency($tax_data['taxable_income']) . '</td></tr>
            <tr><td>Tax Payable</td><td>' . formatCurrency($tax_data['tax_payable']) . '</td></tr>
            <tr><td>Calculation date:</td><td>' . formatDate($tax_data['created_at']) . '</td></tr>
        </table>
    </section>
    <hr>
    <footer>
        <div class="footer-bottom">
            <p>&copy; ' . date('Y') . ' Tax Mate. All rights reserved, Built with ❤️ in India</p>
        </div>
    </footer>
    <hr>
</body>
</html>
';
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('Tax_Report.pdf', array('Attachment' => false));
exit(0);
?>