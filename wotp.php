<form method="post" action="wotp.php" class="form-signin">
<input type="text" class="form-control" name="uid" placeholder="uid">
<input type="text" class="form-control" name="otp" placeholder="otp" autocomplete="off">
<input type="submit" value="absenden" name="butooooon" class="btn btn-lg btn-success btn-block">
</form>
<?php
if(isset($_POST['uid'])&&isset($_POST["otp"])){
include 'auth.php';
auth::wotp($_POST['uid'],$_POST['otp']);
}
?>
