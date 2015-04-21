<?php
include ('../auth.php');
require_once('../lang/'.config::$lang.'.php');
$v=auth::verify();
echo $v;
$a=auth::isadmin($v);
$uname=auth::getcdata($v,'uname');
if($a)
  echo '﻿<head>
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
          <a class="navbar-brand" href="index.php"><strong>vortragssoftware.</strong>admin</a>
		  <a class="navbar-brand" href="users.php" style="color:white">nutzer</a>
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
	<div class="container theme-showcase" role="main">
		<div class="jumbotron">
			<h1>Benutzerverwaltung</h1>

					<h2>Benutzer hinzufügen:</h2>
						<table>
						<td><input type="text" class="form-control" name="user" placeholder="Benutzername"></td>
						<td><input type="password" class="form-control" name="pass" placeholder="Passwort"></td>
						<td><input type="password" class="form-control" name="pass" placeholder="Passwort wiederholen"></td>
						<td><input type="checkbox" aria-label="..."> Adminrechte &emsp;</td>
						<td><input type="submit" value="Nutzer erstellen" class="btn btn-xs btn-success btn-block"></input></td>
						</table>
						<br>
						<br>
					<h2>Benutzerliste:</h2>
							<table class="table">
								<thead>
									<tr>
										<th>User-ID</th>
										<th>Nutzername</th>
										<th>Admin (ja/nein)</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td>1</td>
										<td>MaxMustermann</td>
										<td>ja</td>
									</tr>
									<tr>
										<td>2</td>
										<td>MaxMustermann2</td>
										<td>nein</td>
									</tr>
								</tbody>
							</table>
		</div>

		<a href="..">Zurück zur Hauptseite</a>
	</div>
</body>';
else {
  session_start();
  //$_SESSION["msg"].=style::error("du bist kein Administrator"); //prep for v9, be excited!
  die ("du kommst hier nix rein!");
  header("Location: ../login.php");
}
?>
