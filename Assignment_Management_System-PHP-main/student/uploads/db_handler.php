<?php
require_once('config.php');

$conn = mysqli_connect('127.0.0.1', 'root', '', 'project');


if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
