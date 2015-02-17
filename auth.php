<?php
require_once('config.php');  //config data, like language 
require_once('lang/'.config::$lang.'.php'); //language data for error codes
require_once('otp.php'); //OTP library comment this and wotp and the OTP part of verify() out for shutting off OTP

class auth {

  private function getsecret() {   //Konstruktor, wird automatisch aufgerufen -> holt Session secret key aus datei
    include "./inc/secure/secret.php"; //super-secret server key -> $fsecret
		return $fsecret;
  }

  //AES cryptoset
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
  
  //EasySQL Mini-API
  private static function dbc($link){ //connect
    mysql_connect(config::$dbhost,config::$dbuser,config::$dbpass) or die ("cant connect to SQL (".$link.")");
    mysql_select_db(config::$dbname) or die ('<br><div class="container theme-showcase" role="main"><div class="alert alert-danger" role="alert"><span class="glyphicon glyphicon-exclamation-sign"></span>&emsp;'."cannot connect".'('.$link.')</div></div>');
  }

  private static function dbq($link,$action, $col/*leave empty for delete, set param for update,cols für insert*/,$table,$filter=""/*filter -> values bei insert*/,$debug=false){
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
    if($debug) echo $q; // -> debug
    if($q)
      $q=mysql_query($q) or die ("cannot ".$action." db (".$link.")");
    return $q;
  }
  
  //Pass und OTP Erstellung für bestehenden user
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
  
    public static function wotp($uid,$otp){
    if(ctype_digit($uid)){
      $otp=clean($otp,32);//clean OTP key to base32
      self::dbc("wotp");
      $usec=self::dbq("wotp","select","usecret","users",'uid = "'.$uid.'"');
      $usec=mysql_result($usec,0);
      $ssecret=self::getsecret();
      $oaes=hash("sha512",substr($usec,128,128).substr($ssecret,128,128));
      $oaes=hash("sha512",$oaes.substr($ssecret,0,128).substr($usec,0,128));
      $oaes=substr($oaes,(32*$uid%4),32);
      $otp=self::aescrypt($otp,$oaes);
      //echo $pw; //debug
      self::dbq('wotp','update','otp="'.$otp.'"','users','uid='.$uid);
      mysql_close();
    }
    else echo "uid net numerisch";
  }
  
  public static function cuser($uname,$pw,$adm,$otp="") {
    $user=self::clean($uname);//clean user
    if($uname&&$pw) {//check if both are set (and in case of $user if something is left after cleaning)
      self::dbc("cuser"); //db connect
      if(mysql_num_rows(self::dbq("cuser","select","uid","users",'uname="'.$uname.'"'))===0) {// check for alredy existing username === because in case of failure false is given qhich would be equal 0.
        $ssecret=self::getsecret(); //get server secret
        $usec=self::createRandomKey(); //create fresh user secret
        $pw=hash("sha512",$pw.substr($usec,0,128).substr($ssecret,128,128));//create PW Hash step 1
        $pw=hash("sha512",$pw.substr($ssecret,0,128).substr($usec,128,128));//create PW Hash step 2
        if($adm==true)
          $adm=1;
        else
          $adm=0;
        self::dbq("cuser","insert","uname,password,usecret,admin","users",'"'.$uname.'","'.$pw.'","'.$usec.'",'.$adm);//create new user in db
        $uid=mysql_result(self::dbq("cuser","select","uid","users",'uname="'.$uname.'"'),0); //get User-ID
        mysql_close();
        if($otp){  //if we have an OTP seed
          if(strlen($otp>=16)) //check for length
            self::wotp($uid,$otp);
          else 
            echo "OTP nicht gültig, user wurde ohne OTP kreiert";
        }
      }
      else
        echo "fehler oder Username existiert bereits";
    }
    else
      echo "user oder passwort nicht eingegeben (bei Username werden sonderzeichen, sowie Leerzeichen ignoriert)";
  }
  
