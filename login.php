<!--
       .--.           _____                    _____                    _____            _____           _______
      |o_o |         /\    \                  /\    \                  /\    \          /\    \         /::\    \
      |:_/ |        /::\____\                /::\    \                /::\____\        /::\____\       /::::\    \
     //   \ \      /:::/    /               /::::\    \              /:::/    /       /:::/    /      /::::::\    \
    (|     | )    /:::/    /               /::::::\    \            /:::/    /       /:::/    /      /::::::::\    \
   /'\_   _/`\   /:::/    /               /:::/\:::\    \          /:::/    /       /:::/    /      /:::/~~\:::\    \
   \___)=(___/  /:::/____/               /:::/__\:::\    \        /:::/    /       /:::/    /      /:::/    \:::\    \
               /::::\    \              /::::\   \:::\    \      /:::/    /       /:::/    /      /:::/    / \:::\    \
              /::::::\    \   _____    /::::::\   \:::\    \    /:::/    /       /:::/    /      /:::/____/   \:::\____\
             /:::/\:::\    \ /\    \  /:::/\:::\   \:::\    \  /:::/    /       /:::/    /      |:::|    |     |:::|    |
            /:::/  \:::\    /::\____\/:::/  \:::\   \:::\____\/:::/____/       /:::/____/       |:::|____|     |:::|    |
            \::/    \:::\  /:::/    /\::/    \:::\  /:::/    /\:::\    \       \:::\    \        \:::\    \   /:::/    /
             \/____/ \:::\/:::/    /  \/____/ \:::\/:::/    /  \:::\    \       \:::\    \        \:::\    \ /:::/    /
                      \::::::/    /            \::::::/    /    \:::\    \       \:::\    \        \:::\    /:::/    /
                       \::::/    /              \::::/    /      \:::\    \       \:::\    \        \:::\__/:::/    /
                       /:::/    /               /:::/    /        \:::\    \       \:::\    \        \::::::::/    /
                      /:::/    /               /:::/    /          \:::\    \       \:::\    \        \::::::/    /
                     /:::/    /               /:::/    /            \:::\    \       \:::\    \        \::::/    /
                    /:::/    /               /:::/    /              \:::\____\       \:::\____\        \::/____/
                    \::/    /                \::/    /                \::/    /        \::/    /         ~~
                     \/____/                  \/____/                  \/____/          \/____/
-->
<head>
  <link rel="stylesheet" href="/inc/css/bootstrap.min.css"> <!-- Bootstrap CSS laden -->
</head>
<body>
  <?php

  require_once('auth.php');
  require_once('config.php');
  require_once('lang/'.config::$lang.'.php');

  $logincomplete=false;
  if ($_POST["pass"]&&$_POST["user"]&&$_POST["sub"]) {
  //die("<br /><br />".$_POST["user"]."<br />".hash("sha512",hash("sha512",$_POST["pass"])));
    $result = auth::logon($_POST["user"],$_POST["pass"]);

    if ($result==true) {
      $logincomplete=true;
    }
  }
  if($_POST["kill"]) {
    auth::logout();
  }
?>


<div class="container">
<br>
<br>
<div class="jumbotron">

<form method="post" action="login.php" class="form-signin">
<?php
if(!(auth::verify()||$logincomplete))
echo('
	<input type="text" class="form-control" name="user" placeholder="Banutzername">
  	<br>
  	<input type="password" class="form-control" name="pass" placeholder="passwort">
  	<br>
  	<input type="submit" value="absenden" name="sub" class="btn btn-lg btn-success btn-block">
    ');
else
echo('
        <input type="submit" value="ausloggen" name="kill" class="btn btn-xs btn-warning btn-block">
	</form>

	<br>
	<br>
	<br>
	<br>

	</div>
	</div>
')
?>
<?php
if(auth::verify()||$logincomplete)
echo("<div>login OK hier gehts weiter</div>");
?>
</body>
