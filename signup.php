<?php
    //remember to check telephone to make sure it are number and not any other type of data
    //remember to send error as mail if database doesn't connect
    ini_set('display_errors', 'Off');
    ini_set('error_log', 'errors.log');
    ini_set('log_errors_max_len', '0');
    ini_set('upload_max_filesize', '0M');
    session_start();
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Cache-Control: no-cache");
    header("Pragma: no-cache");
    require_once("connection.php");
    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        $continue = 1;
        $fields = array('fullname', 'gender', 'day', 'month', 'year', 'phone_number', 'username', 'password', 'cpassword');
        foreach ($fields as $field) {
            if (isset($_POST[$field]) && $continue != 0) {
                $continue = 1;
            } else {
                // $err = "The server received an incomplete request!" . $field;
                echo("The server received an incomplete request!" . $field);
                $continue = 0;
            }
        }
        if ($continue == 1) {
            check_fullname();
        }
    }

    function check_fullname() {
        global $err, $_POST, $fullname;
        $fullname = sanitize_input($_POST['fullname']);
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
        global $err, $_POST, $gender;
        $gender = sanitize_input($_POST['gender']);
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
        global $err, $_POST, $year, $month, $day;
        $year = sanitize_input($_POST['year']);
        $month = sanitize_input($_POST['month']);
        $day = sanitize_input($_POST['day']);
        if(empty($year) || empty($month) || empty($day)){
            $err = "Input your date of birth!";
            return;
        }
        if (is_date_valid($year . '-' . $month . '-' . $day) !== TRUE) {
            $err = "Input a valid date of birth!";
            return;
        }
        check_phone_number();
    }

    function check_phone_number() {
        global $err, $_POST, $phone_number;
        $phone_number = sanitize_input($_POST['phone_number']);
        if(empty($phone_number)) {
            $err = "Input your phone number!";
            return;
        }
        if(is_numeric($phone_number) !== TRUE){
            $err = "Phone number can only have numeric characters!";
            return;
        }
        check_username();
    }

    function check_username() {
        global $err, $_POST, $username;
        $username = sanitize_input($_POST['username']);
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
        require('connection.php');
        $stmt = $conn -> prepare("SELECT `userID` FROM `users` WHERE `username`= ?;");
        $stmt -> bind_param("s", $username);

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
        check_password();
    }

    function check_password() {
        global $err, $_POST, $password;
        $password = sanitize_input($_POST['password']);
        $cpassword = sanitize_input($_POST['cpassword']);
        if(empty($password)) {
            $err = "You need a password to secure your lifelog!";
            return;
        }
        if (empty($cpassword)) {
            $err = "Confirm your password to proceed!";
            return;
        }
        if (check_string_length($password, 6, "lt") === FALSE) {
            $err = "Password must have at least 6 characters!";
            return;
        }
        if (check_string_length($password, 20, "gt") === FALSE) {
            $err = "Password must not have more than 20 characters!";
            return;
        }
        if ($password != $cpassword) {
            $err = "Passwords do not match!";
            return;
        }
        insert_values();
    }

    function insert_values() {
        global $err, $fullname, $gender, $year, $month, $day, $phone_number, $username, $password;
        require("connection.php");
        $stmt = $conn -> prepare("INSERT INTO `users`(`fullName`, `gender`, `dateOfBirth`, `phoneNumber`, `userName`, `passWord`) VALUES(?, ?, ?, ?, ?, ?);");
        $stmt -> bind_param("ssssss", $fullname, $gender, $date_of_birth, $phone_number, $username, $password);
        $fullname = ucfirst(strtolower($fullname));
        $username = strtolower($username);
        $date_of_birth = $year . "-" . $month . "-" . $day;
        $password = password_hash($password, PASSWORD_BCRYPT);

        if ($stmt -> execute()) {
            $stmt = $conn -> prepare("SELECT `userID` FROM `users` WHERE `userName`= ?;");
            $stmt -> bind_param("s", $username);

            if ($stmt -> execute()) {
                $result = $stmt -> get_result();
            } else {
                $err = "Sorry, an error occurred, we are trying to fix it as soon as possible!";
                return;
            }

            if ($result -> num_rows > 0) {
                $row = $result -> fetch_assoc();
                $_SESSION['lifelog_new_owner'] = $row['userID'];
                header("Location: home.php");
            } else {
                $err = "Signed up successfully but could not automatically sign in, use the sign in page.";
            }
            $fullname = $surname = $gender = $date_of_birth = $phone_number = $username = $password = $cpassword = "";
        } else {
            $err = "Sorry, an error occured, we are trying to fix it as soon as possible.";
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
<html>
    <head>
        <title>
            My lifelog - Sign Up.
        </title>
        <meta charset="UTF-8">
        <meta name="description" content="I'm a friend and lifelog that you can share things with.">
        <meta name="keywords" content="lifelog, friend, events, communicate, signup">
        <meta name="author" content="Dml Ademola, TechWise LLC.">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <?php
            if ($_SESSION['theme'] == 'dark') {
                echo('<meta name="theme-color" content="rgb(1, 1, 34)"/>');
            } else {
                echo('<meta name="theme-color" content="whitesmoke"/>');
            }
        ?>
        <link rel="icon" href="images/lifelog_lg.jpg" type="image/jpg"/>
        <link rel="stylesheet" href="css/style.css"/>
        <?php
            if ($_SESSION['theme'] == 'dark') {
                echo('<link rel="stylesheet" href="css/dark_theme.css"/>');
            } else {
                echo('<link rel="stylesheet" href="css/light_theme.css"/>');
            }
        ?>
    </head>
    <body>
        <div class="header1" id="signup_head">
            <h1>My lifelog.</h1>
        </div>
        <div class="container1">
            <div class="top1">
                <div class="div1">
                    <h2>Sign Up.</h2>
                </div>
                <div style="text-align: right;">
                    <a href="signin.php">Sign In.</a>
                </div>
            </div>
            <form method="POST" action="<?php echo(sanitize_input($_SERVER['PHP_SELF'], TRUE));?>" name="signup" class="form1">
                <?php if(isset($err)) {echo("<p class=\"err\">" . $err . "</p>"); unset($err);}; ?>
                <label for="fullname" class="label1">Full Name</label>
                <div class="input_group">
                    <input type="text" class="input1" id="fullname" name="fullname" placeholder ="John Doe" value="<?php if(isset($fullname)){ echo ($fullname);} ?>" autocomplete="off"/>
                    <span class="post_input" style="padding-right: 10px;" onclick="empty_this(document.getElementById('fullname'))">&times;</span>
                </div>
                <div class="div">
                    <fieldset>
                        <legend class="label1">Gender</legend>
                        <div class="radio_input1">
                            <input type="radio" class="radio_input" id="rather_not_say" name="gender" value="r" <?php if((isset($gender) && $gender == "r") || empty($gender)){ echo ("checked");} ?>/>
                            <label for="rather_not_say" style="margin: 0;">Rather not say.</label>
                        </div>
                        <div class="radio_input1">
                            <input type="radio" class="radio_input" id="male" name="gender" value="m" <?php if(isset($gender) && $gender == "m"){ echo ("checked");} ?>/>
                            <label for="male" style="margin: 0;">Male.</label>
                        </div>
                        <div class="radio_input1">
                            <input type="radio" class="radio_input" id="female" name="gender" value="f" <?php if(isset($gender) && $gender == "f"){ echo ("checked");} ?>/>
                            <label for="female" style="margin: 0;">Female.</label>
                        </div>
                    </fieldset>
                </div>
                <label for="date_of_birth" class="label1">Date of Birth</label>
                <div class='date_of_birth'>
                        <div class='flex_div'>
                            <select name='day' id='day' class='input2'>
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
                        </div>
                        <div class='flex_div'>
                            <select name='month' id='month' class='input2'>
                                <?php
                                    $fmonth_now = date("m");
                                    $month = array("1" => "January", "2" => "February", "3" => "March", "4" => "April", "5" => "May", "6" => "June", "7" => "July", "8" => "August", "9" => "September", "10" => "October", "11" => "November", "12" => "December");
                                    foreach ($month as $mon_no => $mon_val) {
                                        if ($mon_no == $fmonth_now) {
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
                    </div>
                <label for="telephone_number" class="label1">Telephone Number</label>
                <div class="input_group">
                    <input type="text" class="input1" id="telephone_number" name="phone_number" placeholder="01**********56" value="<?php if(isset($phone_number)) {echo($phone_number);}; ?>" autocomplete="off"/>
                    <span class="post_input" style="padding-right: 10px;" onclick="empty_this(document.getElementById('telephone_number'))">&times;</span>
                </div>
                <label for="username" class="label1">Username</label>
                <div class="input_group">
                    <input type="text" class="input1" id="username" name="username" placeholder="john_doe" value="<?php if(isset($username)) {echo($username);};?>" autocomplete="off"/>
                    <span class="post_input" style="padding-right: 10px;" onclick="empty_this(document.getElementById('username'))">&times;</span>
                </div>
                <label for="password" class="label1">Password</label>
                <div class="input_group">
                    <input type="password" class="input1" id="password" name="password" value="" autocomplete="off"/>
                    <span class="post_input icon1 icon" onclick="psw_vis(document.getElementById('password'), this)"></span>
                </div>
                <label for="cpassword" class="label1">Confirm Password</label>
                <div class="input_group">
                    <input type="password" class="input1" id="cpassword" name="cpassword" value="" autocomplete="off"/>
                    <span class="post_input icon1 icon" onclick="psw_vis(document.getElementById('cpassword'), this)"></span>
                </div>
                <div class="center div">
                    <button type="submit" class="btn1">SIGN UP.</button>
                </div>
            </form>
            <p class="footer1">&copy; <?php echo date("Y"); ?> My lifelog.</p>
            <script src="js/script.js"></script>
        </div>
    </body>
</html>
