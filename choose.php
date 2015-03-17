<?php
require_once('auth.php');
require_once('config.php');
require_once('lang/'.config::$lang.'.php');
if(isset($_POST["kill"])) {
  auth::logout();
}
$user=auth::verify();
if($user){
  echo('');

echo('
<html lang="de">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <link rel="stylesheet" href="inc/css/bootstrap.css"> <!-- Bootstrap CSS laden -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script> <!-- reCaptcha -->
  </head>
  <body class="colorbkg">
    <div class="container">
      <br>
      <br>
      <div class="jumbotron">
        <h1 style="text-align: center">'.lang::$choose.'</h1>
        <h3 style="text-align: center">'.lang::$choosetext.'<h3>
          hier kommt nach login das vortragszeug
          <br>
          <br>
          <form method="post" action="choose.php"><input type="submit" value="'.lang::$logout.'" name="kill" class="btn btn-lg btn-warning btn-block" style="margin-left: auto;margin-right: auto;width:25%;"></form>
          '.$user.'
          <br>
      </div>
    </div>
  </body>
</html>
');}else
{echo('<h1>'.lang::$notloggedin.'<h1>');header("Location: login.php");}
?>
