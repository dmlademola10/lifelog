<?php
    ini_set('display_errors', 'Off');
    ini_set('error_log', 'errors.log');
    ini_set('log_errors_max_len', '0');
    session_start();
    require_once("connection.php");
    if(!isset($_SESSION['lifelog_owner'])){
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        if (!isset($_POST['search_str'])) {
            echo("The server received an incomplete request!");
            return;
        }
    } else {
        return;
    }
    $usearch = sanitize_input($_POST['search_str']);
    $search_split = preg_split("/[,!\. ]/", $usearch);
    if (empty($usearch)) {
        echo('An error occurred, try reloading this page!');
        return;
    }
    if ($_SERVER['HTTP_REFERER'] == 'http://localhost/lifelog/home.php') {
        $sql = "SELECT DISTINCT `recordID`, `brief`, `details`, `timeOfEvent`, `upload1`, `upload2`, `upload3`, `entryTime` FROM `events` WHERE `userID`= ? AND `trashed`='' AND (`brief` LIKE '%" . $usearch . "%' OR `details` LIKE '%" . $usearch . "%'";
        foreach ($search_split as $search => $value) {
            if ($value == $usearch) {
                break;
            }
            if ($value == '') {
                continue;
            }
            $sql = $sql . " OR `brief` LIKE '%" . $value . "%' OR `details` LIKE '%" . $value . "%'";
        }
        $sql = $sql . ") ORDER BY `timeOfEvent` DESC, `recordID` DESC;";
    } elseif ($_SERVER['HTTP_REFERER'] == 'http://localhost/lifelog/trash.php') {
        $sql = "SELECT DISTINCT `recordID`, `brief`, `details`, `timeOfEvent`, `upload1`, `upload2`, `upload3`, `entryTime` FROM `events` WHERE `userID`= ? AND `trashed`!='' AND (`brief` LIKE '%" . $usearch . "%' OR `details` LIKE '%" . $usearch . "%'";
        foreach ($search_split as $search => $value) {
            if ($value == $usearch) {
                break;
            }
            if ($value == '') {
                continue;
            }
            $sql = $sql . " OR `brief` LIKE '%" . $value . "%' OR `details` LIKE '%" . $value . "%'";
        }
        $sql = $sql . ") ORDER BY `timeOfEvent` DESC, `recordID` DESC;";
    } else {
        return;
    }

    $stmt = $conn -> prepare($sql);
    $stmt -> bind_param('i', $_SESSION['lifelog_owner']);

    if ($stmt -> execute()) {
        $result = $stmt -> get_result();
    } else {
        return;
    }

    if ($result -> num_rows > 0){
        echo('<h3 style="text-align: center;"> Search Results.</h3>');
        while($row = $result -> fetch_assoc()){
            echo("\n<div class='event' id='e" . $row['recordID'] . "'>\n");
            if(!empty($row['upload1']) || !empty($row['upload2']) || !empty($row['upload3'])){
                echo("    <div class='preview' onclick='fscreen(document.getElementById(\"event_fscreen\")); evnt_fscreen(\"e" . $row['recordID'] ."\");'>\n");
                for ($i=1; $i <= 3; $i++) {
                    if (!empty($row['upload' . $i]) && (file_type($row['upload' . $i]) == "jpg" || file_type($row['upload' . $i]) == "png" || file_type($row['upload' . $i]) == "jpeg" || file_type($row['upload' . $i]) == "gif" || file_type($row['upload' . $i]) == "jfif" || file_type($row['upload' . $i]) == "bmp")) {
                        echo("        <img src='media/" . $row['upload' . $i] . "' alt='missing file' data-file-id='" . $i . "' id='e" . $row['recordID'] . "_file" . $i . "'/>\n");
                    } elseif (!empty($row['upload' . $i]) && (file_type($row['upload' . $i]) == "mp4" || file_type($row['upload' . $i]) == "webm")) {
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
            if ($_SERVER['HTTP_REFERER'] == 'http://localhost/lifelog/home.php') {
                echo("            <span class='icon icon3 icon_20px' onclick='edit_event_func(\"e" . $row['recordID'] . "\");fscreen(document.getElementById(\"e_event\"));' title='Edit'></span>\n");
                echo("            <span class='icon icon4 icon_20px del' onclick='trash_event(this, \"e" . $row['recordID'] . "\");' title='Move to Trash'></span>\n");
            } else {
                echo("            <span class='icon icon9 icon_20px res' onclick='restore_trash(\"e" . $row['recordID'] . "\");' title='Restore from Trash'></span>\n");
                echo("            <span class='icon icon4 icon_20px del' onclick='delete_event(\"e" . $row['recordID'] . "\");' title='Delete permanently.'></span>\n");
            }
            echo("        </div>\n");
            echo("    </div>\n");
            echo("</div>");
        }
    } else {
        echo("<h1 class='text' id='text'>No event found!</h1>");
    }

    function file_type($filen){
        return strtolower(pathinfo("media/" . $filen, PATHINFO_EXTENSION));
    }

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
