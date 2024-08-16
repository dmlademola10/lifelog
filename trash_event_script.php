<?php
    ini_set('display_errors', 'Off');
    ini_set('error_log', 'errors.log');
    ini_set('log_errors_max_len', '0');
    session_start();
    if (!isset($_SESSION['lifelog_owner'])) {
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        if (!isset($_POST['id'])) {
            echo("The server received an incomplete request!");
            return;
        }
    } else {
        return;
    }

    $recordID = sanitize_input($_POST['id']);
    $recordID = intval(str_ireplace('e', '', $recordID));
    if (empty($recordID) || !is_integer($recordID)) {
        echo("Sorry, that event isn't valid, reload this page!");
        return;
    }

    require("connection.php");
    $stmt = $conn -> prepare("SELECT `userID`, `trashed` FROM `events` WHERE `recordID` = ?;");
    $stmt -> bind_param("i", $recordID);

    if ($stmt -> execute()) {
        $result = $stmt -> get_result();
        $row = $result -> fetch_assoc();
    } else {
        echo("Sorry, i think i have a problem, please, contact the admin.");
        return;
    }

    if ($result -> num_rows < 0 || ($row['userID'] != $_SESSION['lifelog_owner'])) {
        echo("Sorry, that event does not exist!");
        return;
    } elseif ($row['trashed'] != '') {
        echo('This event is already in the trash!');
        return;
    } else {
        $stmt = $conn -> prepare("UPDATE `events` SET `trashed` = ? WHERE `recordID` = ? AND `userID` = ?");
        $stmt -> bind_param("sii", $date, $recordID, $_SESSION['lifelog_owner']);
        $date = date("Y-m-d H:i:s");
        if ($stmt -> execute()) {
            echo("Event moved to trash successfully!");
            return;
        }
    }

    function sanitize_input($data, $trim = TRUE){
        if($trim === TRUE){
            $data = trim($data);
            $data = preg_replace('/\s+/', ' ', $data);
        }

        $data = htmlentities($data, ENT_QUOTES);

        return $data;
    }
?>
