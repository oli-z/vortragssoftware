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
  
  function aesdecrypt($decrypt, $mc_key) {
    $decoded = base64_decode($decrypt);
    $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB),MCRYPT_RAND);
    $decrypted = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $mc_key, trim($decoded),MCRYPT_MODE_ECB, $iv));
    return $decrypted;
  }

  private static function dbc($link){ //connect
    mysql_connect(config::$dbhost,config::$dbuser,config::$dbpass) or die ("cant connect to SQL (".$link.")");
    mysql_select_db(config::$dbname) or die ('<br><div class="container theme-showcase" role="main"><div class="alert alert-danger" role="alert"><span class="glyphicon glyphicon-exclamation-sign"></span>&emsp;'."cannot connect".'('.$link.')</div></div>');
  }

  private static function dbq($link,$action, $col/*leave empty for delete, set param for update,cols für insert*/,$table,$filter=""/*filter -> values bei insert*/){
    $q="";
    if($action=="select"){
      if($filter)
        $q="select ".$col." from ".$table." where ".$filter;
      else
        $q="select ".$col." from ".$table;
    }
    if($action=="update"&&$filter)
      $q="update ".$table." set ".$col." where ".$filter;
    if($action=="delete"&&$filter)
      $q="delete from ".$table." where ".$filter;
    if($action=="insert")
      $q='insert into '.$table.' '.'('.$col.') values ('.$filter.')';
    // echo $q; // -> debug
    if($q)
      $q=mysql_query($q) or die ("cannot ".$action." db (".$link.")");
    return $q;
  }



  public static function wpass($uid,$pw){
    if(ctype_digit($uid)){
      self::dbc("wp");
      $usec=self::dbq("wp","select","usecret","users",'uid = "'.$uid.'"');
      $usec=mysql_result($usec,0);
      $ssecret=self::getsecret();
      $pw=hash("sha512",$pw.substr($usec,0,128).substr($ssecret,128,128));
      $pw=hash("sha512",$pw.substr($ssecret,0,128).substr($usec,128,128));
      //echo $pw; //debug
      self::dbq('wp','update','password="'.$pw.'"','users','uid='.$uid);
      mysql_close();
    }
    else echo "uid net numerisch";
  }



  function cryptokey($uid,$cok=false,$debug=false) {
    self::dbc("cryptokey");
    $ssecret=self::getsecret();//get Server Secret from file
     $usec=self::dbq("cryptokey","select","usecret","users",'uid = "'.$uid.'"');//get usersecret from DB
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
    else
    return false;
  }

  public static function isadmin($uid){
    if(ctype_digit($uid)){
      self::dbc("isadmin");
      return mysql_result(self::dbq("isadmin","select","admin","users","uid=".$uid),0);
      mysql_close();
    }
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

  public static function ip($noproxy=false){
    if (!(isset($_SERVER['HTTP_X_FORWARDED_FOR'])&&$noproxy)) {
      $ip = $_SERVER['REMOTE_ADDR'];
    }
    else {
      $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }

    return $ip;
  }

  public static function verify() {
    self::dbc("verify");
    //delete old cIDs
    self::dbq("verify","delete","","session",'void<'.time());
    //check cookie
    if(isset($_COOKIE["key"])){ //if cookie exists
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
          $num=mysql_result(self::dbq("verify","select","count(sID)","session",'cid="'.$hkey.'"'),0);//count server sIDs for session Key
          if ($num>0){  //if an ID exists
            $login=mysql_result(self::dbq("verify","select","suid","session",'cid="'.$hkey.'"'),0);//get server session-uID
            if($login==$uid) { //if both IDs match
              $extend=time()+600; //create new session end
              self::dbq('verify','update','void='.$extend,'session','cid="'.$hkey.'"');//update new session end in mysql
              setcookie('key',$uid.":".self::aescrypt($key,self::cryptokey($uid)),$extend); //create/replace AES-crypted cookie
            }
            else{
              $login=false;
              setcookie('key','',time()-3600);
              unset($_COOKIE["key"]);
              //echo "fail1"; //debug
            }
          }
          else{
            $login=false;
            setcookie('key','',time()-3600);
            unset($_COOKIE["key"]);
            //echo "fail2"; //debug
          }
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
        self::dbc("logout");
        $logondata=hash("sha512",$key.hash("sha512",self::ip()));
        self::dbq('logout','delete','','session','cid="'.$logondata.'"');
        unset($logondata);
        mysql_close();
      }
    }
    setcookie('key','',time()-3600);
    unset($_COOKIE["key"]);
  }

  public static function logon($user, $pw) {
    // open ckey database
    self::dbc("logon");
    $user=self::clean($user);
    $loginfo=self::dbq("logon","select","password,uid,usecret","users",'uname="'.$user.'"');
    $luid=mysql_result($loginfo,0,1);
    $usec=mysql_result($loginfo,0,2);
    $loginfo=mysql_result($loginfo,0);
    //echo $luid."<br>".$usec."<br>".$loginfo."<br>"; //debug -> show uID,uSecret,dbPass
    $ssecret=self::getsecret();
    //echo $pw;  //debug -> klartext pw
    $pw=hash("sha512",$pw.substr($usec,0,128).substr($ssecret,128,128));
    $pw=hash("sha512",$pw.substr($ssecret,0,128).substr($usec,128,128));
    //echo $pw;  //debug -> PW Hash
    if ($pw==$loginfo){
      $ip=self::ip();
      $chash=self::createRandomKey();
      $duration=time()+600;
      $logon2=hash("sha512",$chash.hash("sha512",$ip));
      self::dbq("logon","insert","cid,void,suid","session",'"'.$logon2.'",'.$duration.','.$luid);//create new session in db
      unset($logon2);
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
    mysql_close();
  }
}
?>
