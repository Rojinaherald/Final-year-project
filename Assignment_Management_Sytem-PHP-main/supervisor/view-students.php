<!-- MAKE REMOVE STUDENT A POP UP INSTEAD OF A FORM IF POSSIBLE -->

<!DOCTYPE html>
<html>
	<?php 
		include "../includes/header.php";
		include "../includes/navbar.php";
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
    <title></title>
</head>
<body  style="background-color:lightblue">
<div class="container">
    <div class="row">
      <form class="form-horizontal" style="float: right;" action="view-students.php" method="post" name="export" enctype="multipart/form-data">
          <div class="form-group">
            <div class="col-md-4 col-md-offset-4">
              
            </div>
          </div>                    
        </form> 
    	<h1>List Of Students</h1>
    	<hr>      
        <div class="panel panel-primary filterable" style="border-color: #00bdaa;">
            <div class="panel-heading" style="background-color: #00bdaa;">
                <h3 class="panel-title">Students</h3>
                
            </div>
            <table class="table">
                <thead>
                    <tr class="filters">
                        <th><input type="text" class="form-control" placeholder="Student ID" disabled></th>
                        <th><input type="text" class="form-control" placeholder="Full Name" disabled></th>
                        <th><input type="text" class="form-control" placeholder="Email" disabled></th>
                        <th><input type="text" class="form-control" placeholder="Level" disabled></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $sql = "SELECT * FROM users WHERE `rank` = 'student'"; 
                        $result = mysqli_query($conn, $sql) or die(mysqli_error($conn));

                      
                        $output = '';
                        if(isset($_POST["query"]))
                        {
                         $search = mysqli_real_escape_string($conn, $_POST["query"]);
                         $query = "
                          SELECT * FROM users 
                          WHERE name LIKE '%".$search."%'
                          OR surname LIKE '%".$search."%' 
                          OR email LIKE '%".$search."%' 
                          OR username LIKE '%".$search."%' 
                          OR level LIKE '%".$search."%'  
                         ";
                        }
                        else
                        {

                          $query = "SELECT * FROM users WHERE `rank`='student' ORDER BY name asc";
                        }

                        $result = mysqli_query($conn, $query);
                        if(mysqli_num_rows($result) > 0)
                        {

                         while($row = mysqli_fetch_array($result))
                         {
                        $username = $row["username"];
                            
                          $output .= '
                           <tr>
                            <td>'.$row["id"].'</td>
                            <td>'.$row["name"]. ' ' .$row["surname"].'</td>
                            <td>'.$row["email"].'</td>
                            <td>'.$row["level"].'</td>
                           </tr>
                          ';
                         }
                         echo $output;
                        }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
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
</style>



