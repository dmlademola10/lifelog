<?php
    ini_set('display_errors', 'Off');
    ini_set('error_log', 'errors.log');
    ini_set('log_errors_max_len', '0');
    ini_set('upload_max_filesize', '5120M');
    ini_set('post_max_size', '6000M');
    header("Content-Type: application/json; charset=UTF-8");
    session_start();
    if(!isset($_SESSION['lifelog_owner'])){
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        if (!isset($_POST['brief']) || !isset($_POST['day']) || !isset($_POST['month']) || !isset($_POST['year']) || !isset($_POST['details']) || !isset($_FILES['upload1']) || !isset($_FILES['upload2']) || !isset($_FILES['upload3'])) {
            $output = '{"success": false, "message": "The server received an incomplete request!"}';
            echo($output);
            return;
        }
    } else {
        $output = '{"success": false, "message": "Sorry, something went wrong try reloading this page!"}';
        echo($output);
        return;
    }

    $brief = sanitize_input($_POST['brief']);
    if ($_FILES['upload1']['size'] > 0) {
        $upload1 = $_FILES['upload1'];
    }
    if ($_FILES['upload2']['size'] > 0) {
        $upload2 = $_FILES['upload2'];
    }
    if ($_FILES['upload3']['size'] > 0) {
        $upload3 = $_FILES['upload3'];
    }
    $day = sanitize_input($_POST['day']);
    $month = sanitize_input($_POST['month']);
    $year = sanitize_input($_POST['year']);
    $details = sanitize_input($_POST['details'], TRUE, TRUE);

    if(empty($details) || empty($brief)){
        $output = '{"success": false, "message": "The brief and details fields can\'t be empty!"}';
        echo($output);
        return;
    }

    if (strlen($_POST['brief']) < 3) {
        $output = '{"success": false, "message": "Your brief must have at least 3 characters!"}';
        echo($output);
        return;
    }

    if (strlen($_POST['brief']) > 30) {
        $output = '{"success": false, "message": "Your brief must not have more than 30 characters!"}';
        echo($output);
        return;
    }

    if (strlen($_POST['details']) < 20) {
        $output = '{"success": false, "message": "Your details must have at least 20 characters!"}';
        echo($output);
        return;
    }

    if (strlen($_POST['details']) > 8000) {
        $output = '{"success": false, "message": "Your details must not have more than 8000 characters!"}';
        echo($output);
        return;
    }

    if ((!is_numeric($month) || !is_numeric($day) || !is_numeric($year)) || !checkdate($month, $day, $year)) {
        $output = '{"success": false, "message": "That date isn\'t valid!"}';
        echo($output);
        return;
    }

    $date_event = date_create($year . "-" . $month . "-" . $day);
    $date_now = date_create(date("Y-m-d"));
    $diff = date_diff($date_event, $date_now);
    $diff = $diff -> format("%R%a");
    if ($diff < 0) {
        $output = '{"success": false, "message": "An event can\'t have happened in the future!"}';
        echo($output);
        return;
    }
    $time_of_event = date_format($date_event, 'Y-m-d');
    require('connection.php');

    // edit the query below to include check date of event to make sure ...
    $stmt = $conn -> prepare("SELECT `recordID` FROM `events` WHERE `userID`= ? AND `brief`= ? AND `details`= ? AND `timeOfEvent`= ?;");
    $stmt -> bind_param("isss", $_SESSION['lifelog_owner'], $brief, $details, $time_of_event);

    if ($stmt -> execute()) {
        $result = $stmt -> get_result();
        if ($result -> num_rows > 0) {
            $output = '{"success": false, "message": "An event with the same brief, details and date already exists!"}';
            echo($output);
            return;
        }
    }
    $stmt = $conn -> prepare("INSERT INTO `events`(`userID`, `brief`, `details`, `timeOfEvent`) VALUES(?, ?, ?, ?)");
    $stmt -> bind_param("isss", $_SESSION['lifelog_owner'], $brief, $details, $time_of_event);

    if ($stmt -> execute()) {
        $output = new stdClass();
        $output -> success = true;
        $output -> message = "Saved event successfully.";

        if (isset($upload1)) {
            $upload_res = upload_file($upload1, 1);
            if ($upload_res !== TRUE) {
                $output -> message = $output -> message . $upload_res;
            }
        }
        if (isset($upload2)) {
            $upload_res = upload_file($upload2, 2);
            if ($upload_res !== TRUE) {
                $output -> message = $output -> message . $upload_res;
            }
        }
        if (isset($upload3)) {
            $upload_res = upload_file($upload3, 3);
            if ($upload_res !== TRUE) {
                $output -> message = $output -> message . $upload_res;
            }
        }
    } else {
        $output = '{"success": false, "message": "Sorry, i think i have a problem, please, contact the admin."}';
        echo($output);
        exit;
    }

    function upload_file($upload, $index) {
        global $msg, $conn, $brief, $details, $time_of_event;

        $target_dir = "media/";
        $target_file = $target_dir . basename($upload["name"]);
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));


        if ($upload["size"] > 500000000) {
            return("<br/>Oops, your file is too large!");
        }
        if ($upload["size"] < 5000) {
            return("<br/>Oh no, your file is too small!");
        }
        if($file_type == "jpg" || $file_type == "png" || $file_type == "jpeg" || $file_type == "gif" || $file_type == "jfif" || $file_type == "bmp") {
            // Check if image file is a actual image or fake image
            $check = getimagesize($upload["tmp_name"]);

            if($check === FALSE) {
                return("<br/>Oops, this is not a valid image!");
            }
            $uniq_name = strtoupper(str_ireplace('.', '', uniqid("MJ_IMG_", TRUE)) . str_ireplace('.', '', uniqid("", TRUE))) . '.' . $file_type;
        } elseif ($file_type == "mp4" || $file_type == "webm") {
            $uniq_name = strtoupper(str_ireplace('.', '', uniqid("MJ_VID_", TRUE)) . str_ireplace('.', '', uniqid("", TRUE))) . '.' . $file_type;
        } else {
            return("<br/>Sorry, that kind of file isn't allowed!");
        }

        if (move_uploaded_file($upload["tmp_name"], $target_file)) {
            $stmt = $conn -> prepare("UPDATE `events` SET `upload" . $index . "` = ? WHERE `userID` = ? AND `brief` = ? AND `details` = ? AND `timeOfEvent` = ?;");
            $stmt -> bind_param("sisss", $uniq_name, $_SESSION['lifelog_owner'], $brief, $details, $time_of_event);
            if($stmt -> execute()){
                // rename($target_file, $upload['tmp_name']);
                rename($target_file, $target_dir . $uniq_name);
                return TRUE;
            } else {
                return("<br/>I have a problem, contact the admins!");
            }
        } else {
            return("<br/>Sorry, i think i have a problem, please, contact the admin.");
        }
    }

    $output = json_encode($output);
    echo($output);

    function sanitize_input($data, $trim = TRUE, $break = FALSE){
        $data = htmlentities($data, ENT_QUOTES);

        if ($break === TRUE) {
            $data = nl2br($data);
        }
        if($trim === TRUE) {
            $data = trim($data);
            $data = preg_replace('/\s+/', ' ', $data);
        }
        return $data;
    }
?>
