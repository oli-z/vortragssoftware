<?php


require_once('config.php');
require_once('lang/'.config::$lang.'.php');
global $sqldberror;

class auth {

private static $ssecret;

function getsecret() {   //Konstruktor, wird automatisch aufgerufen -> holt Session secret key aus datei
        include "./inc/secure/secret.php"; //super-secret server key -> $fsecret
        self::$ssecret = $fsecret;
    }

//AES descrypt
function aescrypt($encrypt, $mc_key) {
    $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB),MCRYPT_RAND);
    $passcrypt = trim(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $mc_key,trim($encrypt),MCRYPT_MODE_ECB, $iv));
    $encode = base64_encode($passcrypt);
    return $encode;
}

function aesdecrypt($decrypt, $mc_key) {   
    $decoded = base64_decode($decrypt);
    $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB),MCRYPT_RAND);
    $decrypted = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $mc_key, trim($decoded),MCRYPT_MODE_ECB, $iv));
    return $decrypted;
}
  
//remove special chars -> SQL Injection
public static function clean($z){
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
    //check cookie
	if(isset($_COOKIE["key"])){
	// open ckey database
    global $sqldberror;
    mysql_connect(config::$dbhost,config::$dbuser,config::$dbpass) or die ('<div class="alert alert-danger" role="alert">cant connect to SQL (verify)</div>');
    self::getsecret();
    mysql_query('use '.config::$dbname) or die ('<div class="container theme-showcase" role="main"><div class="alert alert-danger" role="alert"><span class="glyphicon glyphicon-exclamation-sign"></span>&emsp;'.$sqldberror.'(verify)</div></div>');
    //get IP
    $ip=auth::ip();
    //delete old cIDs
    $del=mysql_query('delete from session where void<'.time()) or die ("cant delete old cIDs (verify)");
    //clean key
    $key=auth::clean($_COOKIE["key"]);
    $key=hash("sha512",$_COOKIE["key"].hash("sha512",$ip));
    //check key
    if(strlen($_COOKIE["key"])==256){
      $check=mysql_query('select count(cID) from session where cid like "'.$key.'"');
      $num = mysql_result ($check,0);
      if ($num>0){
		$login=mysql_result(mysql_query('select suid from session where cid like "'.$key.'"'),0);
        $extend=time()+600;
        $new=mysql_query('update session set void='.$extend.' where cid like "'.$key.'"') or die ("cant update session");
        setcookie('key',$_COOKIE["key"],$extend);
      }
      else
        $login=false;
    }
    else{
      $login=false;
      echo '<div class="container theme-showcase" role="main"><div class="alert alert-danger" role="alert"><span class="glyphicon glyphicon-exclamation-sign"></span>&emsp;Sitzungsschlüssel defekt.<br />Bitte neu einloggen</div></div>';
    }}
	else
	  return false;
    mysql_close();
    return $login;
  }

  public static function createRandomKey(){
    $keyset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $randkey = "";
    for ($i=0; $i<256; $i++)
    $randkey .= substr($keyset, rand(0, strlen($keyset)-1), 1);
    return $randkey;
  }

  public static function logout() {
    self::getsecret();
	if(isset($_COOKIE["key"])){
    mysql_connect(config::$dbhost,config::$dbuser,config::$dbpass) or die ("cant connect to SQL");
    mysql_query('use '.config::$dbname) or die ('<div class="container theme-showcase" role="main"><div class="alert alert-danger" role="alert"><span class="glyphicon glyphicon-exclamation-sign"></span>&emsp;'.$sqldberror.' (logout)</div></div>');
    $logondata=hash("sha512",$_COOKIE["key"].hash("sha512",self::ip()));
    mysql_query('delete from session where cid like "'.$logondata.'"') or die ('cant delete');
    unset($logondata);
    mysql_close();
	}
	setcookie('key','lol',1);
  }

  public static function logon($user, $pw) {
    self::getsecret();
    //echo self::$ssecret; //debug
    global $sqldberror;
    // open ckey database
    $connect=mysql_connect(config::$dbhost,config::$dbuser,config::$dbpass) or die ("cant connect to SQL");

    $setdb=mysql_query('use '.config::$dbname);
    if (!$setdb) {
      die ('<br><div class="container theme-showcase" role="main"><div class="alert alert-danger" role="alert"><span class="glyphicon glyphicon-exclamation-sign"></span>&emsp;'.$sqldberror.'(logon)</div></div>');
    }
    $user=auth::clean($user);
    $loginfo=mysql_query('select password,uid from users where uname like "'.$user.'"') or die ("Datenbankproblem oder user existiert nicht, bitte zurück (logon)");
	$luid=mysql_result($loginfo,0,1);
    $loginfo=mysql_result($loginfo,0);
    //echo(hash("sha512",hash("sha512",$pw))."<br />".$loginfo."<br />");
    mysql_close();
    if(hash("sha512",hash("sha512",$pw))==$loginfo)
    {
	  $ip=self::ip();
      $chash=auth::createRandomKey();
      $duration=time()+600;
      mysql_connect(config::$dbhost,config::$dbuser,config::$dbpass) or die ('<div class="alert alert-danger" role="alert">cant connect to SQL</div>');
      mysql_query('use '.config::$dbname) or die ('<div class="container theme-showcase" role="main"><div class="alert alert-danger" role="alert"><span class="glyphicon glyphicon-exclamation-sign"></span>&emsp;'.$sqldberror.'</div></div>');
      $logon2=hash("sha512",$chash.hash("sha512",$ip));
      $insert=mysql_query('insert into session (cid,void,suid) values ("'.$logon2.'",'.$duration.','.$luid.')') or die ('<div class="alert alert-danger" role="alert">cant insert(logon)'.mysql_error().'</div>');
      unset($logon2);
      mysql_close();
      setcookie('key',$chash,$duration);
      return $luid;
    }
    else{
      echo("logon fail");
      setcookie('key','lol',1);
      return false;
    }
  }
}
?>
