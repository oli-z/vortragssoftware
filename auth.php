<?php
require_once('config.php');  //config data, like language
require_once('lang/'.config::$lang.'.php'); //language data for error codes
require_once('otp.php'); //OTP library comment this and wotp and the OTP part of verify() out for shutting off OTP

class auth {

  //remove special chars -> SQL Injection or Base32/64 character check
  public static function clean($z,$b=0){
    if($b==64){
      $z = preg_replace('/[^a-zA-Z0-9+\/=]+/', '', $z);
    }
    else {
      if($b==32) {
        $z = preg_replace('/[^ABCDEFGHIJKLMNOPQRSTUVWXYZ234567]+/', '', $z);
      }
      else {
        $z = preg_replace('/[^a-zA-Z0-9]+/', '', $z);
      }
    }
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

  //create random key for different purposes
  public static function createRandomKey($l=256){
    $keyset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $randkey = "";
    for ($i=0; $i<$l; $i++)
    $randkey .= substr($keyset, rand(0, strlen($keyset)-1), 1);
    return $randkey;
  }

  //tools for clef

  //clef logon
  private function clef($code){
    //---------------------------------------- getting clef Access token by using the OAuth Code in $code
    if ($code) {
      $postdata = http_build_query(
          array(
              'code' => $code,
              'app_id' => config::$clfpub,
              'app_secret' => config::$clfkey
          )
      );

      $opts = array('http' =>
          array(
              'method'  => 'POST',
              'header'  => 'Content-type: application/x-www-form-urlencoded',
              'content' => $postdata
          )
      );

      $url = 'https://clef.io/api/v1/authorize';

      $context  = stream_context_create($opts);
      $response = file_get_contents($url, false, $context);
      $response = json_decode($response, true);

      if ($response && $response['success']) {
          $access_token = $response['access_token'];
      } else {
          echo $response['error'];
      }
      //------------------------------------ exchange clef access token for user data
      $opts = array('http' =>
                  array(
                      'method'  => 'GET'
                  )
              );

      $base_url = 'https://clef.io/api/v1/info';
      $query_string = '?access_token='.$access_token;
      $url = $base_url.$query_string;

      $context  = stream_context_create($opts);
      $response = file_get_contents($url, false, $context);
      $response = json_decode($response, true);

      if ($response && $response['success']) {
          $user_info = $response['info'];
          $clef_id = $user_info['id'];
          return $clef_id;
      }
      else {
          return false;
      }
    }
    else {
      return false;
    }
  }

  //clef logout
  public function clefout($token) {
    if (isset($token)) {

      $url = "https://clef.io/api/v1/logout";

      $postdata = http_build_query(
          array(
              'logout_token' => $token,
              'app_id' => config::$clfpub,
              'app_secret' => config::$clfkey
          )
      );

      $opts = array('http' =>
          array(
              'method'  => 'POST',
              'header'  => 'Content-type: application/x-www-form-urlencoded',
              'content' => $postdata
          )
      );

      $context  = stream_context_create($opts);
      $response = file_get_contents($url, false, $context);
      $response = json_decode($response, true);

      if($response && $response['success']) {
          $clef_id = $response['clef_id'];
          self::logout("clef",$clef_id);
      }
      else
        return false;
    }
    else
      return false;
  }

  //holt Session secret key aus datei
  private function getsecret() {
    include "inc/secure/secret.php"; //super-secret server key -> $fsecret
		return $fsecret;
  }

  //captcha check
  private function ccheck($captcha) {
    if(isset(config::$cappub)&&config::$cappub&&isset(config::$capkey)&&config::$capkey) {
      if($captcha) {
        $response=file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=".config::$capkey."&response=".$captcha."&remoteip=".$_SERVER['REMOTE_ADDR']);
        if(!$response)
          return true;
        $response = json_decode($response, true);
        return $response['success'];
      }
      else
        return false;
    }
    else
      return true;
  }

  //AES cryptoset
  function aescrypt($encrypt, $mc_key) {
    $passcrypt = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $mc_key,trim($encrypt),MCRYPT_MODE_ECB);
    $encode = base64_encode($passcrypt);
    return $encode;
  }

