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
        echo("The server received an incomplete request!");
        return;
    }

    $recordID = sanitize_input($_POST['id']);
    $recordID = intval(str_ireplace('e', '', $recordID));
    if (empty($recordID) || !is_integer($recordID)) {
        echo("Sorry, that event isn't valid, reload this page!");
        return;
    }

    require("connection.php");
    $stmt = $conn -> prepare("SELECT `userID`, `upload1`, `upload2`, `upload3`, `trashed` FROM `events` WHERE `recordID` = ?;");
    $stmt -> bind_param("i", $recordID);

    if ($stmt -> execute()) {
        $result = $stmt -> get_result();
        $row = $result -> fetch_assoc();
    } else {
        echo("Sorry, i think i have a problem, please, contact the admin.");
        return;
    }

    if ($result -> num_rows < 0 || ($row['userID'] != $_SESSION['lifelog_owner'])) {
        echo("Sorry, that event doesn't exist!");
        return;
    } elseif ($row['trashed'] == "") {
        echo('That event doesn\'t exist in the trash!');
        return;
    } else {
        for ($i=1; $i <= 3; $i++) {
            if (file_exists("media/" . $row['upload' . $i]) && !empty($row['upload' . $i])) {
                if (unlink("media/" . $row['upload' . $i])) {
                    $stmt = $conn -> prepare("UPDATE `events` SET `upload" . $i . "` = '' WHERE `recordID` = ? AND `userID` = ?;");
                    $stmt -> bind_param("ii", $recordID, $_SESSION['lifelog_owner']);

                    if ($stmt -> execute()) {
                        continue;
                    } else {
                        echo("Sorry, i think i have a problem, please, contact the admin.");
                        return;
                    }
                }
            }
        }

        $stmt = $conn -> prepare("DELETE FROM `events` WHERE `recordID` = ? AND `userID` = ?;");
        $stmt -> bind_param("ii", $recordID, $_SESSION['lifelog_owner']);

        if ($stmt -> execute()) {
            echo("done");
            return;
        } else {
            echo("Sorry, i think i have a problem, please, contact the admin.");
            return;
        }
    }

    function sanitize_input($data, $trim = TRUE, $break = FALSE){
        $data = htmlentities($data, ENT_QUOTES);

        if($trim === TRUE){
            $data = trim($data);
            $data = nl2br($data);
            $data = preg_replace('/\s+/', ' ', $data);
        }
        return $data;
    }
?>
