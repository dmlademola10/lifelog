<?php
	session_start();

	unset($_SESSION['lifelog_owner']);
	if (!isset($_SESSION['lifelog_owner'])) {
		header("Location: signin.php");
	}
?>
