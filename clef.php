<?php
include ("auth.php");
if (isset($_GET["type"])&&$_GET["type"]=="clef"&&isset($_GET["code"])) {
  if(!auth::logon(0,0,0,0,$_GET["code"])) {
    session_start();
    $_SESSION["msg"].=auth::$msg;
  }
  header("Location: login.php");
}

if (isset($_GET["type"])&&$_GET["type"]=="clefout"&&isset($_POST['logout_token'])) {
  auth::logout("clef",$_POST['logout_token']);
}
?>
