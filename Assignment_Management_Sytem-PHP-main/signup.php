<?php   
  if(!isset($_SESSION)) {     
    session_start();   
  }   
  include "db_handler.php";   
  
  // Process signup form
  if(isset($_POST['signup'])) {
    // Get form data and sanitize
    $id = mysqli_real_escape_string($conn, $_POST['id']); // Student/Staff ID
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $surname = mysqli_real_escape_string($conn, $_POST['surname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $rank = mysqli_real_escape_string($conn, $_POST['rank']);
    $level = (int)$_POST['level']; // Cast to integer
    
    // Form validation
    $errors = array();
    
    // Check if ID exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0) {
      $errors[] = "ID already exists. Please verify your ID.";
    }
    
    // Check if username exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0) {
      $errors[] = "Username already exists. Please choose another one.";
    }
    
    // Check if email exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0) {
      $errors[] = "Email already exists. Please use another email.";
    }
    
    // Validate username length (max 10 characters per your DB structure)
    if(strlen($username) > 10) {
      $errors[] = "Username must be 10 characters or less.";
    }
    
    // Check password length
    if(strlen($password) > 10) {
      $errors[] = "Password must be 10 characters or less due to system limitations.";
    }
    
    // Check if passwords match
    if($password != $confirm_password) {
      $errors[] = "Passwords do not match.";
    }
    
    // If no errors, proceed with registration
    if(empty($errors)) {
      // Hash the password - Note: We need to modify the database to store longer hashed passwords
      $hashed_password = password_hash($password, PASSWORD_DEFAULT);
      
      // For now, since your database structure limits passwords to 10 chars, we'll store the plain password
      // WARNING: This is not secure and should be changed by updating the database structure
      $store_password = $password; // Insecure - for demonstration only
      
      // Insert user into database
      $stmt = $conn->prepare("INSERT INTO users (id, name, surname, email, username, password, rank, level) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("sssssssi", $id, $name, $surname, $email, $username, $store_password, $rank, $level);
      
      if($stmt->execute()) {
        $success = "Registration successful. You can now login.";
      } else {
        $errors[] = "Error: " . $stmt->error;
      }
    }
  }
?> 
<!DOCTYPE html> 
<html>   
  <head>     
    <meta charset="UTF-8">     
    <title>Create Account</title>     
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">     
    <link rel="stylesheet" href="css/style.css?<?php echo time(); ?> /">     
    <link rel="stylesheet" href="css/style-mickey.css?<?php echo time(); ?> /">
    <style>
      .wrapper {
        max-width: 500px;
        margin: 0 auto;
        padding: 20px;
        background-color: white;
        border-radius: 5px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
      }
      .signup-form input, .signup-form select {
        margin-bottom: 15px;
      }
      .alert {
        margin-top: 20px;
      }
      body {
        background-color: #d9edf7;
        font-family: Arial, sans-serif;
      }
      .signup-page {
        padding: 3% 0 0;
        margin: auto;
      }
      .form {
        position: relative;
        z-index: 1;
        background: #FFFFFF;
        max-width: 450px;
        margin: 0 auto 100px;
        padding: 30px;
        text-align: center;
        box-shadow: 0 0 20px 0 rgba(0, 0, 0, 0.2), 0 5px 5px 0 rgba(0, 0, 0, 0.24);
      }
      .form input, .form select {
        font-family: "Roboto", sans-serif;
        outline: 0;
        background: #f2f2f2;
        width: 100%;
        border: 0;
        margin: 0 0 15px;
        padding: 15px;
        box-sizing: border-box;
        font-size: 14px;
      }
      .form button {
        font-family: "Roboto", sans-serif;
        text-transform: uppercase;
        outline: 0;
        background: #4CAF50;
        width: 100%;
        border: 0;
        padding: 15px;
        color: #FFFFFF;
        font-size: 14px;
        cursor: pointer;
      }
      .form button:hover,.form button:active,.form button:focus {
        background: #43A047;
      }
      .form .message {
        margin: 15px 0 0;
        color: #b3b3b3;
        font-size: 12px;
      }
      .form .message a {
        color: #4CAF50;
        text-decoration: none;
      }
      .input-group {
        display: flex;
        gap: 10px;
      }
      .input-group > div {
        flex: 1;
      }
      h2 {
        color: #4CAF50;
        margin-bottom: 20px;
      }
      .security-warning {
        background-color: #fff3cd;
        color: #856404;
        padding: 10px;
        border-radius: 4px;
        margin-bottom: 15px;
        text-align: left;
        font-size: 12px;
      }
    </style>
  </head>   
  <body> 
    <div class="wrapper">
      <section>       
        <div class="signup-page">         
          <div class="form">
            <h2>Create Account</h2>
            
            <div class="security-warning">
              <strong>Note:</strong> For security reasons, consider updating your database structure to store passwords securely. 
              The current system stores plain text passwords which is not recommended.
            </div>
            
            <?php
              // Display errors if any
              if(!empty($errors)) {
                foreach($errors as $error) {
                  echo "<div class='alert alert-danger'>" . $error . "</div>";
                }
              }
              
              // Display success message
              if(isset($success)) {
                echo "<div class='alert alert-success'>" . $success . "</div>";
                echo "<script>setTimeout(function() { window.location = 'login.php'; }, 3000);</script>";
              }
            ?>
            <form class="signup-form" method="POST">
              <input type="text" name="id" placeholder="Student/Staff ID (8 characters)" maxlength="8" required/>
              
              <div class="input-group">
                <div>
                  <input type="text" name="name" placeholder="First Name" maxlength="25" required/>
                </div>
                <div>
                  <input type="text" name="surname" placeholder="Last Name" maxlength="25" required/>
                </div>
              </div>
              
              <input type="email" name="email" placeholder="Email Address" maxlength="100" required/>
              <input type="text" name="username" placeholder="Username (max 10 chars)" maxlength="10" required/>
              
              <div class="input-group">
                <div>
                  <input type="password" name="password" placeholder="Password (max 10 chars)" maxlength="10" required/>
                </div>
                <div>
                  <input type="password" name="confirm_password" placeholder="Confirm Password" maxlength="10" required/>
                </div>
              </div>
              
              <div class="input-group">
                <div>
                  <select name="rank" required>
                    <option value="">Select Role</option>
                    <option value="Student">Student</option>
                    <option value="Lecturer">Lecturer</option>
                    <option value="Supervisor">Supervisor</option>
                    <option value="Admin">Admin</option>
                  </select>
                </div>
                <div>
                  <select name="level" required>
                    <option value="">Select Level</option>
                    <option value="1">Level 1</option>
                    <option value="2">Level 2</option>
                    <option value="3">Level 3</option>
                    <option value="4">Level 4</option>
                  </select>
                </div>
              </div>
              
              <button type="submit" name="signup">Sign Up</button>
              <p class="message">Already have an account? <a href="login.php">Login</a></p>
            </form>
          </div>       
        </div>     
      </section>
    </div>   
  </body> 
</html>