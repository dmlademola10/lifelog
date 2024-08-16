<?php
    ini_set('display_errors', 'Off');
    ini_set('error_log', 'errors.log');
    ini_set('log_errors_max_len', '0');
    session_start();
    if (!isset($_SESSION['lifelog_owner'])) {
        return;
    }
    require_once("connection.php");

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

    $stmt = $conn -> prepare("SELECT `recordID`, `brief`, `details`, `timeOfEvent`, `upload1`, `upload2`, `upload3`, `entryTime` FROM `events` WHERE `userID`= ? AND `trashed`='' AND `recordID` = ?;");
    $stmt -> bind_param("ii", $_SESSION['lifelog_owner'], $recordID);

    if ($stmt -> execute()) {
        $result = $stmt -> get_result();
    }
    if($result -> num_rows > 0){
        while($row = $result -> fetch_assoc()){
            echo("\n<div class='event' id='e" . $row['recordID'] . "'>\n");
            if(!empty($row['upload1']) || !empty($row['upload2']) || !empty($row['upload3'])){
                echo("    <div class='preview' onclick='fscreen(document.getElementById(\"event_fscreen\")); evnt_fscreen(\"e" . $row['recordID'] ."\");'>\n");
                for ($i=1; $i <= 3; $i++) {
                    if (!empty($row['upload' . $i]) && (file_type($row['upload' . $i]) == "jpg" || file_type($row['upload' . $i]) == "png" || file_type($row['upload' . $i]) == "jpeg" || file_type($row['upload' . $i]) == "gif" || file_type($row['upload' . $i]) == "jfif" || file_type($row['upload' . $i]) == "bmp")) {
                        echo("        <img src='media/" . $row['upload' . $i] . "' alt='missing file' data-file-id='" . $i . "' id='e" . $row['recordID'] . "_img" . $i . "'/>\n");
                    } elseif (!empty($row['upload' . $i]) && (file_type($row['upload' . $i]) == "mp4" || file_type($row['upload' . $i]) == "3gp" || file_type($row['upload' . $i]) == "mkv" || file_type($row['upload' . $i]) == "avi" || file_type($row['upload' . $i]) == "webm" || file_type($row['upload' . $i]) == "ogg")) {
                        echo("        <video muted onmouseover='this.play();this.playbackRate = 3;' onmouseout='this.currentTime = 0;this.pause();' ontimeupdate='vid_preview(this);' data-file-id='" . $i . "'><source src='media/" . $row['upload' . $i] . "' type='video/" . file_type($row['upload' . $i]) . "'>Your browser does not support the video tag.</video>");
                    }
                }
                echo("    </div>\n");
            }
            echo("    <div class='ent_cont'>\n");
            echo("        <div class='entries' onclick='fscreen(document.getElementById(\"event_fscreen\")); evnt_fscreen(\"e" . $row['recordID'] ."\")'>\n");
            echo("            <p class='brief'>" . $row['brief'] . "</p>\n");
            echo("            <h3 class='details'>" . $row['details'] . "</h3>\n");
            echo("            <h6 class='dateofevent' data-value='" . $row['timeOfEvent'] ."'>Date of event: <span>" . date_format(date_create($row['timeOfEvent']),"l, j M Y") . "</span></h6>");
            // echo("            <h6 class='datetime'>Date of entry: " . $row['entryTime'] . "</h6>");
            echo("        </div>\n");
            echo("        <div class='action'>\n");
            echo("            <span class='icon icon3 icon_20px' onclick='edit_event_func(\"e" . $row['recordID'] . "\");fscreen(document.getElementById(\"e_event\"));' title='Edit'></span>\n");
            echo("            <span class='icon icon4 icon_20px del' onclick='trash_event(this, \"e" . $row['recordID'] . "\");' title='Move to Trash'></span>\n");
            echo("        </div>\n");
            echo("    </div>\n");
            echo("</div>");
        }
    } else {
        echo('That event doesn\'t exist!');
        return;
    }
    function sanitize_input($data, $trim = TRUE){
        if($trim === TRUE){
            $data = trim($data);
            $data = preg_replace('/\s+/', ' ', $data);
        }

        $data = htmlentities($data, ENT_QUOTES);

        return $data;
    }
    function file_type($filen){
        return strtolower(pathinfo("media/" . $filen, PATHINFO_EXTENSION));
    }
?>
