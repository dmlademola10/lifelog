<?php
    ini_set('display_errors', 'Off');
    ini_set('error_log', 'errors.log');
    ini_set('log_errors_max_len', '0');
    ini_set('upload_max_filesize', '5120M');
    ini_set('post_max_size', '6000M');
    session_start();
    require_once("connection.php");
    if(!isset($_SESSION['lifelog_owner'])){
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        if (!isset($_POST['event_id']) || !isset($_POST['brief']) || !isset($_POST['details']) || !isset($_FILES['upload1']) || !isset($_FILES['upload2']) || !isset($_FILES['upload3'])) {
            $output = '{"success": false, "message": "The server received an incomplete request!"}';
            echo($output);
            return;
        }
    } else {
        $output = '{"success": false, "message": "Sorry, something went wrong try reloading this page!"}';
        echo($output);
        return;
    }

    $recordID = sanitize_input($_POST['event_id']);
    $recordID = intval(str_ireplace('e', '', $recordID));
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
        $output = '{"success": false, "message": "Sorry, either your details or brief is missing!"}';
        echo($output);
        return;
    }

    if (strlen($_POST['brief']) < 3) {
        $output = '{"success": false, "message": "Your brief must have at least 3 characters!"}';
        echo($output);
        return;
    }

    if (strlen($_POST['details']) < 20) {
        $output = '{"success": false, "message": "Your details must have at least 20 characters!"}';
        echo($output);
        return;
    }

    if (strlen($_POST['brief']) > 30) {
        $output = '{"success": false, "message": "Your brief must not have more than 30 characters!"}';
        echo($output);
        return;
    }

    if (strlen($_POST['details']) > 8000) {
        $output = '{"success": false, "message": "Your details must not have more than 8000 characters!"}';
        echo($output);
        return;
    }

    if (!is_numeric($month) || !is_numeric($day) || !is_numeric($year)) {
        $output = '{"success": false, "message": "That date isn\'t valid!"}';
        echo($output);
        return;
    }

    if (!checkdate($month, $day, $year)) {
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

    $stmt = $conn -> prepare("SELECT `recordID` FROM `events` WHERE `userID`= ? AND `recordID` = ?;");
    $stmt -> bind_param("ii", $_SESSION['lifelog_owner'], $recordID);

    if ($stmt -> execute()) {
        $result = $stmt -> get_result();
        if ($result -> num_rows <= 0) {
            $output = '{"success": false, "message": "Sorry, that event does not exist!"}';
            echo($output);
            return;
        }
    }

    $stmt = $conn -> prepare("SELECT `recordID` FROM `events` WHERE `userID`= ? AND `recordID`!= ? AND `brief`= ? AND `details`= ? AND `timeOfEvent` = ?;");
    $stmt -> bind_param("iisss", $_SESSION['lifelog_owner'], $recordID, $brief, $details, $time_of_event);
    //still need to check if this works properly
    if ($stmt -> execute()) {
        $result = $stmt -> get_result();
        if($result -> num_rows > 0){
            $output = '{"success": false, "message": "An event with same brief, details and date already exists!"}';
            echo($output);
            return;
        }
    }

    $stmt = $conn -> prepare("UPDATE `events` SET `brief`= ?, `details`= ?, `timeOfEvent` = ? WHERE `userID`= ? AND `recordID`= ?;");
    $stmt -> bind_param("sssii", $brief, $details, $time_of_event, $_SESSION['lifelog_owner'], $recordID);

    if ($stmt -> execute()) {
        class Output {
            public $success;
            public $message;

            public function __construct($success, $message) {
                $this -> success = $success;
                $this -> message = $message;
            }
        }
        $output = new Output(true, "Saved new data successfully.");
    }

    for ($i = 1; $i <= 3; $i++) {
        if (isset($_POST['del_img' . $i])) {
            $stmt = $conn -> prepare("SELECT `upload" . $_POST['del_img' . $i] . "` FROM `events` WHERE `userID`= ? AND `recordID`= ? AND `trashed`= ''");
            $stmt -> bind_param("ii", $_SESSION['lifelog_owner'], $recordID);

            if ($stmt -> execute()) {
                $result = $stmt -> get_result();
                $row = $result -> fetch_assoc();
                $file = "media/" . $row['upload' . $_POST['del_img' . $i]];
                if ($result -> num_rows == 1 && !empty($row['upload' . $_POST['del_img' . $i]]) && file_exists($file)) {
                    chown($file, daemon);
                    if (unlink($file)) {
                        $stmt = $conn -> prepare("UPDATE `events` SET `upload" . $_POST['del_img' . $i] . "`= '' WHERE `userID`= ? AND `recordID`= ? AND `trashed`= '';");
                        $stmt -> bind_param("ii", $_SESSION['lifelog_owner'], $recordID);
                        if ($stmt -> execute()) {
                            if ($_POST['del_img' . $i] == 1) {
                                $output -> message = $output -> message . "<br/>Deleted first file successfully.";
                            }
                            if ($_POST['del_img' . $i] == 2) {
                                $output -> message = $output -> message . "<br/>Deleted second file successfully.";
                            }
                            if ($_POST['del_img' . $i] == 3) {
                                $output -> message = $output -> message . "<br/>Deleted third file successfully.";
                            }
                        }
                    } else {
                        if ($_POST['del_img' . $i] == 1) {
                            $output -> message = $output -> message . "<br/>Could not delete first file.";
                        }
                        if ($_POST['del_img' . $i] == 2) {
                            $output -> message = $output -> message . "<br/>Could not delete second file.";
                        }
                        if ($_POST['del_img' . $i] == 3) {
                            $output -> message = $output -> message . "<br/>Could not delete third file.";
                        }
                    }
                } else {
                    if ($_POST['del_img' . $i] == 1) {
                        $output -> message = $output -> message . "<br/>Sorry, first file doesn't exist!";
                    }
                    if ($_POST['del_img' . $i] == 2) {
                        $output -> message = $output -> message . "<br/>Sorry, second file doesn't exist!";
                    }
                    if ($_POST['del_img' . $i] == 3) {
                        $output -> message = $output -> message . "<br/>Sorry, third file doesn't exist!";
                    }
                }
            }
        }
    }

    if (isset($upload1)) {
        if(upload_file($upload1, 1)){
            $output -> message = $output -> message . "<br/>First file saved successfully.";
        } else {
            $output -> message = $output -> message . "<br/>Could not save first file.";
        }
    }
    if (isset($upload2)) {
        if(upload_file($upload2, 2)){
            $output -> message = $output -> message . "<br/>Second file saved successfully.";
        } else {
            $output -> message = $output -> message . "<br/>Could not save second file.";
        }
    }
    if (isset($upload3)) {
        if(upload_file($upload3, 3)){
            $output -> message = $output -> message . "<br/>Third file saved successfully.";
        } else {
            $output -> message = $output -> message . "<br/>Could not save third file.";
        }
    }

    function upload_file($upload, $field) {
        global $msg, $conn, $recordID, $output;

        $target_dir = "media/";
        $target_file = $target_dir . basename($upload["name"]);
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if ($upload["size"] > 500000000) {
            $output -> message = $output -> message . "<br/>Oops, your file is too large!";
            return;
        }

        if ($upload["size"] < 5000) {
            $output -> message = $output -> message . "<br/>Oh no, your file is too small!";
            return;
        }

        if($file_type == "jpg" || $file_type == "png" || $file_type == "jpeg" || $file_type == "gif" || $file_type == "jfif" || $file_type == "bmp") {
            // Check if image file is a actual image or fake image
            $check = getimagesize($upload["tmp_name"]);

            if($check === FALSE) {
                $output -> message = $output -> message . "<br/>Oops, this is not a valid image!";
                return FALSE;
            }

            $uniq_name = strtoupper(str_ireplace('.', '', uniqid("MJ_IMG_", TRUE)) . str_ireplace('.', '', uniqid("", TRUE))) . '.' . $file_type;
        } elseif ($file_type == "mp4" || $file_type == "webm") {
            $uniq_name = strtoupper(str_ireplace('.', '', uniqid("MJ_VID_", TRUE)) . str_ireplace('.', '', uniqid("", TRUE))) . '.' . $file_type;
        } else {
            $output -> message = $output -> message . "<br/>Sorry, that kind of file isn't allowed!";
            return FALSE;
        }

        $stmt = $conn -> prepare("SELECT `upload" . $field . "` FROM `events` WHERE `userID`= ? AND `recordID` = ?");
        $stmt -> bind_param("ii", $_SESSION['lifelog_owner'], $recordID);

        if ($stmt -> execute()) {
            $result = $stmt -> get_result();
            $row = $result -> fetch_assoc();
            if ($result -> num_rows == 1 && !empty($row['upload' . $field])) {
                $file = "media/" . $row['upload' . $field];
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }

        if (move_uploaded_file($upload["tmp_name"], $target_file)) {
            $stmt = $conn -> prepare("UPDATE `events` SET `upload" . $field . "`= ? WHERE `userID`= ? AND `recordID` = ?");
            $stmt -> bind_param("sii", $uniq_name, $_SESSION['lifelog_owner'], $recordID);
            if ($stmt -> execute()) {
                rename($target_file, $target_dir . $uniq_name);
                return TRUE;
            } else {
                $output -> message = $output -> message . "<br/>I have a problem, contact the admins!";
                return FALSE;
            }
        } else {
            $output -> message = $output -> message . "<br/>Sorry, i think i have a problem, please, contact the admin.";
            return FALSE;
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
