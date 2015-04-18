<?php
  require_once('auth.php');
  require_once('config.php');
  require_once('lang/'.config::$lang.'.php');
  require_once('easysql.php');
  if(isset($_POST["kill"])&&$_POST["kill"]) {
    auth::logout();
  }
  $user=auth::verify();
  if(isset($_POST["sendchoice"])) {
    if($user) {
      $array=explode(";",$_POST["idlist"]);
      var_dump($array);
      esql::dbc("choice");
      esql::dbq("choice","delete","","vcon","couid=".$user);
      $unset=0;
      foreach ($array as &$val) {
        if(isset($_POST[$val])) {
          echo $_POST[$val];
          esql::dbq("choice","insert","couid,covid","vcon",$user.",".$_POST[$val]);
        }
        else {
          $unset++;
        }
      }
      if($unset>0) {
        session_start();
        $_SESSION["msg"].=style::warn("du hast ".$unset." Eintrag/Einträge nicht ausgefüllt.");
      }
      unset($val);
      header("Location: choose.php");
      die();
    }
  }
  session_start();
if(isset($_SESSION["msg"]))
  auth::$msg.=$_SESSION["msg"];
session_destroy();
  if($user){
    echo('');
    echo('
    <html lang="de">
    <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <link rel="stylesheet" href="inc/css/bootstrap.min.css"> <!-- Bootstrap CSS laden -->
    <link rel="stylesheet" href="inc/css/bootstrap-theme.min.css"> <!-- Bootstrap CSS laden -->
    <link rel="stylesheet" href="inc/css/fa.css"> <!-- FontAwesome CSS laden -->
    </head>
    <body class="colorbkg">
    <div class="container">
    <br>
    <div class="jumbotron">'.auth::$msg.'
    <h1 style="text-align: center">'.lang::$choose.'</h1>
    <h3 style="text-align: center">'.lang::$choosetext.'<h3>
    <form method="post" action="">');
    esql::dbc("choose");
    $v=esql::dbq("chosse","select","slid,vid,vname,sltime,couid,`limit`","vortrag join slots on (slid=vslid) left outer join vcon on (covid=vid AND couid=".$user.")","1 order by slid,vid");
    if($v) {
      echo '<table class="table table-bordered">';
      echo '<th>'.esql::dbres($v,0,3).'</th><th>Name</th><th>Limit</th><th>bereits belegt</th>';
      $s=esql::dbres($v,0,0);
      $idlist=(string)(esql::dbres($v,0,0));
      while ($r=mysql_fetch_row($v)) {
        if($s!=$r[0]) {
          echo '</table><br><table class="table table-bordered">';
          echo '<th>'.$r[3].'</th><th>Name</th><th>Limit</th><th>bereits belegt</th>';
          $idlist.=";".$r[0];
          $s=$r[0];
        }
        if($r[4]===NULL) {
          $checked="";
        }
        else {
          $checked='checked="checked" ';
        }
        if($r[5]==0) {
          $r[5]="unbegrenzt";
        }
        $taken=esql::dbres(esql::dbq("choose","select","count(*)","vcon","couid!=".$user." AND covid=".$r[1]),0,0);
        if($taken>=$r[5])
          $checked='disabled="disabled"';
        echo '<tr><td><input type=radio '.$checked.'name="'.$r[0].'" value="'.$r[1].'"></td><td>'.$r[2].'</td><td>'.$r[5].'</td><td>'.$taken.'</td></tr>';
      }
      echo "</table>";
      }
      echo ('<br>
      <input type="hidden" name="idlist" value="'.$idlist.'">
      <br>
      <input type="submit" value="'."absenden".'" name="sendchoice" class="btn btn-lg btn-primary btn-block" style="margin-left: auto;margin-right: auto;width:25%;">
      <input type="submit" value="'.lang::$logout.'" name="kill" class="btn btn-lg btn-warning btn-block" style="margin-left: auto;margin-right: auto;width:25%;"></form>
      </div>
      </div>
      </body>
      </html>');
    }
    else
    {
      session_start();
      $_SESSION["msg"]=style::warn("Du bist nicht eingeloggt");
      header("Location: login.php");
    }
  ?>    
