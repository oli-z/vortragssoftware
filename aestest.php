<form method="post" action="aestest.php" class="form-signin">
<input type="text" class="form-control" name="eingabedings" placeholder="">
<input type="submit" value="absenden" name="butooooon" class="btn btn-lg btn-success btn-block">
</form>
<?php
if(isset($_POST['eingabedings'])){
echo($_POST['eingabedings']."<br />");
include('auth.php');
$randkey=auth::createRandomKey();
echo($randkey."<br />");
$aes=auth::aescrypt($_POST['eingabedings'], $randkey);
echo $aes."<br />";
$randkey2=auth::createRandomKey();
echo($randkey2."<br />");
$aes2=auth::aesdecrypt($aes, $randkey2);
echo $aes2."<br />";
$aes3=auth::aesdecrypt($aes, $randkey);
echo $aes3."<br />";
}
?>
