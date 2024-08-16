<?php
session_start();
    // header('Content-Type: text/event-stream');
    // header('Cache-Control: no-cache');
    // header('Access-Control-Allow-Origin: *');

    // $time = date('r');ob_clean();
    // echo "data: The server time is: {$time}\n\n";
    // flush();



    // $var = <<<XML
    // help
    // us
    // XML;
    // $vr = str_ireplace('h', 'nn', $var);
    // echo($vr);
    // echo $var;
    $key = ini_get("session.upload_progress.prefix") . $_POST[ini_get("session.upload_progress.name")];
    var_dump($_SESSION[$key]);
    echo $key;
    print_r($_SESSION[$key]);
?>
<form name='form' method='POST'>
    <input type='hidden' name='<?php echo( ini_get('session.upload_progress.name')); ?>' value='form'>
    <input type='file' name='upload1'>
    <input type='submit'>
</form>
