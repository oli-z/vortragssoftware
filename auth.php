<?php
require_once('config.php');
require_once('lang/'.config::$lang.'.php');

class auth {

private function getsecret() {   //Konstruktor, wird automatisch aufgerufen -> holt Session secret key aus datei
        include "./inc/secure/secret.php"; //super-secret server key -> $fsecret
		return $fsecret;
    }

//AES descrypt
function aescrypt($encrypt, $mc_key) {
    $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB),MCRYPT_RAND);
    $passcrypt = trim(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $mc_key,trim($encrypt),MCRYPT_MODE_ECB, $iv));
    $encode = base64_encode($passcrypt);
    return $encode;
}

public static function wpass($uid,$pw)
{
	global $sqldberror;
    // open ckey database
	if(ctype_digit($uid)){
    $connect=mysql_connect(config::$dbhost,config::$dbuser,config::$dbpass) or die ("cant connect to SQL (wp)");
    $setdb=mysql_query('use '.config::$dbname) or die ('<br><div class="container theme-showcase" role="main"><div class="alert alert-danger" role="alert"><span class="glyphicon glyphicon-exclamation-sign"></span>&emsp;'.$sqldberror.'(writepass)</div></div>');
    $usec=mysql_query('select usecret from users where uid = "'.$uid.'"') or die ("Datenbankproblem oder user existiert nicht, bitte zurück (writepass)");
	$usec=mysql_result($usec,0);
	$ssecret=self::getsecret();
	$pw=hash("sha512",$pw.substr($usec,0,128).substr($ssecret,128,128));
	$pw=hash("sha512",$pw.substr($ssecret,0,128).substr($usec,128,128));
	//echo $pw; //debug
	mysql_query ('update users set password="'.$pw.'" where uid='.$uid) or die (mysql_error());
	mysql_close();
	}
	else echo "uid net numerisch";
}

function aesdecrypt($decrypt, $mc_key) {   
    $decoded = base64_decode($decrypt);
    $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB),MCRYPT_RAND);
    $decrypted = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $mc_key, trim($decoded),MCRYPT_MODE_ECB, $iv));
    return $decrypted;
}

function cryptokey($uid,$cok=false,$debug=false) {
	mysql_connect(config::$dbhost,config::$dbuser,config::$dbpass) or die ('<div class="alert alert-danger" role="alert">cant connect to SQL (verify)</div>');
    $ssecret=self::getsecret();
    mysql_query('use '.config::$dbname) or die ('<div class="container theme-showcase" role="main"><div class="alert alert-danger" role="alert"><span class="glyphicon glyphicon-exclamation-sign"></span>&emsp;'.$sqldberror.'(verify)</div></div>');
	$usec=mysql_query('select usecret from users where uid='.$uid) or die("can't fetch user secret<br>");//get usersecret from DB
	$usec=mysql_result($usec,0);//get user secret from DB Result
	if(!$cok) //check old key=false -> check current key
	$t=time();
	else
	$t=time()-21600; //6h zurück, um mit altem key zu arbeiten (im falle der Übergangsphase)
	$day=floor($t/86400); //ermittle Tageswert
	$aeshash=hash("sha512",$ssecret.$usec.$day.$_SERVER['HTTP_USER_AGENT']);//berechne Basis-Hash aus Tag, Server Secret und User Secret
	$offset=floor(($t%86400)/21600)*32; //offset des Hash =1/4 Tag -> 6h, weil AES max keylength-> 32 char und hash = 128 char -> 4 mögliche keys
	$aeskey=substr($aeshash,($offset),32); //sortiere key aus Hash
	if($debug) echo ("ssec=".$ssecret."<br>usec=".$usec."<br>day=".$day."<br>browser=".$_SERVER['HTTP_USER_AGENT']."<br>off=".$offset."<br>hash=".$aeshash."<br>key=".$aeskey."<br>"); //debug
	return $aeskey; //gib key zurück
}

public static function isadmin($uid){
if(ctype_digit($uid));
}

