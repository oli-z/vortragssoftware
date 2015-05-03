<?php
require_once('config.php');
require_once('lang/'.config::$lang.'.php');
echo('
<html lang="de">
    <head>
      <meta http-equiv="content-type" content="text/html; charset=utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <link rel="stylesheet" href="inc/css/bootstrap.min.css"> <!-- Bootstrap CSS laden -->
      <link rel="stylesheet" href="inc/css/bootstrap-theme.min.css"> <!-- Bootstrap CSS laden -->
      <link rel="stylesheet" href="inc/css/font-awesome.min.css"> <!-- FontAwesome CSS laden -->
      <link rel="stylesheet" href="inc/css/fa.css"> <!-- FA-Wise CSS laden -->
      <title>'.lang::$submitbug.' - '.config::$title.'</title>
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
      <div class="jumbotron"><h1 class="text-center">'.config::$title.'</h1><h2 class="text-center">'.lang::$submitbug.'</h2>
	  <form method="post" action="bug.php">
<input type="text" name="bugmail">
<input type="text" name="bugtext">
<input type="submit">
</form>');
$bugmail = $_POST[bugmail];
$bugtext= $_POST[bugtext];
mysql_connect(config::$dbhost,config::$dbuser,config::$dbpass) or die;
    mysql_select_db(config::$dbname) or die;
$insert = mysql_query("INSERT INTO bugs (mail, bugtext) VALUES ('$bugmail', '$bugtext')") or die(mysql_error());
echo('</form>
      </div>
      </div>
    </body>
  </html>')
?>