  //create AES key for Session Cookie
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
    if($debug) 
      echo ("ssec=".$ssecret."<br>usec=".$usec."<br>day=".$day."<br>browser=".$_SERVER['HTTP_USER_AGENT']."<br>off=".$offset."<br>hash=".$aeshash."<br>key=".$aeskey."<br>"); //debug
      return $aeskey; //gib key zurück
  }
  
  //decrypt Session Cookie
  function cdec($cookie){
    if(substr_count($cookie,":")==1) { //cookie sections intact -> 2 sections seperated with :
      $id=explode(":",$cookie)[0];
      $cipher=explode(":",$cookie)[1];
      if(ctype_digit($id) && $cipher==self::clean($cipher,64)) { //cookie data intact -> userID=number,cipher=base64string
        $t=self::aesdecrypt($cipher,self::cryptokey($id)); //try decrypt current time section
        if($t==self::clean($t))  //AES fail -> random junk -> wont survive clean function, AES success -> sessionID(A-Z,a-z,0-9) -> survives clean function
          return $id.":".$t; //return user id and session key
        else {
          $k=self::cryptokey($id,1);
          $t=self::aesdecrypt($cipher,$k); //check last time section
          if($t==self::clean($t)) { //see above
            return $id.":".$t; //return user id and decrypted session key -> continue verify
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

   //check for Admin
  public static function isadmin($uid){
    if(ctype_digit($uid)){
      self::dbc("isadmin");
      return mysql_result(self::dbq("isadmin","select","admin","users","uid=".$uid),0);
      mysql_close();
    }
  }

//remove special chars -> SQL Injection or Base32/64 safety-line
  public static function clean($z,$b=0){
    if($b==64)
      $z = preg_replace('/[^a-zA-Z0-9+\/=]+/', '', $z);
    else
      if($b==32)
        preg_replace('/[^ABCDEFGHIJKLMNOPQRSTUVWXYZ234567]+/', '', $z);
      else
        $z = preg_replace('/[^a-zA-Z0-9]+/', '', $z);
    $z = str_replace(' ', '', $z);
    return $z;
  }

  //get user IP
  public static function ip($noproxy=false){
    if (!(isset($_SERVER['HTTP_X_FORWARDED_FOR'])&&$noproxy)) {
      $ip = $_SERVER['REMOTE_ADDR'];
    }
    else {
      $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    return $ip;
  }

  //check for and verify Session
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

  //logon User
  public static function logon($user, $pw, $otp="") {
    // open ckey database
    self::dbc("logon");
    $user=self::clean($user);
    $loginfo=self::dbq("logon","select","password,uid,otp,usecret","users",'uname="'.$user.'"');
    $luid=mysql_result($loginfo,0,1);
    $usec=mysql_result($loginfo,0,3);
    $lotp=mysql_result($loginfo,0,2);
    $loginfo=mysql_result($loginfo,0);
    //echo $luid."<br>".$usec."<br>".$loginfo."<br>"; //debug -> show uID,uSecret,dbPass
    $ssecret=self::getsecret();
    //echo $pw;  //debug -> klartext pw
    $pw=hash("sha512",$pw.substr($usec,0,128).substr($ssecret,128,128));
    $pw=hash("sha512",$pw.substr($ssecret,0,128).substr($usec,128,128));
    //echo $pw;  //debug -> PW Hash
    if ($pw==$loginfo){ //check correct PW
      if($lotp){ //ckeck for db OTP  //for disabling OTP Cmment from this line to
        if($otp){
          if(self::clean($lotp,32)!=$lotp){ //OTP-seed not base32 -> AES-crypted
            if(self::clean($lotp,64)==$lotp){ //otp-seed base64 --> AES-crypted
              $oaes=hash("sha512",substr($usec,128,128).substr($ssecret,128,128));
              $oaes=hash("sha512",$oaes.substr($ssecret,0,128).substr($usec,0,128));
              $oaes=substr($oaes,(32*$luid%4),32);
              $otplain=self::aesdecrypt($lotp,$oaes);
              if($otplain==self::clean($otplain,32)&&strlen($otplain)>=16)
              {
                if(otp::verify_key($otplain, $otp,5))
                  return $luid;
              }
              else
                return false; //OTP AES Fail
            }            
            else
              return false; // OTP DB Problem
          }
          else{  //plaintext Seed
            if(strlen($lotp>=16)&&otp::verify_key($lotp, $otp,5))
              return $luid;
          }
        }
        else
          return false; //OTp nötig aber nicht angegeben.
      }  //OTP Check end  --> for disabling OTP comment until this line (including itself)
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
      echo("logon fail1");
      setcookie('key','',time()-3600);
      unset($_COOKIE["key"]);
      return false;
    }
    mysql_close();
  }
}
?>
