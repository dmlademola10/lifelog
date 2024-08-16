<?php
	$servername = "localhost";
	$conn_username = "root";
	$conn_password = "";
	$database = "lifelog_db";

	$conn = new mysqli($servername, $conn_username, $conn_password, $database);
	if ($conn->connect_error) {
        $conn = new mysqli($servername, $conn_username, $conn_password);
	    $sql = "CREATE DATABASE `" . $database . "`;";
        if($conn -> query($sql) === FALSE) {
            echo("<h1 style=\"color:red\">An error occured</h1><br/>Contact the administrator for help!");
            return;
        } else{
            $sql = "CREATE TABLE `" . $database . "`.`users` (`userID` INT NOT NULL AUTO_INCREMENT, `fullName` VARCHAR(255) NOT NULL, `otherName` VARCHAR(255) NOT NULL, `dateOfBirth` VARCHAR(255) NOT NULL, `telephoneNumber` VARCHAR(255) NOT NULL, `email` VARCHAR(255) NOT NULL, `hobby` VARCHAR(255) NOT NULL, `userName` VARCHAR(255) NOT NULL, `passWord` VARCHAR(255) NOT NULL, PRIMARY KEY (`userID`), UNIQUE (`userName`)) ENGINE = InnoDB;";
            if($conn->query($sql) === TRUE) {
                header("Location: index.php");
            } else {
                echo("<h1 color=\"red\">An error occured</h1><br/>Could not setup environment for user, contact the administrator for help!");
            }
        }
	} else {
        header("Location: index.php");
    }
?>
