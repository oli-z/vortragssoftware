<?php
include ('../auth.php');
require_once('../lang/'.config::$lang.'.php');
$v=auth::verify();
echo $v;
$a=auth::isadmin($v);
$uname=auth::getcdata($v,'uname');
if($a)
  echo '<html>
<head>
	<meta charset="utf-8">
	<link href="../inc/css/bootstrap.css" rel="stylesheet">
</head>
<body>
	<nav class="navbar navbar-inverse navbar-fixed-top">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="index.php" style="color:white"><strong>vortragssoftware.</strong>admin</a>
		  <a class="navbar-brand" href="users.php">nutzer</a>
		  <a class="navbar-brand" href="#">vorträge</a>
		  <a class="navbar-brand" href="#">konfiguration</a>
		  <a class="navbar-brand" href="#">updates</a>
        </div>
        <div id="navbar" class="navbar-collapse collapse">
          <form class="navbar-form navbar-right">
            <div class="form-group" style="color:grey">
              Eingeloggt als <strong>'.$uname.'</strong>.
            </div>&ensp;
            <button type="submit" class="btn btn-success">ausloggen</button>
          </form>
        </div>
      </div>
    </nav>
<br>
<br>
<br>
<br>
<br>
<br>
	<div class="container theme-showcase" role="main">
		<div class="jumbotron">
			<h1>Willkommen bei der Administration.</h1>
		</div>

		<a href="..">Zurück zur Hauptseite</a>
	</div>
</body>
</html>';
else {
  session_start();
  //$_SESSION["msg"].=style::error("du bist kein Administrator"); //prep for v9, be excited!
  die ("du kommst hier nix rein!");
  header("Location: ../login.php");
}
?>
