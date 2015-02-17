<form method="post" action="" class="form-signin">
<input type="text" class="form-control" name="uname" placeholder="username">
<input type="password" class="form-control" name="pass" placeholder="pass">
<input type="text" class="form-control" name="otp" placeholder="OTP">
<label for="check1"> <input class="form-control" type="checkbox" name="admin" id="check1"> Admin </label>
<input type="submit" value="absenden" name="butooooon" class="btn btn-lg btn-success btn-block">
</form>
<?php
if(isset($_POST['uname'])&&isset($_POST["pass"])){
include 'auth.php';
auth::cuser($_POST['uname'],$_POST['pass'],isset($_POST["admin"]),$_POST["otp"]);
}
?>