  function aesdecrypt($decrypt, $mc_key) {
    $decoded = base64_decode($decrypt);
    $decrypted = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $mc_key,$decoded,MCRYPT_MODE_ECB));
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
      $q=mysql_query($q) or die ("cannot ".$action." in db (".$link.")");
    return $q;
  }

  private static function dbres($res,$row=0,$col=0) {
    return mysql_result($res,$row,$col);
  }

  private static function dbclose($link="") {
    mysql_close();
  }

  //Usertools

  //create user
  public static function cuser($uname,$pw,$adm=0,$otp="") {
    $user=self::clean($uname);//clean user
    if($uname&&$pw) {//check if both are set (and in case of $user if something is left after cleaning)
    self::dbc("cuser"); //db connect
    $cnt=self::dbres(self::dbq("cuser","select","count(uid)","users",'uname="'.$uname.'"'));
      if($cnt===0||$cnt==="0") {
        // check for alredy existing username === because in case of failure false is given qhich would be equal 0.
        $ssecret=self::getsecret(); //get server secret
        $usec=self::createRandomKey(); //create fresh user secret
        $pw=hash("sha512",$pw.substr($usec,0,128).substr($ssecret,128,128));//create PW Hash step 1
        $pw=hash("sha512",$pw.substr($ssecret,0,128).substr($usec,128,128));//create PW Hash step 2
        if($adm==true)
          $adm=1;
        else
          $adm=0;
        self::dbq("cuser","insert","uname,password,usecret,admin","users",'"'.$uname.'","'.$pw.'","'.$usec.'",'.$adm);//create new user in db
        $uid=self::dbres(self::dbq("cuser","select","uid","users",'uname="'.$uname.'"'),0); //get User-ID
        self::dbclose();
        if($otp){  //if we have an OTP seed
          if(strlen($otp>=16)) //check for length
            self::wotp($uid,$otp);
          else
            echo "OTP nicht gültig, user wurde ohne OTP kreiert";
        }
        return true;
      }
      else {
        if($cnt>0)
          echo "Username existiert bereits";
        if($cnt===false)
          echo "fehler";
        return false;
      }
    }
    else {
      echo "user oder passwort nicht eingegeben (bei Username werden sonderzeichen, sowie Leerzeichen ignoriert)";
      return false;
    }
  }

  //write pass
  public static function wpass($uid,$pw){
    if((is_int($uid) || ctype_digit($uid)) && (int)$uid >= 0){
      self::dbc("wp");
      $usec=self::dbq("wp","select","usecret","users",'uid = "'.$uid.'"');
      $usec=self::dbres($usec,0);
      $ssecret=self::getsecret();
      $pw=hash("sha512",$pw.substr($usec,0,128).substr($ssecret,128,128));
      $pw=hash("sha512",$pw.substr($ssecret,0,128).substr($usec,128,128));
      //echo $pw; //debug
      self::dbq('wp','update','password="'.$pw.'"','users','uid='.$uid);
      self::dbclose();
    }
    else echo "uid net numerisch";
  }

  //write otp
  public static function wotp($uid,$otp){
    if((is_int($uid) || ctype_digit($uid)) && (int)$uid >= 0){
      $otp=self::clean($otp,32);//clean OTP key to base32
      self::dbc("wotp");
      $usec=self::dbq("wotp","select","usecret","users",'uid = "'.$uid.'"');
      $usec=self::dbres($usec,0);
      $ssecret=self::getsecret();
      $oaes=hash("sha512",substr($usec,128,128).substr($ssecret,128,128));
      $oaes=hash("sha512",$oaes.substr($ssecret,0,128).substr($usec,0,128));
      $oaes=substr($oaes,(32*$uid%4),32);
      $otp=self::aescrypt($otp,$oaes);
      self::dbq('wotp','update','otp="'.$otp.'"','users','uid='.$uid);
      self::dbclose();
    }
    else echo "uid net numerisch";
  }

  //session tools

  //logon User
  public static function logon($user, $pw, $otp="",$captcha="",$clefcode="") {
    $check=self::verify();
    if ($clefcode) {
      $clid=self::clef($clefcode); //get Clef User-ID
      if((is_int($clid) || ctype_digit($clid)) && (int)$clid >= 0) {
        self::dbc("clogon");
        $cnt=self::dbres(self::dbq("clogon","select","count(clid)","users",'clid='.$clid),0);
        if($cnt) {
          $clid=self::dbq("clogon","select","uid","users",'clid='.$clid);  //overwrite clef-id with request for user-id
          $luid=self::dbres($clid,0);
          if($luid && (is_int($luid) || ctype_digit($luid)) && (int)$luid >= 0) {
            if(strpos($check,"clef:")!==false)
            {
              $nclid=explode(":",$check)[1];
              self::dbq('logon','update','clid="'.$nclid.'"','users','uid='.$luid);
              self::logout();
            }
            self::wsession($luid,"clef");
            header("Location: login.php");
          }
          else
            return false;
        }
        else {
          if ($cnt!==false) {
            $type="clef:".$clid;
            self::wsession(0,$type);
          }
        }
      }
    }
    else {
      if(self::ccheck($captcha)) {
        // open ckey database
        self::dbc("logon");
        $user=self::clean($user);
        $cnt=self::dbres(self::dbq("logon","select","count(uid)","users",'uname="'.$user.'"'),0);
        if($cnt>0) {
          $loginfo=self::dbq("logon","select","password,uid,otp,usecret","users",'uname="'.$user.'"');
          $luid=self::dbres($loginfo,0,1);
          $usec=self::dbres($loginfo,0,3);
          $lotp=self::dbres($loginfo,0,2);
          $loginfo=self::dbres($loginfo,0);
          self::dbclose();
          $ssecret=self::getsecret();
          $pw=hash("sha512",$pw.substr($usec,0,128).substr($ssecret,128,128));
          $pw=hash("sha512",$pw.substr($ssecret,0,128).substr($usec,128,128));
          if ($pw==$loginfo){ //check correct PW
            if($lotp){ //ckeck for db OTP  //for disabling OTP Cmment from this line to
              if($otp){
                if(self::clean($lotp,32)!=$lotp){ //OTP-seed not base32 -> AES-crypted or corrupt
                  if(self::clean($lotp,64)==$lotp){ //otp-seed base64 --> AES-crypted or corrupt
                    $oaes=hash("sha512",substr($usec,128,128).substr($ssecret,128,128));
                    $oaes=hash("sha512",$oaes.substr($ssecret,0,128).substr($usec,0,128));
                    $oaes=substr($oaes,(32*$luid%4),32);
                    $otplain=self::aesdecrypt($lotp,$oaes);
                    if($otplain==self::clean($otplain,32)&&strlen($otplain)>=16)
                    {
                      if(otp::verify_key($otplain, $otp,5)); //wenn okay -> nichts machen -> einfach weiter zum ende des OTP checks
                      else
                        return false; //sonst raus hier
                    }
                    else
                      return false; //OTP AES Fail
                  }
                  else
                    return false; // OTP DB Problem
                }
                else{  //plaintext Seed
                  if(strlen($lotp>=16)&&otp::verify_key($lotp, $otp,5)); //wenn okay -> nichts machen -> einfach weiter zum ende des OTP checks
                  else
                    return false; //sonst raus hier
                }
              }
              else
                return false; //OTp nötig aber nicht angegeben.
            }  //OTP Check end  --> for disabling OTP comment until this line (including itself)
            if(strpos($check,"clef:")!==false)
            {
              $nclid=explode(":",$check)[1];
              self::dbc("logon");
              self::dbq('logon','update','clid='.$nclid.'','users','uid='.$luid);
              self::dbclose();
              self::logout();
            }
            self::wsession($luid);
            header("Location: login.php");
          }
          else{  //wrong pass
            echo("logon fail1");
            setcookie('key','',time()-3600);
            unset($_COOKIE["key"]);
            return false;
          }
        }
        else {  //wrong user
          $_POST["user"]="";
          return false;
        }
      }
      else {
          echo "captcha vergessen oder etwas stimmt nicht...";  //captcha fail
          $_POST["user"]="";
      }
    }
  }

  //create AES key for Session Cookie
  function cryptokey($uid,$cok=false,$debug=false) {
    $ssecret=self::getsecret();//get Server Secret from file
    if($uid!=0) {
      self::dbc("cryptokey");
      $usec=self::dbq("cryptokey","select","usecret","users",'uid = "'.$uid.'"');//get usersecret from DB
      $usec=self::dbres($usec,0);//get user secret from DB Result
    }
    else {
      $usec="";
    }
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

  //Write session
  private function wsession($luid=0,$type="") {
    self::dbc("wsession");
    if((is_int($luid) || ctype_digit($luid)) && (int)$luid >= 0) {
      $ip=self::ip();
      $chash=self::createRandomKey();
      $duration=time()+600;
      $logon2=hash("sha512",$chash.hash("sha512",$ip));
      self::dbq("wsession","insert","cid,void,suid,type","session",'"'.$logon2.'",'.$duration.','.$luid.',"'.$type.'"');//create new session in db
      unset($logon2);
      $chash=self::aescrypt($chash,self::cryptokey($luid));
      setcookie('key',$luid.":".$chash,$duration);
    }
    self::dbclose();
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
          $num=self::dbres(self::dbq("verify","select","count(sID)","session",'cid="'.$hkey.'"'),0);//count server sIDs for session Key
          if ($num>0){  //if an ID exists
            $query=self::dbq("verify","select","suid,type","session",'cid="'.$hkey.'"');
            $login=self::dbres($query,0);//get server session-uID
            $type=self::dbres($query,0,1);
            if($login==$uid) { //if both IDs match
              $extend=time()+600; //create new session end
              self::dbq('verify','update','void='.$extend,'session','cid="'.$hkey.'"');//update new session end in db
              setcookie('key',$uid.":".self::aescrypt($key,self::cryptokey($uid)),$extend); //create/replace AES-crypted cookie
              if ($login===0||$login==="0") {
                $login=$type;
              }
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
    self::dbclose();
    return $login;
  }

  //decrypt Session Cookie
  function cdec($cookie){
    if(substr_count($cookie,":")==1) { //cookie sections intact -> 2 sections seperated with :
      $id=explode(":",$cookie)[0];
      $cipher=explode(":",$cookie)[1];
      if((is_int($id) || ctype_digit($id)) && (int)$id >= 0 && $cipher==self::clean($cipher,64)) { //cookie data intact -> userID=number,cipher=base64string
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
    if((is_int($uid) || ctype_digit($uid)) && (int)$uid >= 0){
      self::dbc("isadmin");
      return self::dbres(self::dbq("isadmin","select","admin","users","uid=".$uid),0);
      self::dbclose();
    }
  }

  //logout user
  public static function logout($type="",$id="") {
    self::getsecret();
    if(isset($_COOKIE["key"])){
      $key=self::cdec($_COOKIE["key"]); //decode Cookie -> get client uID+sID
      if($key) {
        $key=explode(":",$key)[1]; // get client sID
        self::dbc("logout");
        $logondata=hash("sha512",$key.hash("sha512",self::ip()));
        self::dbq('logout','delete','','session','cid="'.$logondata.'"');
        unset($logondata);
        self::dbclose();
        header("Location: login.php");
      }
    }
    if($type="clef"&&(is_int($id) || ctype_digit($id)) && (int)$id >= 0) {
      self::dbc("clefout");
      $uid=self::dbq("clefout","select","uid","users",'clid='.$id);
      $uid=self::dbres($uid,0);
      self::dbq("clefout","delete","","session","suid=".$uid.' AND type="clef"');
    }
    setcookie('key','',time()-3600);
    unset($_COOKIE["key"]);
  }

}
?>
