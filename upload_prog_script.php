<?php
    ini_set('display_errors', 'Off');
    ini_set('error_log', 'errors.log');
    ini_set('log_errors_max_len', '0');
    header('Cache-Control: no-cache');
    session_start();

    if (!isset($_SESSION['lifelog_owner'])) {
        echo('{"progress": false, "message": "Your session has expired!"}');
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        if (!isset($_POST['field'])) {
            $output = '{"success": false, "message": "The server received an incomplete request!"}';
            echo($output);
            return;
        }
    } else {
        $output = '{"success": false, "message": "Sorry, something went wrong try reloading this page!"}';
        echo($output);
        return;
    }

    $key = ini_get("session.upload_progress.prefix") . $_POST['field'];
    if (isset($_SESSION[$key])) {
        $processed = 0;
        for ($i=0; $i <= 2; $i++) {
            if (isset($_SESSION[$key]['files'][$i]['bytes_processed'])) {
                $processed += $_SESSION[$key]['files'][$i]['bytes_processed'];
            } else {
                break;
            }
        }
        $percent_processed = ($processed / $_SESSION[$key]['content_length']) * 100;
        echo('{"progress": true, "message": "' . round($percent_processed, 0, PHP_ROUND_HALF_UP) . '"}');
    } else {
        echo('{"progress": "stopped", "message": ""}');
    }
?>
