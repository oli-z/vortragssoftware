<form method="post" action="wpass.php" class="form-signin">
<input type="text" class="form-control" name="uid" placeholder="uid">
<input type="password" class="form-control" name="pass" placeholder="pass">
<input type="submit" value="absenden" name="butooooon" class="btn btn-lg btn-success btn-block">
</form>
<?php
if(isset($_POST['uid'])&&isset($_POST["pass"])){
include 'auth.php';
auth::wpass($_POST['uid'],$_POST['pass']);
}
?>
