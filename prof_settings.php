<?php
    ini_set('display_errors', 'Off');
    ini_set('error_log', 'errors.log');
    ini_set('log_errors_max_len', '0');
    ini_set('upload_max_filesize', '5120M');
    require('connection.php');
    require_once("session.php");
    if(!isset($_SESSION['lifelog_owner'])){
        return;
    }
    $stmt = $conn -> prepare("SELECT `fullName`, `gender`, `dateOfBirth`, `phoneNumber`, `email`, `userName`, `noOfEvents`, `theme` FROM `users` WHERE `userID` = ?;");
    $stmt -> bind_param("i", $_SESSION['lifelog_owner']);
    if ($stmt -> execute()) {
        $result = $stmt -> get_result();
        $row = $result -> fetch_assoc();
        $fullname = $row['fullName'];
        $gender = $row['gender'];
        $date_of_birth = $row['dateOfBirth'];
        $phone_number = $row['phoneNumber'];
        $email = $row['email'];
        $username = $row['userName'];
        $no_of_events = $row['noOfEvents'];
        $theme = $row['theme'];
        $_SESSION['theme'] = $theme;
    } else {
        $err = "An error occured!";
    }
    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        $continue = 1;
        $fields = array('fullname', 'gender', 'day', 'month', 'year', 'phone_number', 'email', 'username', 'password', 'npassword', 'cpassword', 'no_of_events', 'theme');
        foreach ($fields as $field) {
            if (!isset($_POST[$field]) || $continue != 1) {
                $err = "The server received an incomplete request!";
                $continue = 0;
            } else {
                $continue = 1;
            }
        }
        if ($continue == 1) {
            $fullname = sanitize_input($_POST['fullname']);
            $gender = sanitize_input($_POST['gender']);
            $date_of_birth = sanitize_input($_POST['year']) . '-' . sanitize_input($_POST['month']) . '-' . sanitize_input($_POST['day']);
            $phone_number = sanitize_input($_POST['phone_number']);
            $email = sanitize_input($_POST['email']);
            $username = sanitize_input($_POST['username']);
            $no_of_events = sanitize_input($_POST['no_of_events']);
            $theme = sanitize_input($_POST['theme']);
            check_fullname();
        }
    }

    function check_fullname() {
        global $err, $fullname;
        if (empty($fullname)) {
            $err = "Your name was not given!";
            return;
        }
        if (!preg_match("/^[a-zA-Z ]*$/", $fullname)) {
            $err = "Your name can only contain letters and whitespaces!";
            return;
        }
        if (check_string_length($fullname, 3, "lt") === FALSE){
            $err = "Your name must be at least 3 characters!";
            return;
        }
        if (check_string_length($fullname, 20, "gt") === FALSE) {
            $err = "Your name must not be more than 20 characters!";
            return;
        }
        check_gender();
    }

    function check_gender() {
        global $err, $gender;
        if (empty($gender)) {
            $err = "Select a gender!";
            return;
        }
        if ($gender != "r" && $gender != "m" && $gender != "f") {
            $err = "Select a valid gender!";
            $gender = "r";
            return;
        }
        check_date_of_birth();
    }

    function check_date_of_birth() {
        global $err, $date_of_birth;
        if (is_date_valid($date_of_birth) !== TRUE) {
            $err = "Input a valid date of birth!";
            return;
        }
        check_phone_number();
    }

    function check_phone_number() {
        global $err, $phone_number;
        if(empty($phone_number)) {
            $err = "Input your phone number!";
            return;
        }
        if(is_numeric($phone_number) !== TRUE){
            $err = "Phone number can only have numeric characters!";
            return;
        }
        check_email();
    }

    function check_email() {
        global $err, $email;
        if (empty($email)) {
            check_username();
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) === TRUE) {
            $err = 'Input a valid email!';
            return;
        }
        check_username();
    }

    function check_username() {
        global $conn, $err, $username;
        if(empty($username)) {
            $err = "You need a username to become a lifelog owner!";
            return;
        }
        if (!preg_match("/^[A-Za-z][A-Za-z0-9]*(?:_[A-Za-z0-9]+)*$/", $username)){
            $err = "Username can only contain alphanumeric characters and underscores within them!";
            return;
        }
        if (check_string_length($username, 5, "lt") === FALSE){
            $err = "Username cannot have less than 5 characters!";
            return;
        }
        if (check_string_length($username, 20, "gt") === FALSE){
            $err = "Username cannot have more than 20 characters!";
            return;
        }
        $stmt = $conn -> prepare("SELECT `userID` FROM `users` WHERE `username`= ? AND `userID` != ?;");
        $stmt -> bind_param("si", $username, $_POST['lifelog_owner']);

        if($stmt -> execute()) {
            $result = $stmt -> get_result();
        } else {
            $err = "Sorry, an error occurred, we are trying to fix it as soon as possible!";
            return;
        }
        if ($result -> num_rows > 0) {
            $err = "User with the same username already exists!";
            return;
        }
        check_no_of_events();
    }

    function check_no_of_events() {
        global $err, $no_of_events;
        if(empty($no_of_events)) {
            $err = "Select the number of events/page you want!";
            return;
        }
        if ($no_of_events != 10 && $no_of_events != 20 && $no_of_events != 30) {
            $err = "An error occurred, reload this page or report if the problem persists!";
            return;
        }
        check_theme();
    }

    function check_theme(){
        global $err, $theme;
        if (empty($theme)) {
            $err = "Select a theme!";
            return;
        }
        if ($theme != 'light' && $theme != 'dark') {
            $err = "An error occurred, reload this page or report if the problem persists!";
            return;
        }
        check_password();
    }

    function check_password() {
        global $conn, $err, $password;
        $password = sanitize_input($_POST['password']);
        $npassword = sanitize_input($_POST['npassword']);
        $cpassword = sanitize_input($_POST['cpassword']);
        if(empty($password)) {
            $err = "Input your password to save changes!";
            return;
        }
        $stmt = $conn -> prepare('SELECT `passWord` FROM `users` WHERE `userID` = ?');
        $stmt -> bind_param('i', $_SESSION['lifelog_owner']);

        if ($stmt -> execute()) {
            $result = $stmt -> get_result();
            $row = $result -> fetch_assoc();
            if (!password_verify($password, $row['passWord'])) {
                $err = 'That password is incorrect!';
                return;
            }
        } else {
            $err = 'An error occurred!';
            return;
        }

        if (empty($npassword)) {
            $password = password_hash($password, PASSWORD_BCRYPT);
            update_values();
            return;
        }
        if (empty($cpassword)) {
            $err = 'Confirm your new password to continue!';
            return;
        }
        if (check_string_length($npassword, 6, "lt") === FALSE) {
            $err = "Your new password must have at least 6 characters!";
            return;
        }
        if (check_string_length($npassword, 20, "gt") === FALSE) {
            $err = "Your new password must not have more than 20 characters!";
            return;
        }
        if ($npassword == $password) {
            $err = 'New and existing passwords shouldn\'t be the same!';
            return;
        }
        if ($npassword != $cpassword) {
            $err = "Passwords do not match!";
            return;
        }
        $password = password_hash($npassword, PASSWORD_BCRYPT);
        update_values();
    }

    function update_values() {
        global $conn, $err, $msg, $fullname, $gender, $date_of_birth, $phone_number, $email, $username, $password, $no_of_events, $theme;
        $stmt = $conn -> prepare("UPDATE `users` SET `fullName` = ?, `gender` = ?, `dateOfBirth` = ?, `phoneNumber` = ?, `email` = ?, `userName` = ?, `passWord` = ?, `noOfEvents` = ?, `theme` = ? WHERE `userID` = ?;");
        $stmt -> bind_param("sssssssssi", $fullname, $gender, $date_of_birth, $phone_number, $email, $username, $password, $no_of_events, $theme, $_SESSION['lifelog_owner']);
        $fullname = ucwords(strtolower($fullname));
        $username = strtolower($username);
        $email = strtolower($email);


        if ($stmt -> execute() === TRUE) {
            $msg = "User details successfully updated.";
            $_SESSION['theme'] = $theme;
        } else {
            $err = "Sorry, an error occurred, we are trying to fix it as soon as possible!";
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

    function check_string_length($str, $length, $type){
        if($type == "lt"){
            if(strlen(html_entity_decode($str, ENT_QUOTES)) < $length){
                return FALSE;
            }
        } elseif ($type == "gt") {
            if(strlen(html_entity_decode($str, ENT_QUOTES)) > $length) {
                return FALSE;
            }
        }
    }

    function is_date_valid($date_of_birth){
        $date_of_birth = date_parse($date_of_birth);
        if(($date_of_birth['error_count'] + $date_of_birth['warning_count']) < 1 && checkdate($date_of_birth['month'], $date_of_birth['day'], $date_of_birth['year']) !== FALSE){
            return TRUE;
        } else {
            return FALSE;
        }
    }


?>
<!DOCTYPE html>
<html lang="EN-GB">
    <head>
        <title>
            My lifelog - Settings.
        </title>
        <meta charset="UTF-8">
        <meta name="description" content="I'm a friend and lifelog that you can share things with."/>
        <meta name="keywords" content="lifelog, friend, events, communicate"/>
        <meta name="author" content="Dml Ademola, TechWise LLC."/>
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <?php
            if (isset($_SESSION['theme']) && $_SESSION['theme'] == 'dark') {
                echo('<meta name="theme-color" content="rgb(1, 1, 34)"id="theme-color"/>');
            } else {
                echo('<meta name="theme-color" content="whitesmoke"id="theme-color"/>');
            }
        ?>
        <link rel="icon" href="images/lifelog_lg.jpg" type="image/jpg"/>
        <link rel="stylesheet" href="css/style.css"/>
        <?php
            if (isset($_SESSION['theme']) && $_SESSION['theme'] == 'dark') {
                echo('<link rel="stylesheet" href="css/dark_theme.css" id="theme"/>');
            } else {
                echo('<link rel="stylesheet" href="css/light_theme.css" id="theme"/>');
            }
        ?>
    </head>
    <body>
        <div class="container1">
            <div class="head" style='border-bottom: 1px solid '>
                <div class="division">
                    <h1>My lifelog.</h1>
                </div>
                <div class="division top_act">
                    <a href="home.php" class='icon icon8' title="Home."></a>
                    <a href="trash.php" class='icon icon4' title="Go to Trash."></a>
                    <a href="prof_settings.php" class='icon icon10 active' title="Profile & Preferences."></a>
                    <a href="signout.php" class='signout icon icon7' title="Sign Out."></a>
                </div>
                <div class="division">
                    <?php
                        echo("<h4>Hola, " . $row['fullName'] . "</h4>");
                    ?>
                </div>
            </div>
            <div class='settings' id='settings'>
                <form name="settings" id="settings" method="post" enctype="multipart/form-data">
                    <h2 style="text-align: center;">Profile & Preferences.</h2>
                    <span id="msg"><?php if (isset($err)) { echo($err); } elseif (isset($msg)) { echo($msg); } ?></span><br/>
                    <fieldset>
                        <legend>Profile</legend>
                        <div class='flex_row'>
                            <div class='flex_div'>
                                <label for='fullname'>Full Name</label>
                                <input type='text' name='fullname' class='input2' id='fullname' value='<?php echo($fullname); ?>'/>
                            </div>
                        </div>
                        <div class='flex_row'>
                            <div class='flex_div'>
                                <label for="gender">Gender</label>
                                <select name='gender' id ='gender' class='input2'>
                                    <option value='r' <?php if ($gender == 'r') {echo("selected");} ?> >Rather not say</option>
                                    <option value='m' <?php if ($gender == 'm') {echo("selected");} ?> >Male</option>
                                    <option value='f' <?php if ($gender == 'f') {echo("selected");} ?> >Female</option>
                                </select>
                            </div>
                        </div>
                        <label for='date_of_birth'>Date of Birth</label>
                        <div class='flex_row'>
                            <div class='flex_div'>
                                <select name='day' id='day' class='input2'>
                                    <?php
                                        $dateofbirth = explode('-', $date_of_birth);
                                        print_r($dateofbirth);
                                        for ($i=1; $i <= 31; $i++) {
                                            if ($i == $dateofbirth[2]) {
                                                echo("\n<option value='" . $i . "' selected>" . $i . "</option>");
                                                continue;
                                            }
                                            echo("\n<option value='" . $i . "'>" . $i . "</option>");
                                        }
                                    ?>
                                </select>
                            </div>
                            <div class='flex_div'>
                                <select name='month' id='month' class='input2'>
                                    <?php
                                        $month = array("1" => "January", "2" => "February", "3" => "March", "4" => "April", "5" => "May", "6" => "June", "7" => "July", "8" => "August", "9" => "September", "10" => "October", "11" => "November", "12" => "December");
                                        foreach ($month as $mon_no => $mon_val) {
                                            if ($mon_no == $dateofbirth[1]) {
                                                echo("\n<option value='" . $mon_no . "' selected>" . $mon_val . "</option>");
                                                continue;
                                            }
                                            echo("\n<option value='" . $mon_no . "'>" . $mon_val . "</option>");
                                        }
                                    ?>
                                </select>
                            </div>
                            <div class='flex_div'>
                                <select name='year' id='year' class='input2'>
                                    <?php
                                        $year_now = date("Y");
                                        for ($i = 1922; $i <= $year_now; $i++) {
                                            if ($i == $dateofbirth[0]) {
                                                echo("\n<option value='" . $i . "' selected>" . $i . "</option>");
                                                continue;
                                            }
                                            echo("\n<option value='" . $i . "'>" . $i . "</option>");
                                        }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class='flex_row'>
                            <div class='flex_div'>
                                <label for='phone_number'>Phone Number</label>
                                <input type='text' name='phone_number' class='input2' id='phone_number' value='<?php echo($phone_number); ?>'/>
                            </div>
                        </div>
                        <div class='flex_row'>
                            <div class='flex_div'>
                                <label for='email'>Email</label>
                                <input type='email' name='email' class='input2' id='email' value='<?php echo($email); ?>'/>
                            </div>
                        </div>
                    </fieldset>
                    <fieldset>
                        <legend>Preferences/Others</legend>
                        <div class='flex_row'>
                            <div class='flex_div'>
                                <label for='username'>Username</label>
                                <input type='text' name='username' class='input2' id='username' value='<?php echo($username); ?>'/>
                            </div>
                        </div>
                        <div class='flex_row'>
                            <div class='flex_div'>
                                <label for="no_of_events">Number of events/page</label>
                                <select name='no_of_events' id ='no_of_events' class='input2'>
                                    <option value='10' <?php if ($no_of_events == 10) {echo("selected");} ?> >10(Faster)</option>
                                    <option value='20' <?php if ($no_of_events == 20) {echo("selected");} ?> >20</option>
                                    <option value='30' <?php if ($no_of_events == 30) {echo("selected");} ?> >30(Slower)</option>
                                </select>
                            </div>
                            <div class='flex_div'>
                                <label for='theme'>Theme</label>
                                <select name='theme' id ='theme' class='input2'>
                                    <option value='light' <?php if ($theme == 'light') {echo("selected");} ?>>Light.</option>
                                    <option value='dark' <?php if ($theme == 'dark') {echo("selected");} ?>>Dark.</option>
                                </select>
                            </div>
                        </div>
                        <div class='flex_row'>
                            <div class='flex_div'>
                                <label for='password'>Password</label>
                                <input type='password' name='password' class='input2' id='password' value=''/>
                            </div>
                        </div>
                        <div class='flex_row'>
                            <div class='flex_div'>
                                <label for='npassword'>New Password</label>
                                <input type='password' name='npassword' class='input2' id='npassword' value=''/>
                            </div>
                        </div>
                        <div class='flex_row'>
                            <div class='flex_div'>
                                <label for='cpassword'>Confirm New Password</label>
                                <input type='password' name='cpassword' class='input2' id='cpassword' value=''/>
                            </div>
                        </div>
                    </fieldset>
                    <div class='form_div'>
                        <button type='submit'>Save Changes.</button>
                    </div>
                </form>
                <?php
                    $stmt = $conn -> prepare("SELECT CHAR_LENGTH(`brief`) AS briefLen, CHAR_LENGTH(`details`) AS descLen, CHAR_LENGTH(`timeOfEvent`) AS toeLen, CHAR_LENGTH(`upload1`) AS up1Len, CHAR_LENGTH(`upload2`) AS up2Len, CHAR_LENGTH(`upload3`) AS up3Len, CHAR_LENGTH(`trashed`) AS trashedLen, CHAR_LENGTH(`entryTime`) AS enttimeLen, `upload1`, `upload2`, `upload3` FROM `events` WHERE `userID` = ?;");
                    $stmt -> bind_param("i", $_SESSION['lifelog_owner']);

                    if ($stmt -> execute()) {
                        $result = $stmt -> get_result();
                    } else {
                        echo("Sorry, an error occurred!");
                    }
                    if ($result -> num_rows >= 0) {
                        $used = 0;
                        while ($row = $result -> fetch_array()) {
                            $size = ((4 * $row['briefLen']) + 2) + ((4 * $row['descLen']) + 3) + ((4 * $row['toeLen']) + 2) + ((4 * $row['up1Len']) + 2) + ((4 * $row['up2Len']) + 2) + ((4 * $row['up3Len']) + 2) + ((4 * $row['trashedLen']) + 2) + ((4 * $row['enttimeLen']) + 2);
                            if (!empty($row['upload1']) && file_exists("media/" . $row['upload1']) && filesize("media/" . $row['upload1'])) {
                                $size = $size + filesize("media/" . $row['upload1']);
                            }
                            if (!empty($row['upload2']) && file_exists("media/" . $row['upload2']) && filesize("media/" . $row['upload2'])) {
                                $size = $size + filesize("media/" . $row['upload2']);
                            }
                            if (!empty($row['upload3']) && file_exists("media/" . $row['upload3']) && filesize("media/" . $row['upload3'])) {
                                $size = $size + filesize("media/" . $row['upload3']);
                            }
                            $used += $size;
                        }

                        $used_percent = round(($used / 3000000000) * 100, 2, PHP_ROUND_HALF_UP);

                        if ($used_percent < 50) {
                            $bgcolor = "lime";
                        } elseif ($used_percent < 75) {
                            $bgcolor = "yellow";
                        } else {
                            $bgcolor = "red";
                        }

                        echo("<div id='size_cont'><div id='size_used' style=\"width: " . $used_percent . "%; background-color: " . $bgcolor . ";\"></div></div>\n");
                        echo("                <p class='center size'>You have used <span style='color: " . $bgcolor . ";'>" . $used_percent . "%</span>, you have <span style='color: " . $bgcolor . ";'>" . (100 - $used_percent) . "% of 3GB</span> left.</p>");
                    }
                ?>
            </div>
        </div>
        <script src="js/script.js"></script>
    </body>
</html>
