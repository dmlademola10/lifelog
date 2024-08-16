<?php
	ini_set('display_errors', 'Off');
    ini_set('error_log', 'errors.log');
    ini_set('log_errors_max_len', '0');
    ini_set('upload_max_filesize', '5120M');
	$servername = "localhost";
	$conn_username = "root";
	$conn_password = "";
	$database = "lifelog_db";

	$conn = new mysqli($servername, $conn_username, $conn_password, $database);
	if ($conn->connect_error) {
		header("Location: setup.php");
	    die("Connection failed: " . $conn->connect_error);
	}
?>
