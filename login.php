<?php
require_once('auth.php');
require_once('config.php');
require_once('lang/'.config::$lang.'.php');
$user=false;
@session_start();
if(isset($_SESSION["msg"]))
  auth::$msg.=$_SESSION["msg"];
session_destroy();
//auth::$msg.=style::warn("lol").style::info("lol").style::success("lol").style::error("lol");  //WISE-debug
 if(isset($_POST["ghostout"])) {
  auth::logout("ghost",auth::verify());
}
if(isset($_POST["kill"])) {
  auth::logout();
}
if(!isset($_POST['g-recaptcha-response']))
  $_POST['g-recaptcha-response']="";
if(!isset($_POST['otp']))
  $_POST['otp']="";
if (isset($_POST["sub"])/*&&$_POST["pass"]&&$_POST["user"]*/) {
  auth::logon($_POST["user"],$_POST["pass"],$_POST["otp"],$_POST['g-recaptcha-response']);
}
else {
  $user=auth::verify();
}
if(isset($_POST["clfdis"])&&$_POST["clfdis"]) {
  auth::clfdis($user);
}
if(!empty(auth::$msg))
  auth::$msg.="<br>";
  echo('<html lang="de">
    <head>
      <meta http-equiv="content-type" content="text/html; charset=utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <link rel="stylesheet" href="inc/css/bootstrap.min.css"> <!-- Bootstrap CSS laden -->
      <link rel="stylesheet" href="inc/css/bootstrap-theme.min.css"> <!-- Bootstrap CSS laden -->
      <link rel="stylesheet" href="inc/css/font-awesome.min.css"> <!-- FontAwesome CSS laden -->
      <link rel="stylesheet" href="inc/css/fa.css"> <!-- FA-Wise CSS laden -->
      <title>'.lang::$loginform.' - '.config::$title.'</title>
      <noscript><style>
      .js {
        display:none;
      }
      </style></noscript>
    </head>
    <body>
      <div class="container">
      <br>
      <br>
      <div class="jumbotron"><h1 class="text-center">'.config::$title.'</h1><h2 class="text-center">'.lang::$loginform.'</h2>'.auth::$msg);
echo ('
      <form method="post" action="login.php" class="form-signin">');
if(!isset($user))
  $user=false;
if(isset($_POST["user"])&&$_POST["user"])
  $val=$_POST["user"];
else
  $val="";
if(!((ctype_digit($user)&&$user)||$user)) {
  echo('
  <input type="text" class="form-control" name="user" placeholder="'.lang::$user.'" value="'.$val.'" style="margin-left: auto;margin-right: auto;min-width:18em;width:50%;">
  <br>
  <input type="password" class="form-control" name="pass" placeholder="'.lang::$pass.'" style="margin-left: auto;margin-right: auto;min-width:18em;width:50%;">');
  if(config::$otpact)
    echo ('<br>
  <input type="text" maxlength="6" class="form-control" name="otp" placeholder="'.lang::$otp.'" autocomplete="off" style="margin-left: auto;margin-right: auto;min-width:18em;width:50%;">');
if(isset(config::$capact)&&config::$capact&&isset(config::$cappub)&&config::$cappub&&isset(config::$capkey)&&config::$capkey)
  echo ('<br>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script> <!-- reCaptcha -->
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
<button type="submit" name="sub" class="btn btn-lg btn-success btn-block" style="margin-left: auto;margin-right: auto;min-width:12em;width:25%;"><i class="fa fa-lg fa-sign-in"></i>&emsp;'.lang::$login.'</button>');
  if(isset(config::$clfact)&&config::$clfact&&isset(config::$clfpub)&&config::$clfpub&&isset(config::$clfkey)&&config::$clfkey)
  echo ('<span class="js"><br><div style="margin-left:auto; margin-right:auto; display: table; font-size: 2em;">ODER</div><br>
    <button type="button" class="clef-button btn btn-block btn-lg btn-info" style="margin-left: auto;margin-right: auto;min-width:12em;width:25%;"
    data-app-id="'.config::$clfpub.'" data-redirect-url="'.config::$clfred.'" data-custom="true"><i class="fa fa-lg fa-mobile"></i>&emsp;'.lang::$clefin.'</button>
    <script type="text/javascript" src="https://clef.io/v3/clef.js"></script></span>');
}
else {
  echo('<button type="submit" name="kill" class="btn btn-lg btn-warning btn-block" style="margin-left: auto;margin-right: auto;min-width:12em;width:25%;"><i class="fa fa-lg fa-sign-out"></i>&emsp;'.lang::$logout.'</button>');
  echo('<button type="submit" name="ghostout" class="btn btn-lg btn-danger btn-block" style="margin-left: auto;margin-right: auto;min-width:12em;width:25%;">
  <span class="fa-stack fa-stack-button">
  <i class="fa fa-tablet fa-stack-2x fa-fw"></i>
  <i class="fa fa-times fa-stack-1x fa-fw"></i>
  </span>'.lang::$ghost.'</button>'.
  str_replace("<{here}>",'<a href="choose.php">'.lang::$here.'</a>',lang::$loginok).
  '<br>user ID:'.$user.'<br>'/* -> debug*/);
  if(auth::isadmin($user))
    echo str_replace("<{here}>",'<a href="admin/index.php">'.lang::$here.'</a>',lang::$adminok);
  //echo($_SERVER['HTTP_USER_AGENT']); //debug
  if(config::$clfact&&auth::getcdata($user,"clid")) {
    echo '<br>'.str_replace("<{id}>",auth::getcdata($user,"clid"),lang::$clefconnected);
    if(auth::verify("type")!="clef")
      echo '<br>'.str_replace("<{here}>",'<input type="submit" value="'.lang::$here.'" name="clfdis" class="btn btn-xs btn-warning">',lang::$clefdiscon);
    else
      echo '<br>'.lang::$clefnodis;
  }
  else {
    echo ('<span class="js"><br>'.str_replace("<{here}>",'<a class="clef-button btn btn-xs btn-info" data-app-id="'.config::$clfpub.'" data-redirect-url="'.config::$clfred.'" data-custom="true">'.lang::$here.'</a>',lang::$clefcon).'</span>
    <script type="text/javascript" src="https://clef.io/v3/clef.js"></script></span>');
  }
}
echo('</form>
      </div>
      </div>
    </body>
  </html>')
?>
