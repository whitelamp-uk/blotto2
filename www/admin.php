<?php

$cfgs                       = glob ('/home/blotto/config/*.cfg.php');
$configs                    = array ();
foreach ($cfgs as $cfg) {
    $lines                  = file ($cfg);
    $name                   = 'Unnamed';
    foreach ($lines as $line) {
        if (strpos($line,'BLOTTO_ORG_NAME')) {
            $matches        = array ();
            preg_match ("<,\s+'(.*)'>",$line,$matches);
            if (array_key_exists(1,$matches)) {
                $name       = $matches[1];
            }
            break;
        }
    }
    $configs[$cfg]          = $name;
}

$errors = array (
    UPLOAD_ERR_INI_SIZE     => "PHP upload_max_filesize directive was exceeded",
    UPLOAD_ERR_FORM_SIZE    => "HTML MAX_FILE_SIZE directive was exceeded",
    UPLOAD_ERR_PARTIAL      => "Only partially uploaded",
    UPLOAD_ERR_NO_FILE      => "No file uploaded",
    UPLOAD_ERR_NO_TMP_DIR   => "No upload temporary directory",
    UPLOAD_ERR_CANT_WRITE   => "Failed to write temporary file",
    UPLOAD_ERR_EXTENSION    => "Prevented by unknown PHP extension"
);


$error = false;
$msg = false;

if (count($_POST)) {

    if (!$error && (!array_key_exists('cfg',$_POST) || !array_key_exists($_POST['cfg'],$configs))) {
        $error = 'Config is not valid';
    }

    if (!$error && (!array_key_exists('usr',$_POST) || !array_key_exists('pwd',$_POST))) {
        $error = 'User/password not given';
    }

    if (!$error && (!strlen($_POST['usr']) || !strlen($_POST['pwd']))) {
        $error = 'Username or password empty';
    }

    if (!$error) {
        require $_POST['cfg'];
        mysqli_report (MYSQLI_REPORT_STRICT);
        try {
            $zo = new mysqli (
                'localhost',$_POST['usr'],$_POST['pwd'],BLOTTO_DB
            );
        }
        catch (mysqli_sql_exception $e) {
            $error = "Could not connect to org database";
        }
    }

    if (!$error) {
        $sane_file_size = BLOTTO_WEB_UPLOAD_MAX_MB;
        // Do stuff here
        $msg = "We will do some stuff here";
    }

}


$calendar = array ();
exec ("/usr/bin/php '".__DIR__."/rsm.calendar.php'",$calendar); 


?><!doctype html>
<html class="no-js" lang="">

  <head>

    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />
    <meta name="description" content="" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <title><?php echo htmlspecialchars (BLOTTO_BRAND); ?> admin</title>

    <style>
a {
    float: right;
    font-weight: bold;
}
pre {
    float: left;
    width: 16vw;
    margin-left: 2vw;
}
#calendar {
    margin-bottom: 0.4em;
    border-style: solid;
    border-width: 1px;
    border-radius: 0.3em;
    padding: 0.2em;
    text-align: center;
}
.msg {
    font-weight: bold;
    color: #884400;
}
.error {
    color: #880000;
}
.warning {
    color: #e65c00;
}
.ok {
    color: #008800;
}
    </style>

  </head>

  <body>

    <div id="calendar"><strong>RSM upload schedule</strong> &nbsp; &nbsp; latest: <strong><?php echo $calendar[0]; ?></strong> &nbsp; &nbsp; next: <strong><?php echo $calendar[1]; ?></strong></div>

    <form method="post" enctype="multipart/form-data">
      <select name="cfg">
        <option value="">Select org config:</option>
<?php foreach($configs as $cfg=>$name): ?>
        <option value="<?php echo htmlspecialchars($cfg); ?>" <?php if(array_key_exists('cfg',$_POST) && $cfg==$_POST['cfg']): ?> selected <?php endif; ?> ><?php echo htmlspecialchars($name); ?></option>
<?php endforeach; ?>
      </select>
      <input type="text" name="usr" placeholder="User" value="<?php if(array_key_exists('usr',$_POST)) {echo htmlspecialchars($_POST['usr']);} ?>" />
      <input type="password" name="pwd" placeholder="Password" value="<?php if(array_key_exists('pwd',$_POST)) {echo htmlspecialchars($_POST['pwd']);} ?>" />
      <input type="submit" name="upload" value="Go" />
    </form>

<?php if($error): ?>
    <p class="error"><?php echo nl2br(htmlspecialchars($error)); ?></p>
<?php endif; ?>

<?php if($msg): ?>
    <p class="msg"><?php echo nl2br(htmlspecialchars($msg)); ?></p>
<?php endif; ?>


  </body>

</html>

