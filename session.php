<?php
    ini_set('display_errors', 'Off');
    ini_set('error_log', 'errors.log');
    ini_set('log_errors_max_len', '0');
    session_start();

    if(!isset($_SESSION['lifelog_owner']) && !isset($_SESSION['lifelog_new_owner'])) {
        header("Location: signin.php");
    }

    if (isset($_SESSION['lifelog_new_owner'])) {
    	$_SESSION['lifelog_owner'] = $_SESSION['lifelog_new_owner'];
    	unset($_SESSION['lifelog_new_owner']);
        echo("<!DOCTYPE HTML><html><head><script type='text/javascript'>alert('WELCOME, NEW USER, TO YOUR lifelog\\nThanks for trying our lifelog, we hope you enjoy using it!');</script></head></html>");
    }
?>