function cdec($cookie){
if(substr_count($cookie,":")==1) { //cookie sections intact -> 2 sections seperated with :
$id=explode(":",$cookie)[0];
$cipher=explode(":",$cookie)[1];
if(ctype_digit($id) && $cipher==self::clean($cipher,1)) { //cookie data intact -> userID=number,cipher=base64string
$t=self::aesdecrypt($cipher,self::cryptokey($id)); //try decrypt current time section
if($t==self::clean($t))  //AES fail -> random junk -> wont survive clean function, AES success -> sessionID(A-Z,a-z,0-9) -> survives clean function
return $id.":".$t; //return user id and session key
else {
$k=self::cryptokey($id,1);
$t=self::aesdecrypt($cipher,$k); //check last time section
if($t==self::clean($t)) { //see above
return $id.":".$t; //return user id and session key -> continue verify
}
else
return false;
}
}
else
return false;
}
else
return false;
}

//remove special chars -> SQL Injection
public static function clean($z,$b64=false){
	if($b64)
		$z = preg_replace('/[^a-zA-Z0-9+\/=]+/', '', $z);
	else
		$z = preg_replace('/[^a-zA-Z0-9]+/', '', $z);
    $z = str_replace(' ', '', $z);
    return $z;
}

  public static function ip(){
    if (! isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      $ip = $_SERVER['REMOTE_ADDR'];
    }
    else {
      $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }

    return $ip;
  }
  
  public static function verify() {
    mysql_connect(config::$dbhost,config::$dbuser,config::$dbpass) or die ('<div class="alert alert-danger" role="alert">cant connect to SQL (verify)</div>');
    self::getsecret();
    mysql_query('use '.config::$dbname) or die ('<div class="container theme-showcase" role="main"><div class="alert alert-danger" role="alert"><span class="glyphicon glyphicon-exclamation-sign"></span>&emsp;'.$sqldberror.'(verify)</div></div>');
	//delete old cIDs
    $del=mysql_query('delete from session where void<'.time()) or die ("cant delete old cIDs (verify)");
	//check cookie
	if(isset($_COOKIE["key"])){
	// open ckey database
    global $sqldberror;
    //get IP
    $ip=self::ip();
    //clean key
    $key=self::cdec($_COOKIE["key"]); //decode Cookie -> get client uID+sID
	if($key) {
	$uid=explode(":",$key)[0]; // get client uID
	$key=explode(":",$key)[1]; // get client sID
    $hkey=hash("sha512",$key.hash("sha512",$ip)); //get DB sID by hashing of Client sID and IP
    //check key
    if(strlen($key)==256){ //key length
      $num = mysql_result (mysql_query('select count(cID) from session where cid like "'.$hkey.'"'),0); //count server sIDs
      if ($num>0){  //if an ID exists
		$login=mysql_result(mysql_query('select suid from session where cid like "'.$hkey.'"'),0); //get server uID
		if($login==$uid) { //if both IDs match
        $extend=time()+600; //create new session end
        $new=mysql_query('update session set void='.$extend.' where cid like "'.$hkey.'"') or die ("cant update session"); //update new session end in mysql
        setcookie('key',$uid.":".self::aescrypt($key,self::cryptokey($uid)),$extend); //create/replace AES-crypted cookie
		}
		else{
		$login=false;
		setcookie('key','',time()-3600);
		unset($_COOKIE["key"]);
		}
      }
      else
        $login=false;
		setcookie('key','',time()-3600);
		unset($_COOKIE["key"]);
    }
    else{
	setcookie('key','',time()-3600);
	unset($_COOKIE["key"]);
      $login=false;
      echo '<div class="container theme-showcase" role="main"><div class="alert alert-danger" role="alert"><span class="glyphicon glyphicon-exclamation-sign"></span>&emsp;Sitzungsschlüssel defekt.<br />Bitte neu einloggen</div></div>';
    }
	}
	else{
	  setcookie('key','',time()-3600);
	  unset($_COOKIE["key"]);
	  echo '<div class="container theme-showcase" role="main"><div class="alert alert-danger" role="alert"><span class="glyphicon glyphicon-exclamation-sign"></span>&emsp;Sitzungsschlüssel defekt.<br />Bitte neu einloggen</div></div>';
	  $login=false;
	  }
  }
	else
	  $login=false;
	mysql_close();
    return $login;
  }

  public static function createRandomKey($l=256){
    $keyset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $randkey = "";
    for ($i=0; $i<$l; $i++)
    $randkey .= substr($keyset, rand(0, strlen($keyset)-1), 1);
    return $randkey;
  }

  public static function logout() {
    self::getsecret();
	if(isset($_COOKIE["key"])){
	$key=self::cdec($_COOKIE["key"]); //decode Cookie -> get client uID+sID
	if($key) {
	$key=explode(":",$key)[1]; // get client sID
    mysql_connect(config::$dbhost,config::$dbuser,config::$dbpass) or die ("cant connect to SQL");
    mysql_query('use '.config::$dbname) or die ('<div class="container theme-showcase" role="main"><div class="alert alert-danger" role="alert"><span class="glyphicon glyphicon-exclamation-sign"></span>&emsp;'.$sqldberror.' (logout)</div></div>');
    $logondata=hash("sha512",$key.hash("sha512",self::ip()));
    mysql_query('delete from session where cid like "'.$logondata.'"') or die ('cant delete');
    unset($logondata);
    mysql_close();
	}
	}
	setcookie('key','',time()-3600);
	unset($_COOKIE["key"]);
  }

  public static function logon($user, $pw) {
    global $sqldberror;
    // open ckey database
    $connect=mysql_connect(config::$dbhost,config::$dbuser,config::$dbpass) or die ("cant connect to SQL");

    $setdb=mysql_query('use '.config::$dbname);
    if (!$setdb) {
      die ('<br><div class="container theme-showcase" role="main"><div class="alert alert-danger" role="alert"><span class="glyphicon glyphicon-exclamation-sign"></span>&emsp;'.$sqldberror.'(logon)</div></div>');
    }
    $user=self::clean($user);
    $loginfo=mysql_query('select password,uid,usecret from users where uname like "'.$user.'"') or die ("Datenbankproblem oder user existiert nicht, bitte zurück (logon)");
	$luid=mysql_result($loginfo,0,1);
    $loginfo=mysql_result($loginfo,0);
	$usec=mysql_result($loginfo,0,2);
    //echo(hash("sha512",hash("sha512",$pw))."<br />".$loginfo."<br />");
    mysql_close();
	$ssecret=self::getsecret();
	$pw=hash("sha512",$pw.substr($usec,0,128).substr($ssecret,128,128));
	$pw=hash("sha512",$pw.substr($ssecret,0,128).substr($usec,128,128));
    if ($pw==$loginfo)
    {
	  $ip=self::ip();
      $chash=self::createRandomKey();
      $duration=time()+600;
      mysql_connect(config::$dbhost,config::$dbuser,config::$dbpass) or die ('<div class="alert alert-danger" role="alert">cant connect to SQL</div>');
      mysql_query('use '.config::$dbname) or die ('<div class="container theme-showcase" role="main"><div class="alert alert-danger" role="alert"><span class="glyphicon glyphicon-exclamation-sign"></span>&emsp;'.$sqldberror.'</div></div>');
      $logon2=hash("sha512",$chash.hash("sha512",$ip));
      $insert=mysql_query('insert into session (cid,void,suid) values ("'.$logon2.'",'.$duration.','.$luid.')') or die ('<div class="alert alert-danger" role="alert">cant insert(logon)'.mysql_error().'</div>');
      unset($logon2);
      mysql_close();
	  $chash=self::aescrypt($chash,self::cryptokey($luid));
      setcookie('key',$luid.":".$chash,$duration);
      return $luid;
    }
    else{
      echo("logon fail");
      setcookie('key','',time()-3600);
	  unset($_COOKIE["key"]);
      return false;
    }
  }
}
?>
