<?php
echo ('<html lang="de">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
<link rel="stylesheet" href="inc/css/bootstrap.min.css"> <!-- Bootstrap CSS laden -->
</head>
<body>
<div class="container">
<br>
<br>
<div class="jumbotron">');

require_once('auth.php');
require_once('config.php');
require_once('lang/'.config::$lang.'.php');
$logincomplete=false;
if (isset($_POST["sub"])&&$_POST["pass"]&&$_POST["user"]) {
//die("<br /><br />".$_POST["user"]."<br />".hash("sha512",hash("sha512",$_POST["pass"])));
$logincomplete = auth::logon($_POST["user"],$_POST["pass"]);
//echo "logon:".$result; //debug Multiuser
}
if(isset($_POST["kill"])) {
auth::logout();
}
echo '<form method="post" action="login.php" class="form-signin">';

if(!(auth::verify()||$logincomplete))
echo('
<input type="text" class="form-control" name="user" placeholder="Banutzername">
<br>
<input type="password" class="form-control" name="pass" placeholder="passwort">
<br>
<input type="submit" value="absenden" name="sub" class="btn btn-lg btn-success btn-block" style="margin-left: auto;margin-right: auto;width:50%;">');
else
echo('<input type="submit" value="ausloggen" name="kill" class="btn btn-lg btn-warning btn-block" style="margin-left: auto;margin-right: auto;width:50%;">');


$vid=auth::verify();
if($vid||$logincomplete){
if(!$vid)
$vid=$logincomplete;
echo("<div>login OK hier gehts weiter</div><br>".$vid."(verify)<br>"/* -> debug*/);
if(auth::isadmin($vid)) echo "admin";
//echo($_SERVER['HTTP_USER_AGENT']); //debug
}
echo('</form>
</div>
</div>
</body>
</html>')
?>
