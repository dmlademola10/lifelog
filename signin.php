<?php
    ini_set('display_errors', 'Off');
    ini_set('error_log', 'errors.log');
    ini_set('log_errors_max_len', '0');
    ini_set('upload_max_filesize', '0M');
    session_start();
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Cache-Control: no-cache");
    header("Pragma: no-cache");

    if(isset($_SESSION['lifelog_owner'])) {
        header("Location: home.php");
    }

    if($_SERVER['REQUEST_METHOD'] == "POST" && (isset($_POST['username']) && isset($_POST['password']))) {
        $username = sanitize_input($_POST['username']);
        $password = sanitize_input($_POST['password'], FALSE);
        check_username();
    }

    function check_username() {
        global $username, $err;
        require_once("connection.php");

        if (empty($username)) {
            $err = "Username field can't be empty, <a href='signup.php' class='b_a'>get a free lifelog</a> if you don't have one!";
            return;
        }
        $stmt = $conn -> prepare("SELECT `userID`, `passWord` FROM `users` WHERE `username`= ?");
        $stmt -> bind_param("s", $username);

        if ($stmt -> execute()) {
            $result = $stmt -> get_result();
        } else {
            $err ="Sorry, i think i have a problem, please, contact the admin.";
            return;
        }
        if ($result -> num_rows == 1) {
            while ($row = $result -> fetch_assoc()) {
                $userid = $row['userID'];
                $hash_psw = $row['passWord'];
            }
        } else {
            $err = "That user does not exist, <a href='signup.php' class='b_a'>get a free lifelog</a> instead!";
            return;
        }
        check_password($userid, $hash_psw);
    }

    function check_password($userid, $hash_psw) {
        global $password, $err;
        if (empty($password)) {
            $err = "Password can't be empty, have you <a href='#' class='b_a'>forgotten it?</a>";
            return;
        }
        if(password_verify($password, $hash_psw) !== TRUE){
            $err = "That password isn't correct, have you <a href='#' class='b_a'>forgotten it?</a>";
            return;
        } else {
            $_SESSION['lifelog_owner'] = $userid;
            header("Location: home.php");
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
<!DOCTYPE HTML>
<html>
    <head>
        <title>
            My lifelog.
        </title>
        <meta charset="UTF-8">
        <meta name="description" content="I'm a friend and lifelog that you can share things with.">
        <meta name="keywords" content="lifelog, friend, events, communicate, signin, login">
        <meta name="author" content="Dml Ademola, TechWise LLC.">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <?php
            if (isset($_SESSION['theme']) && $_SESSION['theme'] == 'dark') {
                echo('<meta name="theme-color" content="rgb(1, 1, 34)"/>');
            } else {
                echo('<meta name="theme-color" content="whitesmoke"/>');
            }
        ?>
        <link rel="icon" href="images/lifelog_lg.jpg" type="image/jpg"/>
        <link rel="stylesheet" href="css/style.css"/>
        <?php
            if (isset($_SESSION['theme']) && $_SESSION['theme'] == 'dark') {
                echo('<link rel="stylesheet" href="css/dark_theme.css"/>');
            } else {
                echo('<link rel="stylesheet" href="css/light_theme.css"/>');
            }
        ?>
        <style type="text/css">
            html, body{
                display: flex;
                justify-content: center;
                align-items: center;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header1">
                <h1 class="site_title">My lifelog.</h1>
            </div>
            <form name="signin" action="<?php echo(htmlspecialchars($_SERVER['PHP_SELF'])); ?>" method="POST">
                <?php if(isset($err)) {echo("<p class=\"err\">" . $err . "</p>");unset($err);} ?>
                <label for="username">Username</label>
                <div class="input_group">
                    <input type="text" name="username" class="input1" value="<?php if(isset($username)) { echo($username); } ?>" id='username' autocomplete="off" />
                    <span class="post_input" style="padding-right: 10px;" onclick="empty_this(document.getElementById('username'))">&times;</span>
                </div>
                <label for="password">Password</label>
                <div class="input_group">
                    <input type="password" name="password" class="input1" id="password"/>
                    <span class="post_input icon1 icon" onclick="psw_vis(document.getElementById('password'), this)" id='psw_btn'></span>
                </div>
                <button type="submit" class="btn1">SIGN IN</button><br/>
                <p style="text-align: center; margin: 0;"><a href="signup.php">Sign Up</a></p>
            </form>
            <noscript>You can't use this lifelog if javascript isn't supported by your browser</noscript>
        </div>
        <script src="js/script.js"></script>
    </body>
</html>
