<?php
session_start();
include 'connect.php'; 
if (!isset($_SESSION['user_id'])) {
    header("Location: register.php"); 
    exit();
}
$user_id = $_SESSION['user_id'];
if (isset($_FILES['profile_image']) && !empty($_FILES['profile_image']['name'])) {
    $image_name = time() . "_" . basename($_FILES['profile_image']['name']); 
    $target_dir = "uploads/"; 
    $target_file = $target_dir . $image_name;

    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
        $sql = "UPDATE users SET profile_image = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $image_name, $user_id);
        $stmt->execute();
        $stmt->close();
    }
}
$sql = "SELECT full_name, email, phone, pan_number, dob, address, aadhar_number, profile_image FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $full_name = $row['full_name'];
    $email = $row['email'];
    $phone = $row['phone'];
    $pan_number = $row['pan_number'];
    $dob = $row['dob'];
    $address = $row['address'];
    $aadhar_number = $row['aadhar_number'];
    $profile_image = $row['profile_image'];
} else {
    die("Error: User not found!");
}
$stmt->close();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dob = $_POST['dob'];
    $address = $_POST['address'];
    $aadhar_number = $_POST['aadhar_number'];
    $sql = "UPDATE users SET dob=?, address=?, aadhar_number=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $dob, $address, $aadhar_number, $user_id);
    if ($stmt->execute()) {
        header("Location: incomedetails.php"); 
        exit();
    } else {
        echo "Error updating details: " . $stmt->error;
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tax Mate - Settings</title>
    <link rel="icon" href="./Images/Logo_dark.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="./CSS/setting.css">
</head>
<body>
<div class="container">
    <h1>Settings <i class="fa-solid fa-gear fa-spin"></i></h1>

    <form action="setting.php" method="POST" enctype="multipart/form-data">
        <div class="Personal">
            <div class="personal_left">
                <h4>Personal Information</h4>  
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($full_name); ?>" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" class="form-control" value="<?php echo htmlspecialchars($phone); ?>" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Taxpayer ID (PAN)</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($pan_number); ?>" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" class="form-control" name="dob" value="<?php echo htmlspecialchars($dob); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea class="form-control" name="address" required><?php echo htmlspecialchars($address); ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Aadhar Number</label>
                    <input type="text" class="form-control" name="aadhar_number" value="<?php echo htmlspecialchars($aadhar_number); ?>">
                </div>
            </div>

            <div class="personal_right">
                <h4>Profile Picture</h4>
                <div class="text-center">
                    <img id="profilePreview" src="<?php echo isset($profile_image) ? 'uploads/' . $profile_image : './Images/default-profileimg.png'; ?>" class="profile-img">
                    <input type="file" class="form-control mt-2" id="profileImage" name="profile_image" accept="image/*">
                </div>
            </div>
        </div>

        <br>
        <div class="btn-container">
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
</div>

<script>
    document.getElementById('profileImage').addEventListener('change', function(event) {
        const reader = new FileReader();
        reader.onload = () => document.getElementById('profilePreview').src = reader.result;
        reader.readAsDataURL(event.target.files[0]);
    });
</script>

</body>
</html>
