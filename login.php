<?php
require_once('auth.php');
require_once('config.php');
require_once('lang/'.config::$lang.'.php');
$logincomplete=false;
if(isset($_POST["kill"])) {
  auth::logout();
}
if(!isset($_POST['g-recaptcha-response']))
  $_POST['g-recaptcha-response']="";
if (isset($_POST["sub"])&&$_POST["pass"]&&$_POST["user"]) {
  $logincomplete = auth::logon($_POST["user"],$_POST["pass"],$_POST["otp"],$_POST['g-recaptcha-response']);
}
else {
  $vid=auth::verify();
}
echo ('
  <html lang="de">
    <head>
      <meta http-equiv="content-type" content="text/html; charset=utf-8">
      <link rel="stylesheet" href="inc/css/bootstrap.min.css"> <!-- Bootstrap CSS laden -->
      <script src="https://www.google.com/recaptcha/api.js" async defer></script> <!-- reCaptcha -->
    </head>
    <body>
      <div class="container">
      <br>
      <br>
      <div class="jumbotron">
      <form method="post" action="login.php" class="form-signin">');
if(!isset($vid))
  $vid=false;
if(isset($_POST["user"])&&$_POST["user"])
  $val=$_POST["user"];
else
  $val="";
if(!((ctype_digit($vid)&&$vid)||$logincomplete)) {
  echo('
  <input type="text" class="form-control" name="user" placeholder="Banutzername" value="'.$val.'" style="margin-left: auto;margin-right: auto;width:50%;">
  <br>
  <input type="password" class="form-control" name="pass" placeholder="passwort" style="margin-left: auto;margin-right: auto;width:50%;">
  <br>
  <input type="text" maxlength="6" class="form-control" name="otp" placeholder="OTP, wenn du nicht weißt was das ist, leerlassen" autocomplete="off" style="margin-left: auto;margin-right: auto;width:50%;">
  <br>');
if(isset(config::$cappub)&&config::$cappub&&isset(config::$capkey)&&config::$capkey)
  echo ('<script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <div class="g-recaptcha" data-sitekey="'.config::$cappub.'" style="margin-left: auto;margin-right: auto; display: table;"></div>
    <noscript>
      <div style="width: 302px; height: 352px; margin-left: auto;margin-right: auto;">
        <div style="width: 302px; height: 352px; position: relative;">
          <div style="width: 302px; height: 352px; position: absolute;">
            <iframe src="https://www.google.com/recaptcha/api/fallback?k='.config::$cappub.'"
                    frameborder="0" scrolling="no"
                    style="width: 302px; height:352px; border-style: none;">
            </iframe>
          </div>
          <div style="width: 250px; height: 80px; position: absolute; border-style: none;
                      bottom: 21px; left: 25px; margin: 0px; padding: 0px; right: 25px;">
            <textarea id="g-recaptcha-response" name="g-recaptcha-response"
                      class="g-recaptcha-response"
                      style="width: 250px; height: 80px; border: 1px solid #c1c1c1;
                      margin: 0px; padding: 0px; resize: none;" value=""></textarea>
          </div>
        </div>
      </div>
    </noscript>');
echo('<br>
<input type="submit" value="absenden" name="sub" class="btn btn-lg btn-success btn-block" style="margin-left: auto;margin-right: auto;width:25%;"><br>');
  if(isset(config::$clfpub)&&config::$clfpub)
  echo ('<div style="margin-left:auto; margin-right:auto; display: table; font-size: 2em;">ODER<br></div>
    <div class="clef-wrapper" style="margin-left:auto; margin-right:auto; width:188px;">
      <script data-type="login" data-redirect-url="http://localhost/oli/clef.php?type=clef"  data-style="button" data-color="blue" data-app-id="'.config::$clfpub.'"  class="clef-button" src="https://clef.io/v3/clef.js" type="text/javascript"></script>
    </div>');
}
else {
  if(!$vid)
    $vid=$logincomplete;
  echo('<input type="submit" value="ausloggen" name="kill" class="btn btn-lg btn-warning btn-block" style="margin-left: auto;margin-right: auto;width:25%;">');
}

if(ctype_digit($vid)&&$vid){
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
