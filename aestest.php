<form method="post" action="aestest.php" class="form-signin">
<input type="text" class="form-control" name="eingabedings" placeholder="">
<input type="submit" value="absenden" name="butooooon" class="btn btn-lg btn-success btn-block">
</form>
<?php
echo($_POST['eingabedings']);
include('auth.php');
$randkey=auth::createRandomKey();
echo($randkey)
auth::aescrypt($string, $randkey);
?>
