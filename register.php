<?php
session_start();
include 'connect.php';

// Store error messages
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $pan_number = trim($_POST['pan_number']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate phone number (exactly 10 digits)
    if (!preg_match("/^[0-9]{10}$/", $phone)) {
        $error_message = "❌ Invalid phone number! Must be 10 digits.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Check if email already exists
        $stmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error_message = "❌ Email already registered!";
        } else {
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, pan_number, password) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $full_name, $email, $phone, $pan_number, $hashed_password);

            if ($stmt->execute()) {
                $_SESSION['user_id'] = $conn->insert_id;
                header("Location: setting.php");
                exit();
            } else {
                $error_message = "❌ Registration failed: " . $stmt->error;
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="logo" href="../Images/Logo_2.png">
    <link rel="stylesheet" href="./CSS/sign_up.css" />
    <title>Sign in & Sign up Form</title>
    <script>
      // Show alert message if there's an error
      <?php if(!empty($error_message)): ?>
      window.onload = function() {
        alert("<?php echo $error_message; ?>");
      }
      <?php endif; ?>
    </script>
  </head>
  <body>
    <div class="container">
      <div class="forms-container">
        <div class="signin-signup">
          <form action="login.php" method="POST" class="sign-in-form">
            <h2 class="title">Sign in</h2>
            <div class="input-field">
              <i class="fas fa-user"></i>
              <input type="text" placeholder="Email" name="login_email"  required=""/>
            </div>
            <div class="input-field">
              <i class="fas fa-lock"></i>
              <input type="password" placeholder="Password" name="login_password" required="" />
            </div>  
            <input type="submit" value="Login" class="btn solid" />
            <p class="social-text">Or Sign in with social platforms</p>
            <div class="social-media">
              <a href="#" class="social-icon">
                <i class="fab fa-facebook-f"></i>
              </a>
              <a href="#" class="social-icon">
                <i class="fab fa-twitter"></i>
              </a>
              <a href="#" class="social-icon">
                <i class="fab fa-google"></i>
              </a>
              <a href="#" class="social-icon">
                <i class="fab fa-linkedin-in"></i>
              </a>
            </div>
          </form>
          <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="sign-up-form" method="POST">
            <h2 class="title">Sign up</h2>
            <div class="input-field">
              <i class="fas fa-user"></i>
              <input type="text" placeholder="Username" name="full_name" required=""/>
            </div>
            <div class="input-field">
              <i class="fas fa-envelope"></i>
              <input type="email" placeholder="Email" name="email" required=""/>
            </div>
            <div class="input-field">
              <i class="fas fa-phone"></i>
              <input type="text" placeholder="Phone Number" name="phone" required />
          </div>
            <div class="input-field">
              <i class="fas fa-id-card"></i>
              <input type="text" placeholder="PAN Number" name="pan_number" required />
          </div>
            <div class="input-field">
              <i class="fas fa-lock"></i>
              <input type="password" placeholder="Password" name="password" required=""/>
            </div>
            <div class="input-field">
                <i class="fas fa-lock"></i>
                <input type="password" placeholder="Confirm password" name="confirm_password" required=""/>
              </div>
            <input type="submit" class="btn" value="Sign up" />
            <p class="social-text">Or Sign up with social platforms</p>
            <div class="social-media">
              <a href="#" class="social-icon">
                <i class="fab fa-facebook-f"></i>
              </a>
              <a href="#" class="social-icon">
                <i class="fab fa-twitter"></i>
              </a>
              <a href="#" class="social-icon">
                <i class="fab fa-google"></i>
              </a>
              <a href="#" class="social-icon">
                <i class="fab fa-linkedin-in"></i>
              </a>
            </div>
          </form>
        </div>
      </div>
      <div class="panels-container">
        <div class="panel left-panel">
          <div class="content">
            <h2>New here ?</h3>
            <p>
                Get started with our hassle-free tax calculating and filing system.
                Sign up today and simplify your tax process!
            </p>
            <button class="btn transparent" id="sign-up-btn">Sign up</button>
          </div>
          <img src="./Images/imag1_signp.png" class="image" alt="" height="70%" width="100%"/>
        </div>
        <div class="panel right-panel">
          <div class="content">
            <h3>Already Registered?</h3>
            <p>Welcome back! Sign in to manage your tax calculations and filings with ease.</p>
            <button class="btn transparent" id="sign-in-btn">
              Sign in
            </button>
          </div>
          <img src="./Images/image2_login.png" class="image" alt=""  />
        </div>
      </div>
    </div>
    <script>
      const sign_in_btn = document.querySelector("#sign-in-btn");
const sign_up_btn = document.querySelector("#sign-up-btn");
const container = document.querySelector(".container");

sign_up_btn.addEventListener("click", () => {
  container.classList.add("sign-up-mode");
});

sign_in_btn.addEventListener("click", () => {
  container.classList.remove("sign-up-mode");
});
    </script>
  </body>
</html>