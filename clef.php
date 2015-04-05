<?php
include ("auth.php");
if (isset($_GET["type"])&&$_GET["type"]=="clef"&&isset($_GET["code"])) {
  auth::logon(0,0,0,0,$_GET["code"]);
  header("Location: login.php");
}

if (isset($_GET["type"])&&$_GET["type"]=="clefout"&&isset($_POST['logout_token'])) {
  auth::clefout($_POST['logout_token']);
}
?>
