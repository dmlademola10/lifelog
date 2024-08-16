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
        exit("An error occured!");
    }
    function file_type($filen){
        return strtolower(pathinfo("media/" . $filen, PATHINFO_EXTENSION));
    }
?>
<!DOCTYPE html>
<html lang="EN-GB" id='html'>
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
                    <img src='images/lifelog_lg.jpg' alt='' style='width: 40px; height: 40px;'/>
                    <h1>My lifelog.</h1>
                </div>
                <div class="division top_act">
                    <a href="home.php" class='icon icon8 active' title="Home."></a>
                    <a href="trash.php" class='icon icon4' title="Go to Trash."></a>
                    <a href="prof_settings.php" class='icon icon10' title="Profile & Preferences."></a>
                    <span onclick="fscreen(document.getElementById('n_event'));" class='icon icon6' title='Add new event.'></span>
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
                        $stmt = $conn -> prepare("SELECT `recordID`, `brief`, `details`, `timeOfEvent`, `upload1`, `upload2`, `upload3`, `entryTime` FROM `events` WHERE `userID`= ? AND `trashed`='' ORDER BY `timeOfEvent` DESC, `recordID` DESC LIMIT 0, " . $row['noOfEvents'] . ";");
                        $stmt -> bind_param("i", $_SESSION['lifelog_owner']);

                        if ($stmt -> execute()) {
                            $result = $stmt -> get_result();
                        }
                        if ($result -> num_rows > 0){
                            while($row = $result -> fetch_assoc()){
                                echo("\n<div class='event' id='e" . $row['recordID'] . "'>\n");
                                if(!empty($row['upload1']) || !empty($row['upload2']) || !empty($row['upload3'])){
                                    echo("    <div class='preview' onclick='fscreen(document.getElementById(\"event_fscreen\")); evnt_fscreen(\"e" . $row['recordID'] ."\");'>\n");
                                    for ($i = 1; $i <= 3; $i++) {
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
                                echo("            <span class='icon icon3 icon_20px' onclick='edit_event_func(\"e" . $row['recordID'] . "\");fscreen(document.getElementById(\"e_event\"));' title='Edit'></span>\n");
                                echo("            <span class='icon icon4 icon_20px del' onclick='trash_event(this, \"e" . $row['recordID'] . "\");' title='Move to Trash'></span>\n");
                                echo("        </div>\n");
                                echo("    </div>\n");
                                echo("</div>");
                            }
                        } else {
                            echo("<h1 class='text' id='text'>No event found!</h1>");
                        }
                    ?>
                </div>
                <div class="fscreen" id="fscreen">
                    <div class="n_event" id='n_event'>
                        <span class='close' onclick="document.getElementById('fscreen').style.display = 'none';" title='Close'>&times;</span>
                        <form name="new_event" id="new_event" method="post" enctype="multipart/form-data" onsubmit="save_event_ajax(event, document.getElementById('brief').value, document.getElementById('details').value);">
                            <h2 style="text-align: center;">Add New Event.</h2>
                            <span id="msg"></span><br/>
                            <input type='hidden' name='<?php echo(ini_get('session.upload_progress.name')); ?>' value='new'/>
                            <div id='prog_cont' class='prog_cont'>
                                <div id='prog_val' class='prog_val' style='width: 0%;'></div>
                            </div>
                            <label for="brief">Brief</label>
                            <input type="text" name="brief" id="brief" oninput='document.getElementById("brief_count").innerHTML = count_rem(this.value.length, 30, "brief_count"); document.getElementById("msg").outerHTML = "<span id =\"msg\"></span>"; document.getElementById("prog_val").style.width = "0%"' onblur='document.getElementById("brief_count").style.visibility = "hidden";' onfocus='document.getElementById("brief_count").style.visibility = "visible"; document.getElementById("brief_count").innerHTML = count_rem(this.value.length, 30, "brief_count");'/><br>
                            <span id='brief_count'></span><br/>
                            <label for="upload1">Upload first file</label>
                            <input type="file" name="upload1" id="upload1" onchange='document.getElementById("msg").outerHTML = "<span id =\"msg\"></span>"; document.getElementById("prog_val").style.width = "0%"'/>
                            <label for="upload2">Upload second file</label>
                            <input type="file" name="upload2" id="upload2" onchange='document.getElementById("msg").outerHTML = "<span id =\"msg\"></span>"; document.getElementById("prog_val").style.width = "0%"'/>
                            <label for="upload2">Upload third file</label>
                            <input type="file" name="upload3" id="upload3" onchange='document.getElementById("msg").outerHTML = "<span id =\"msg\"></span>"; document.getElementById("prog_val").style.width = "0%"'/>
                            <label for='date_of_event'>Date of Event</label>
                            <div class='date_of_event' id='date_of_event'>
                                <select name='day' id='day' class='input2' onchange='document.getElementById("msg").outerHTML = "<span id =\"msg\"></span>"; document.getElementById("prog_val").style.width = "0%"'>
                                    <?php
                                        $day_now = date("d");
                                        for ($i=1; $i <= 31; $i++) {
                                            if ($i == $day_now) {
                                                echo("\n<option value='" . $i . "' selected>" . $i . "</option>");
                                                continue;
                                            }
                                            echo("\n<option value='" . $i . "'>" . $i . "</option>");
                                        }
                                    ?>
                                </select>
                                <select name='month' id='month' class='input2' onchange='document.getElementById("msg").outerHTML = "<span id =\"msg\"></span>"; document.getElementById("prog_val").style.width = "0%"'>
                                    <?php
                                        $fmonth_now = date("m");
                                        $month = array("01" => "January", "02" => "February", "03" => "March", "04" => "April", "05" => "May", "06" => "June", "07" => "July", "08" => "August", "09" => "September", "10" => "October", "11" => "November", "12" => "December");
                                        foreach ($month as $mon_no => $mon_val) {
                                            if ($mon_no == $fmonth_now) {
                                                echo("\n<option value='" . $mon_no . "' selected>" . $mon_val . "</option>");
                                                continue;
                                            }
                                            echo("\n<option value='" . $mon_no . "'>" . $mon_val . "</option>");
                                        }
                                    ?>
                                </select>
                                <select name='year' id='year' class='input2' onchange='document.getElementById("msg").outerHTML = "<span id =\"msg\"></span>"; document.getElementById("prog_val").style.width = "0%"'>
                                    <?php
                                        $year_now = date("Y");
                                        for ($i = $year_now - 75; $i <= $year_now; $i++) {
                                            if ($i == $year_now) {
                                                echo("\n<option value='" . $i . "' selected>" . $i . "</option>");
                                                continue;
                                            }
                                            echo("\n<option value='" . $i . "'>" . $i . "</option>");
                                        }
                                    ?>
                                </select>
                            </div>
                            <label for="details">Details</label>
                            <textarea name="details" id="details" oninput='document.getElementById("desc_count").innerHTML = count_rem(this.value.length, 8000, "desc_count"); document.getElementById("msg").outerHTML = "<span id =\"msg\"></span>"; document.getElementById("prog_val").style.width = "0%"' onblur='document.getElementById("desc_count").style.visibility = "hidden";' onfocus='document.getElementById("desc_count").style.visibility = "visible"; document.getElementById("desc_count").innerHTML = count_rem(this.value.length, 8000, "desc_count");'></textarea><br/>
                            <span id='desc_count'></span><br/>
                            <div class="form_div">
                                <button type="submit">SAVE</button>
                                <button type="reset" style='background-color: red; color: whitesmoke;' onclick='document.getElementById("msg").innerHTML ="";'>CLEAR</button>
                            </div>
                        </form>
                    </div>
                    <div class="e_event" id='e_event'>
                        <span class='close' onclick="document.getElementById('fscreen').style.display = 'none';" title='Close'>&times;</span>
                        <form name="edit_event" id="edit_event" method="post" enctype="multipart/form-data" onsubmit="edit_event_ajax(event, document.getElementById('e_brief').value, document.getElementById('e_details').value);">
                            <h2 style="text-align: center;">Edit Event.</h2>
                            <span id="e_msg"></span><br/>
                            <input type='hidden' name='<?php echo(ini_get('session.upload_progress.name')); ?>' value='edit'/>
                            <div id='e_prog_cont' class='e_prog_cont'>
                                <div id='e_prog_val' class='e_prog_val' style='width: 0%;'></div>
                            </div>
                            <input type='hidden' name='event_id' id='event_id'/>
                            <label for="e_brief">Brief</label>
                            <input type="text" name="brief" id="e_brief" oninput='document.getElementById("e_brief_count").innerHTML = count_rem(this.value.length, 30, "e_brief_count");' onblur='document.getElementById("e_brief_count").style.visibility = "hidden";' onfocus='document.getElementById("e_brief_count").style.visibility = "visible"; document.getElementById("e_brief_count").innerHTML = count_rem(this.value.length, 30, "e_brief_count");'/><br>
                            <span id='e_brief_count'></span><br/>
                            <div class='e_preview' id='e_preview'></div>
                            <label for="e_upload1">Replace/Add first file</label>
                            <input type="file" name="upload1" id="e_upload1"/>
                            <label for="e_upload2">Replace/Add second file</label>
                            <input type="file" name="upload2" id="e_upload2"/>
                            <label for="e_upload3">Replace/Add third file</label>
                            <input type="file" name="upload3" id="e_upload3"/>
                            <label for='e_date_of_event'>Date of Event</label>
                            <div class='date_of_event' id='e_date_of_event'>
                                <select name='day' id='e_day' class='input2'>
                                    <?php
                                        for ($i=1; $i <= 31; $i++) {
                                            echo("\n<option value='" . $i . "'>" . $i . "</option>");
                                        }
                                    ?>
                                </select>
                                <select name='month' id='e_month' class='input2'>
                                    <?php
                                        $month = array("01" => "January", "02" => "February", "03" => "March", "04" => "April", "05" => "May", "06" => "June", "07" => "July", "08" => "August", "09" => "September", "10" => "October", "11" => "November", "12" => "December");
                                        foreach ($month as $mon_no => $mon_val) {
                                            echo("\n<option value='" . $mon_no . "'>" . $mon_val . "</option>");
                                        }
                                    ?>
                                </select>
                                <select name='year' id='e_year' class='input2'>
                                    <?php
                                        for ($i=1947; $i <= $year_now; $i++) {
                                            echo("\n<option value='" . $i . "'>" . $i . "</option>");
                                        }
                                    ?>
                                </select>
                            </div>
                            <label for="e_details">Details</label>
                            <textarea name="details" id="e_details" oninput='document.getElementById("e_desc_count").innerHTML = count_rem(this.value.length, 8000, "e_desc_count");' onblur='document.getElementById("e_desc_count").style.visibility = "hidden";' onfocus='document.getElementById("e_desc_count").style.visibility = "visible"; document.getElementById("e_desc_count").innerHTML = count_rem(this.value.length, 8000, "e_desc_count");'></textarea><br/>
                            <span id='e_desc_count'></span><br/>
                            <div>
                                <div class="form_div">
                                    <button type="submit">SAVE</button>
                                    <button type="reset" style='background-color: red; color: whitesmoke;' onclick='document.getElementById("e_msg").innerHTML ="";'>CLEAR</button>
                                </div>
                            </div>
                        </form>
                    </div>
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
                <span onclick='pagination_refresh_ajax("prev")' title='Previous.'>&laquo;</span>
                <span onclick='pagination_refresh_ajax("next")' title='Next.'>&raquo;</span>
            </div>
        </div>
        <script src="js/script.js"></script>
    </body>
</html>
