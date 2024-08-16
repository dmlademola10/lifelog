<?php
    ini_set('display_errors', 'Off');
    ini_set('error_log', 'errors.log');
    ini_set('log_errors_max_len', '0');
    ini_set('upload_max_filesize', '5120M');
    require_once("session.php");
    require_once("connection.php");
    $_SESSION['pgno'] = 0;
    $stmt = $conn -> prepare("SELECT `fullName`, `noOfEvents`, `theme` FROM `users` WHERE `userID` = ?;");
    $stmt -> bind_param("i", $_SESSION['lifelog_owner']);
    if ($stmt -> execute()) {
        $result = $stmt -> get_result();
        $row = $result -> fetch_assoc();
        $_SESSION['theme'] = $row['theme'];
    } else {
        echo("An error occured!");
    }
    function file_type($filen){
        return strtolower(pathinfo("media/" . $filen, PATHINFO_EXTENSION));
    }
?>
<!DOCTYPE html>
<html lang="EN-GB">
    <head>
        <title>
            My lifelog - Home.
        </title>
        <meta charset="UTF-8">
        <meta name="description" content="I'm a friend and lifelog that you can share things with."/>
        <meta name="keywords" content="lifelog, friend, events, communicate"/>
        <meta name="author" content="Dml Ademola, TechWise LLC."/>
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <?php
            if ($row['theme'] == 'dark') {
                echo('<meta name="theme-color" content="rgb(1, 1, 34)"/>');
            } else {
                echo('<meta name="theme-color" content="whitesmoke"/>');
            }
        ?>
        <link rel="icon" href="images/lifelog_lg.jpg" type="image/jpg"/>
        <link rel="stylesheet" href="css/style.css"/>
        <?php
            if ($row['theme'] == 'dark') {
                echo('<link rel="stylesheet" href="css/dark_theme.css"/>');
            } else {
                echo('<link rel="stylesheet" href="css/light_theme.css"/>');
            }
        ?>
    </head>
    <body onload="close_wel()">
        <div class='welcome' id='welcome'>
            <h1>Welcome to ...</h1>
            <img src='images/lifelog_lg.jpg' alt='#img'><br/>
            <p class='wel'>My lifelog</p>
        </div>
        <div class="container1">
            <div class="head">
                <div class="division">
                    <h1>My lifelog.</h1>
                </div>
                <div class="division top_act">
                    <a href="home.php" class='icon icon8' title="Home."></a>
                    <a href="trash.php" class='icon icon4 active' title="Go to Trash."></a>
                    <a href="prof_settings.php" class='icon icon10' title="Profile & Preferences."></a>
                    <a href="signout.php" class='signout icon icon7' title="Sign Out."></a>
                </div>
                <div class="division">
                    <?php
                        // echo("<h4>Hola, " . $row['fullName'] . "</h4>");
                    ?>
                    <form name='search' onsubmit='search_ajax(event, this["search_str"].value);'>
                        <input type='text' name='search_str' class='input2' placeholder='Search for an event...' onkeyup='search_ajax(event, this.value);' onfocus='if (this.value != "") { search_ajax(event, this.value); }'/>
                    </form>
                </div>
            </div>
            <div class="main" id="main">
                <div class="events" id="events">
                    <?php

                        // remember to change order by to entry Time and not record id;
                        $stmt = $conn -> prepare("SELECT `recordID`, `brief`, `details`, `timeOfEvent`, `upload1`, `upload2`, `upload3`, `entryTime` FROM `events` WHERE `userID`= ? AND `trashed`!='' ORDER BY `recordID` DESC LIMIT 0, " . $row['noOfEvents'] . ";");
                        $stmt -> bind_param("i", $_SESSION['lifelog_owner']);

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
                                echo("            <h6 class='dateofevent'>Date of event: " . $row['timeOfEvent'] . "</h6>");
                                // echo("            <h6 class='datetime'>Date of entry: " . $row['entryTime'] . "</h6>");
                                echo("        </div>\n");
                                echo("        <div class='action'>\n");
                                echo("            <span class='icon icon9 icon_20px res' onclick='restore_trash(\"e" . $row['recordID'] . "\");' title='Restore from Trash'></span>\n");
                                echo("            <span class='icon icon4 icon_20px del' onclick='delete_event(\"e" . $row['recordID'] . "\");' title='Delete permanently.'></span>\n");
                                echo("        </div>\n");
                                echo("    </div>\n");
                                echo("</div>");
                            }
                        } else {
                            echo("<h1 class='text' id='text'>No event found in trash!</h1>");
                        }
                    ?>
                </div>
                <div class="fscreen" id="fscreen">
                    <div class="event_fscreen" id='event_fscreen'>
                        <span class='close' onclick="document.getElementById('fscreen').style.display = 'none';" title='Close'>&times;</span>
                        <div class='event_container'>
                            <p id='fscreen_brief' style='text-align: center;overflow-wrap: anywhere;'></p>
                            <div class='event_file' id='event_file'></div>
                            <h3 id='fscreen_desc' class='fscreen_desc'></h3>
                            <h5 id='fscreen_dateofevent'></h5>
                            <h5 id='fscreen_datetime'></h5>
                            <div id='fscreen_action' class='fscreen_action'></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class='bottom'>
                <span onclick='pagination_refresh_ajax("prev")' title='Previous.'>&laquo;Prev</span>
                <span onclick='pagination_refresh_ajax("next")' title='Next.'>Next&raquo;</span>
            </div>
        </div>
        <script src="js/script.js"></script>
    </body>
</html>
