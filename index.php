<?php
    ini_set('display_errors', 'Off');
    ini_set('error_log', 'errors.log');
    ini_set('log_errors_max_len', '0');
    ini_set('upload_max_filesize', '5120M');
    session_start();
    if (isset($_SESSION['lifelog_owner'])) {
        header("Location: home.php");
    } else {
        header("Location: signin.php");
    }
?>
