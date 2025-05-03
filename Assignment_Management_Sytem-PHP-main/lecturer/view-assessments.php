<?php
// Add this at the top of your file, after the existing includes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Include database connection
include "../db_handler.php";

// Make sure to include PHPMailer
require_once(__DIR__ . '/phpmailer/PHPMailer.php');
require_once(__DIR__ . '/phpmailer/SMTP.php');
require_once(__DIR__ . '/phpmailer/Exception.php');


// Add notification handling code
if(isset($_POST['send_notification'])) {
    $assessment_id = mysqli_real_escape_string($conn, $_POST['assessment_id']);
    
    // Get assessment details
    $assessmentQuery = "SELECT a.*, m.module_name FROM assessment a 
                      JOIN module m ON a.module_code = m.module_code 
                      WHERE a.assessment_code = '$assessment_id'";
    $assessmentResult = mysqli_query($conn, $assessmentQuery);
    
    if($assessmentResult && mysqli_num_rows($assessmentResult) > 0) {
        $assessment = mysqli_fetch_assoc($assessmentResult);
        $module_code = $assessment['module_code'];
        $module_name = $assessment['module_name'];
        $assessment_name = $assessment['name'];
        $assessment_desc = $assessment['description'];
        $assessment_weight = $assessment['weighs'];
        $assessment_deadline = $assessment['deadline'];
        
        // Format deadline date for email
        $deadlineDate = date('l, F j, Y', strtotime($assessment_deadline));
        
        // Get all students enrolled in this module
        $studentsQuery = "SELECT email, name, surname FROM users WHERE  `rank` = 'student'";
        $studentsResult = mysqli_query($conn, $studentsQuery);
        
        if($studentsResult && mysqli_num_rows($studentsResult) > 0) {
            // Create new PHPMailer instance
            $mail = new PHPMailer(true); // true enables exceptions
            
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'theemaildelivery@gmail.com';
                $mail->Password   = 'vayn uydp xcjd xupf';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                $mail->SMTPDebug  = 0; // Set to 2 for debugging
                
                // Default sender
                $mail->setFrom('theemaildelivery@gmail.com', 'Academic System');
                
                // Set email subject
                $subject = "Assessment Reminder: $module_code - $assessment_name";
                $mail->Subject = $subject;
                
                $emailsSent = 0;
                $totalStudents = mysqli_num_rows($studentsResult);
                
                // Send email to each student
                while($student = mysqli_fetch_assoc($studentsResult)) {
                    $studentEmail = $student['email'];
                    $studentName = $student['name'] . ' ' . $student['surname'];
                    
                    // Clear previous recipients
                    $mail->clearAddresses();
                    
                    // Add recipient
                    $mail->addAddress($studentEmail, $studentName);
                    
                    // Email body with HTML formatting
                    $message = "
                    <html>
                    <head>
                        <title>Assessment Notification</title>
                        <style>
                            body { font-family: Arial, sans-serif; line-height: 1.6; }
                            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                            h3 { color: #333366; }
                            ul { padding-left: 20px; }
                            .footer { margin-top: 30px; border-top: 1px solid #eee; padding-top: 10px; font-size: 0.9em; color: #777; }
                            .important { color: #cc0000; font-weight: bold; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <p>Dear $studentName,</p>
                            <p>This is a reminder about an assessment for your module <strong>$module_code - $module_name</strong>.</p>
                            
                            <h3>Assessment Details:</h3>
                            <ul>
                                <li><strong>Name:</strong> $assessment_name</li>
                                <li><strong>Weight:</strong> $assessment_weight</li>
                                <li><strong>Deadline:</strong> <span class='important'>$deadlineDate</span></li>
                            </ul>
                            
                            <p><strong>Description:</strong><br>$assessment_desc</p>
                            
                            <p>Please log in to the student portal for more information and assessment requirements.</p>
                            
                            <div class='footer'>
                                <p>Best regards,<br>
                                University Academic Team</p>
                            </div>
                        </div>
                    </body>
                    </html>
                    ";
                    
                    // Set email format to HTML
                    $mail->isHTML(true);
                    $mail->Body = $message;
                    $mail->AltBody = strip_tags(str_replace('<br>', "\n", $message)); // Plain text version
                    
                    // Try to send the email
                    if($mail->send()) {
                        $emailsSent++;
                    }
                }
                
                // Show success message
                echo "<div class='alert alert-success' role='alert'>
                        <strong>Success!</strong> Notification emails have been sent to $emailsSent out of $totalStudents students enrolled in $module_code.
                      </div>";
                
            } catch(Exception $e) {
                // Log the error and display a message
                error_log("Mailer Error: " . $mail->ErrorInfo);
                echo "<div class='alert alert-danger' role='alert'>
                        <strong>Error:</strong> There was an issue sending notification emails: " . $mail->ErrorInfo . "
                      </div>";
            }
        } else {
            echo "<div class='alert alert-warning' role='alert'>
                    <strong>Warning:</strong> No students found enrolled in module $module_code.
                  </div>";
        }
    } else {
        echo "<div class='alert alert-danger' role='alert'>
                <strong>Error:</strong> Assessment not found.
              </div>";
    }
}
?>

<!DOCTYPE html>
<html>
  <?php 
    include "../includes/header.php";
    include "../includes/admin-navbar.php";
    include "../db_handler.php";
  ?>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" type="text/css" href="bootstrap/css/bootstrap.min.css"/>
    <title>Assessment List</title>
</head>
<body style="background-color:lightblue">
<div class="container">
    <div class="row">
        <h1>List Of Assessments</h1>
        <hr>
        
        <!-- Export button -->
        <form method="post" class="mb-3">
          <button type="submit" name="export" class="btn btn-success">
            <i class="glyphicon glyphicon-download"></i> Export to CSV
          </button>
        </form>
        
        <div class="panel panel-primary filterable" style="border-color: #00bdaa;">
            <div class="panel-heading" style="background-color: #00bdaa;">
                <h3 class="panel-title">Assessments</h3>
                <div class="pull-right">
                    <button class="btn btn-default btn-xs btn-filter"><span class="glyphicon glyphicon-filter"></span> Filter</button>
                </div>
            </div>
            <table class="table">
                <thead>
                    <tr class="filters">
                        <th><input type="text" class="form-control" placeholder="Assessment Name" disabled></th>
                        <th><input type="text" class="form-control" placeholder="Description" disabled></th>
                        <th><input type="text" class="form-control" placeholder="Sub Assessment" disabled></th>
                        <th><input type="text" class="form-control" placeholder="SA Description" disabled></th>
                        <th><input type="text" class="form-control" placeholder="SA Weight" disabled></th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $output = '';
                        if(isset($_POST["query"]))
                        {
                         $search = mysqli_real_escape_string($conn, $_POST["query"]);
                         $query = "
                          SELECT * FROM assessment 
                          WHERE name LIKE '%".$search."%' 
                         ";
                        }
                        else
                        {
                         $query = "
                          SELECT * FROM assessment ORDER BY assessment_code asc
                         ";
                        }
                        $result = mysqli_query($conn, $query);
                        if(mysqli_num_rows($result) > 0)
                        {
                         while($row = mysqli_fetch_array($result))
                         {
                          $output .= '
                           <tr>
                            <td>'.$row["name"].'</td>
                            <td>'.$row["description"].'</td>
                            <td>'.$row["sub_assessment"].'</td>
                            <td>'.$row["sub_assessment_description"].'</td>
                            <td>'.$row["sub_assessment_weight"].'</td>
                            <td>
                              <form method="post">
                                <input type="hidden" name="assessment_id" value="'.$row["assessment_code"].'">
                                <button type="submit" name="send_notification" class="btn btn-warning btn-sm">
                                  <i class="glyphicon glyphicon-bell"></i> Send Notification
                                </button>
                              </form>
                            </td>
                           </tr>
                          ';
                         }
                         echo $output;
                        }
                        else
                        {
                          echo '<tr><td colspan="6" class="text-center">No assessments found</td></tr>';
                        }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    $('.btn-filter').on('click', function(){
        var $panel = $(this).parents('.filterable'),
        $filters = $panel.find('.filters input'),
        $tbody = $panel.find('.table tbody');
        if ($filters.prop('disabled') == true) {
            $filters.prop('disabled', false);
            $filters.first().focus();
        } else {
            $filters.val('').prop('disabled', true);
            $tbody.find('.no-result').remove();
            $tbody.find('tr').show();
        }
    });

    $('.filterable .filters input').keyup(function(e){
        var code = e.keyCode || e.which;
        if (code == '9') return;
        
        var $input = $(this),
        inputContent = $input.val().toLowerCase(),
        $panel = $input.parents('.filterable'),
        column = $panel.find('.filters th').index($input.parents('th')),
        $table = $panel.find('.table'),
        $rows = $table.find('tbody tr');
        
        var $filteredRows = $rows.filter(function(){
            var value = $(this).find('td').eq(column).text().toLowerCase();
            return value.indexOf(inputContent) === -1;
        });
        
        $table.find('tbody .no-result').remove();
        
        $rows.show();
        $filteredRows.hide();
        
        if ($filteredRows.length === $rows.length) {
            $table.find('tbody').prepend($('<tr class="no-result text-center"><td colspan="'+ $table.find('.filters th').length +'">No results found</td></tr>'));
        }
    });
});
</script>

</body>
</html>

<style type="text/css">
    .filterable {
        margin-top: 15px;
    }
    .filterable .panel-heading .pull-right {
        margin-top: -20px;
    }
    .filterable .filters input[disabled] {
        background-color: transparent;
        border: none;
        cursor: auto;
        box-shadow: none;
        padding: 0;
        height: auto;
    }
    .filterable .filters input[disabled]::-webkit-input-placeholder {
        color: #333;
    }
    .filterable .filters input[disabled]::-moz-placeholder {
        color: #333;
    }
    .filterable .filters input[disabled]:-ms-input-placeholder {
        color: #333;
    }
    /* Additional styling */
    .mb-3 {
        margin-bottom: 15px;
    }
    .btn-sm {
        padding: 5px 10px;
        font-size: 12px;
    }
</style>