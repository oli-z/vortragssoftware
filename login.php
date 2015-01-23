<head>
  <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css"> <!-- Bootstrap CSS laden -->
</head>
<body>
  <?php

  require_once('auth.php');
  require_once('config.php');
  require_once('lang/'.$lang.'.php');

  $logincomplete=false;
  if ($_POST["pass"]&&$_POST["user"]&&$_POST["sub"]) {
    $result = auth::logon($_POST["user"],hash("sha512",$_POST["pass"]));

    if ($result==true) {
      $chash=auth::createRandomKey();
      $duration=time()+600;
      mysql_connect("localhost","root") or die ('<div class="alert alert-danger" role="alert">cant connect to SQL</div>');
      mysql_query('use schuletest') or die ('<div class="container theme-showcase" role="main"><div class="alert alert-danger" role="alert"><span class="glyphicon glyphicon-exclamation-sign"></span>&emsp;'.$sqldberror.'</div></div>');
      $insert=mysql_query('insert into session (cid,void) values ("'.$chash.'",'.$duration.')') or die ('<div class="alert alert-danger" role="alert">cant insert</div>');
      mysql_close();
      setcookie('key',$chash,$duration);
      $logincomplete=true;
    }
    else
    setcookie('key','lol',1);
  }
  if($_POST["kill"]) {
    mysql_connect("localhost","root") or die ("cant connect to SQL");
    mysql_query('use schuletest') or die ('<div class="container theme-showcase" role="main"><div class="alert alert-danger" role="alert"><span class="glyphicon glyphicon-exclamation-sign"></span>&emsp;'.$sqldberror.'</div></div>');
    mysql_query('delete from session where cid like "'.$_COOKIE["key"].'"') or die ('cant delete');
    mysql_close();
  }
?>


<div class="container">
<br>
<br>
<div class="jumbotron">

<form method="post" action="login.php" class="form-signin">
  <input type="text" class="form-control" name="user" placeholder="Banutzername">
  <br>
  <input type="password" class="form-control" name="pass" placeholder="passwort">
  <br>
  <table cellspacing="10" cellpadding="20">
      <tr>
        <td style="margin-right:5mm;"><input type="submit" value="absenden" name="sub" class="btn btn-lg btn-success btn-block"></td>
        <td>&emsp;	&emsp;	</td>
        <td><input type="submit" value="ausloggen" name="kill" class="btn btn-lg btn-warning btn-block"></td>
      </tr>
    </table>
</form>

<br>
<br>
<br>
<br>

</div>
</div>


<?php
if(auth::verify()||$logincomplete)
echo("<div>login OK hier gehts weiter</div>");
?>
</body>
