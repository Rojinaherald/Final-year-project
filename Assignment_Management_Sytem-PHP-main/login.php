<?php
  if(!isset($_SESSION)) {
    session_start();
  }
  include "db_handler.php";

  // Initialize error variables
  $usernameErr = $passwordErr = $loginErr = "";
  $username = $password = "";
  
  // Form validation function
  function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
  }
  
  // Process form submission
  if(isset($_POST['submit'])) {
    // Validate username
    if (empty($_POST['userid'])) {
      $usernameErr = "Username is required";
    } else {
      $username = test_input($_POST['userid']);
    }
    
    // Validate password
    if (empty($_POST['password'])) {
      $passwordErr = "Password is required";
    } else {
      $password = test_input($_POST['password']);
    }
    
    // Proceed only if no validation errors
    if (empty($usernameErr) && empty($passwordErr)) {
      // Use prepared statements to prevent SQL injection
      $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
      $stmt->bind_param("ss", $username, $password);
      $stmt->execute();
      $result = $stmt->get_result();
      
      if (!$row = $result->fetch_assoc()) {
        $loginErr = "Invalid username or password";
      } else {
        $_SESSION['id'] = $row['username'];
        
        if(isset($row['rank'])) {
          $rank = strtolower($row['rank']);
          $_SESSION['rank'] = $row['rank'];
          
          switch($rank) {
            case 'admin':
              header("Location: home/adminHome.php");
              exit();
            case 'lecturer':
              header("Location: home/lecturerHome.php");
              exit();
            case 'student':
              header("Location: home/studentHome.php");
              exit();
            case 'supervisor':
              header("Location: home/supervisorHome.php");
              exit();
            default:
              $loginErr = "Role not found";
          }
        } else {
          $loginErr = "User role not defined";
        }
      }
    }
  }
?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <title> Login Form</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="stylesheet" href="css/style.css?<?php echo time(); ?> /">
    <link rel="stylesheet" href="css/style-mickey.css?<?php echo time(); ?> /">
    <style>
      .error {
        color: #FF0000;
        font-size: 14px;
        margin-bottom: 10px;
      }
      .alert {
        padding: 10px;
        margin-bottom: 15px;
        border-radius: 4px;
      }
      .alert-danger {
        background-color: #f2dede;
        border-color: #ebccd1;
        color: #a94442;
      }
      .alert-success {
        background-color: #dff0d8;
        border-color: #d6e9c6;
        color: #3c763d;
      }
    </style>
  </head>
  <body style="background-color:lightblue;">
    <br><br><br><br><br><br><br>
    
    <div class="wrapper">
      <section>
        <div class="login-page">
          <div class="form">
            <!-- Display login error message if exists -->
            <?php if (!empty($loginErr)): ?>
              <div class="alert alert-danger">
                <?php echo $loginErr; ?>
              </div>
            <?php endif; ?>
            
            <!-- Display login error message if exists -->
            <?php if (!empty($loginErr)): ?>
              <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $loginErr; ?>
              </div>
            <?php endif; ?>
            
            <form class="login-form" method="POST" id="loginForm" novalidate>
              <p>
                <i class="fas fa-user-circle"></i> Login to your account
              </p>
              <div class="form-group">
                <label for="userid"><i class="fas fa-user"></i> Username</label>
                <input type="text" name="userid" id="userid" placeholder="Enter your username" value="<?php echo $username; ?>"/>
                <span class="error"><?php echo $usernameErr; ?></span>
              </div>
              
              <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Password</label>
                <input type="password" name="password" id="password" placeholder="Enter your password"/>
                <span class="error"><?php echo $passwordErr; ?></span>
              </div>
              
         
              
              <button type="submit" name="submit" id="submit">
                <i class="fas fa-sign-in-alt"></i> Login
              </button>
              
              <div style="text-align: center; margin-top: 20px;">
                <p>Don't have an account? <a href="#" style="color: #3498db; text-decoration: none;">Register here</a></p>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
      
    <script src='https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js'></script>
    <script src="../js/index.js"></script>
      <script>
        $(document).ready(function() {
          // Form validation using jQuery
          $("#loginForm").on("submit", function(e) {
            let isValid = true;
            const userid = $("#userid").val().trim();
            const password = $("#password").val().trim();
            
            // Clear previous error messages
            $(".error").text("");
            
            // Username validation
            if (userid === "") {
              $("#userid").next(".error").text("Username is required");
              isValid = false;
              $("#userid").css("border-color", "#FF0000");
            } else {
              $("#userid").css("border-color", "#ddd");
            }
            
            // Password validation
            if (password === "") {
              $("#password").next(".error").text("Password is required");
              isValid = false;
              $("#password").css("border-color", "#FF0000");
            } else {
              $("#password").css("border-color", "#ddd");
            }
            
            if (!isValid) {
              e.preventDefault(); // Prevent form submission if validation fails
            }
          });
          
          // Add input focus effects
          $("input").focus(function() {
            $(this).css("border-color", "#3498db");
          }).blur(function() {
            if ($(this).val().trim() !== "") {
              $(this).css("border-color", "#ddd");
            }
          });
          
          // Show/hide password toggle
          $(".form").append('<div class="show-password" style="position: absolute; right: 15px; top: 48%; transform: translateY(-50%); cursor: pointer;"><i class="fas fa-eye"></i></div>');
          
          $(".show-password").on("click", function() {
            const passwordField = $("#password");
            const icon = $(this).find("i");
            
            if (passwordField.attr("type") === "password") {
              passwordField.attr("type", "text");
              icon.removeClass("fa-eye").addClass("fa-eye-slash");
            } else {
              passwordField.attr("type", "password");
              icon.removeClass("fa-eye-slash").addClass("fa-eye");
            }
          });
        });
      </script>
      
      <!-- Add a footer -->
      <footer style="position: fixed; bottom: 0; width: 100%; text-align: center; padding: 10px; color: white; font-size: 14px; background-color: rgba(0,0,0,0.1);">
        <p>&copy; 2025 Assignment Management System | <a href="#" style="color: white; text-decoration: underline;">Terms of Service</a> | <a href="#" style="color: white; text-decoration: underline;">Privacy Policy</a></p>
      </footer>
    </div>
  </body>
</html>