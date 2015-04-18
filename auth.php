<?php
  require_once('config.php');  //config data, like language 
  require_once('lang/'.config::$lang.'.php'); //language data for error codes
  require_once('otp.php'); //OTP library comment this and wotp and the OTP part of verify() out for shutting off OTP
  require_once('easysql.php');
  
  class auth {
    public static $msg="";
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
      if (!(empty($_SERVER['HTTP_X_FORWARDED_FOR'])&&$noproxy)) {
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
    
    //check for natural number
    public static function n($n) {  //return bool
      if((is_int($n) || ctype_digit($n)) && (int)$n >= 0)
        return true;
      else
        return false;
    }
    
    //AES cryptoset
    public static function aescrypt($encrypt, $mc_key) {
      $passcrypt = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $mc_key,trim($encrypt),MCRYPT_MODE_ECB);
      $encode = base64_encode($passcrypt);
      return $encode;
    }
    
    public static function aesdecrypt($decrypt, $mc_key) {
      $decoded = base64_decode($decrypt);
      $decrypted = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $mc_key,$decoded,MCRYPT_MODE_ECB));
      return $decrypted;
    }
    
    //tools for clef
    //clef logon
    private function clef($code){  //return clef_id or false
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
        } 
        else {
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
    private function clefout($token) { //returns clef_id or false
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
          return $response['clef_id'];
        }
        else
          return false;
      }
      else
        return false;
    }
    
    //holt Session secret key aus datei
    private function getsecret() {
      include ("inc/secure/secret.php"); //super-secret server key -> $fsecret
      return $fsecret;
    }
    
    //captcha check
    private function ccheck($captcha) {  //return bool
      if(isset(config::$capact)&&config::$capact&&isset(config::$cappub)&&config::$cappub&&isset(config::$capkey)&&config::$capkey) {
        $response=file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=".config::$capkey."&response=".$captcha."&remoteip=".$_SERVER['REMOTE_ADDR']);
        if(!$response)
        return true;  //if no captccha response mark as true to avoid lockout
        $response = json_decode($response, true);
        return $response['success']; //true or false based on success of captcha solving
      }
      else
        return true;  //if no keys set, mark as true
    }
    
    //User tools
    //create user
    public static function cuser($uname,$pw,$adm=0,$otp="") {
      $user=self::clean($uname);//clean user
      if($uname&&$pw) {//check if both are set (and in case of $user if something is left after cleaning)
        esql::dbc("cuser"); //db connect
        $cnt=esql::dbres(esql::dbq("cuser","select","count(uid)","users",'uname="'.$uname.'"'));
        if($cnt===0||$cnt==="0") {
          // check for alredy existing username === because in case of failure false is given qhich would be equal 0.
          $ssecret=self::getsecret(); //get server secret
          $usec=self::createRandomKey(); //create fresh user secret
          $pw=hash("sha512",$pw.substr($usec,0,128).substr($ssecret,128,128));//create PW Hash step 1
          $pw=hash("sha512",$pw.substr($ssecret,0,128).substr($usec,128,128));//create PW Hash step 2
          if($adm==true)  //check for admin parameter
            $adm=1;  //set admin
          else
            $adm=0;  //or not
          esql::dbq("cuser","insert","uname,password,usecret,admin","users",'"'.$uname.'","'.$pw.'","'.$usec.'",'.$adm);//create new user in db
          $uid=esql::dbres(esql::dbq("cuser","select","uid","users",'uname="'.$uname.'"'),0); //get User-ID
          esql::dbclose();
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
      if(self::n($uid)){
        esql::dbc("wp");
        $usec=esql::dbq("wp","select","usecret","users",'uid = "'.$uid.'"');
        $usec=esql::dbres($usec,0);
        $ssecret=self::getsecret();
        $pw=hash("sha512",$pw.substr($usec,0,128).substr($ssecret,128,128));
        $pw=hash("sha512",$pw.substr($ssecret,0,128).substr($usec,128,128));
        //echo $pw; //debug
        esql::dbq('wp','update','password="'.$pw.'"','users','uid='.$uid);
        esql::dbclose();
      }
      else echo "uid net numerisch";
    }
    
    //write otp
    public static function wotp($uid,$otp){
      if(self::n($uid)){
        $otp=self::clean($otp,32);//clean OTP key to base32
        esql::dbc("wotp");
        $usec=esql::dbq("wotp","select","usecret","users",'uid = "'.$uid.'"');
        $usec=esql::dbres($usec,0);
        $ssecret=self::getsecret();
        $oaes=hash("sha512",substr($usec,128,128).substr($ssecret,128,128));
        $oaes=hash("sha512",$oaes.substr($ssecret,0,128).substr($usec,0,128));
        $oaes=substr($oaes,(32*$uid%4),32);
        $otp=self::aescrypt($otp,$oaes);
        esql::dbq('wotp','update','otp="'.$otp.'"','users','uid='.$uid);
        esql::dbclose();
      }
      else echo "uid net numerisch";
    }
    
    public static function clfdis($uid){
      if(auth::verify("type")!="clef") {
        if(self::n($uid)){
          esql::dbc("clfdis");
          esql::dbq('clfdis','update','clid=NULL','users','uid='.$uid);
          esql::dbclose();
          session_start();
          @$_SESSION["msg"].=style::success("Die Zuordnung mit clef wurde gelöscht.");
          self::logout("clfdis",$uid);
          header("Location: login.php");
          die();
        }
      }
      else { //doesnt normally occur, but as failsafe
        session_start();
        @$_SESSION["msg"].=style::warn("Die Zuordnung mit clef kann nicht gelöscht werden, wenn du mit clef eingeloggt bist.");
        header("Location: login.php");
      }
    }
    
    //session tools
    //logon User
    public static function logon($user, $pw, $otp="",$captcha="",$clefcode="") {
      $check=self::verify();
      if ($clefcode) {
        if(isset(config::$clfact)&&config::$clfact) {
          $clid=self::clef($clefcode); //get Clef User-ID
          if(self::n($clid)) {
            esql::dbc("clogon");
            $cnt=esql::dbres(esql::dbq("clogon","select","count(uid)","users",'clid='.$clid),0);
            if($cnt) {
              if(!$check) {
                $luid=esql::dbres(esql::dbq("clogon","select","uid","users",'clid='.$clid),0);
                if($luid && self::n($luid)) {
                  self::wsession($luid,"clef");
                  header("Location: login.php");
                }
                else
                  return false;
              }
              else {
                session_start();
                $_SESSION["msg"].=style::warn("Die angegebene Clef-ID wird bereits verwendet.");
                return false;
              }
            }
            else {
              if ($cnt!==false) {
                if(self::n($check)) {
                  esql::dbq('logon','update','clid="'.$clid.'"','users','uid='.$check);
                  @session_start();
                  self::logout("norel");
                  self::wsession($check,"clef");
                  $_SESSION["msg"].=style::success("Die Account wurde erfolgreich mit clef verbunden.");
                }
                else
                {
                  session_start();
                  $_SESSION["msg"].=style::warn("Die angegebene Clef-ID ist leider unbekannt");
                }
              }
            }
          }
        }
        else {
          self::$msg.=style::error("clef wurde deaktiviert. Bitte wenden Sie sich an den Admin.");
          return false;
        }
      }
      else {
        if(self::ccheck($captcha)) {
          // open ckey database
          esql::dbc("logon");
          $user=self::clean($user);
          if($user) {
            $cnt=esql::dbres(esql::dbq("logon","select","count(uid)","users",'uname="'.$user.'"'),0);
            if($cnt>0) {
              if($pw) {
                $loginfo=esql::dbq("logon","select","password,uid,otp,usecret","users",'uname="'.$user.'"');
                $luid=esql::dbres($loginfo,0,1);
                $usec=esql::dbres($loginfo,0,3);
                $lotp=esql::dbres($loginfo,0,2);
                $loginfo=esql::dbres($loginfo,0);
                esql::dbclose();
                $ssecret=self::getsecret();
                $pw=hash("sha512",$pw.substr($usec,0,128).substr($ssecret,128,128));
                $pw=hash("sha512",$pw.substr($ssecret,0,128).substr($usec,128,128));
                if ($pw==$loginfo){ //check correct PW
                  if(config::$otpact) {  //check for otp enabled for disabling otp altogether set otpact in config.php to false
                    if($lotp){ //ckeck for db OTP in userdb
                      if($otp){
                        if(self::clean($lotp,32)!=$lotp){ //OTP-seed not base32 -> AES-crypted or corrupt
                          if(self::clean($lotp,64)==$lotp){ //otp-seed base64 --> AES-crypted or corrupt       
                            $oaes=hash("sha512",substr($usec,128,128).substr($ssecret,128,128));
                            $oaes=hash("sha512",$oaes.substr($ssecret,0,128).substr($usec,0,128));
                            $oaes=substr($oaes,(32*$luid%4),32);
                            $otplain=self::aesdecrypt($lotp,$oaes);
                            if($otplain==self::clean($otplain,32)) {
                              if(strlen($otplain)>=16){ //wenn OTP seed >=16 chars, weiter zur prüfung
                                if(otp::verify_key($otplain, $otp,5)); //wenn okay -> nichts machen -> einfach weiter zum ende des OTP checks
                                else { //sonst raus hier
                                  self::$msg.=style::warn("Das Einmalpasswort (OTP) ist falsch.");
                                  return false;
                                }
                              }
                              else {
                                self::$msg.=style::error("Der OTP seed in der Datenbank ist zu kurz. Bitte Administrator kontaktieren.");
                              }
                            }
                            else { //OTP AES Fail
                              self::$msg.=style::error("Der Seed für das Einmalpasswort (OTP) konnte nicht entschlüsselt werden. Bitte Administrator kontaktieren.");
                              return false; 
                            }
                          }
                          else { // OTP DB Problem
                            self::$msg.=style::error("Der Seed für das Einmalpasswort (OTP) ist defekt. Bitte Administrator kontaktieren.");
                            return false; 
                          }
                        }
                        else {  //plaintext Seed
                          if(strlen($lotp>=16)) //wenn okay -> nichts machen -> einfach weiter zum ende des OTP checks
                            if(otp::verify_key($lotp, $otp,5));
                            else
                              self::$msg.=style::warn("Das Einmalpasswort (OTP) ist falsch.");
                          else { //OTP db fail
                            self::$msg.=style::error("Der OTP seed in der Datenbank ist zu kurz. Bitte Administrator kontaktieren.");
                            return false; //sonst raus hier,
                          }
                        }
                      }
                      else {
                        self::$msg.=style::warn("Es ist ein Einmalpasswort (OTP) nötig um sich in diesen Account einzuloggen.");
                        return false; //OTp nötig aber nicht angegeben.
                      }
                    }
                  }
                  self::wsession($luid);
                  header("Location: login.php");
                }
                else{  //wrong pass
                  setcookie('key','',time()-3600,config::$ckpath,"",config::$chttps,true);
                  unset($_COOKIE["key"]);
                  self::$msg.=style::warn("Das Passwort ist falsch...");
                  //return false;
                }
              }
              else {  //no pass
                self::$msg.=style::warn("Bitte gib ein Passwort ein.");
                return false;
              }
            }
            else { //user doesnt exist
              $_POST["user"]="";
              self::$msg.=style::warn("Der User existiert nicht.");
              return false;
            }
          }
          else { //no user
            self::$msg.=style::warn("Bitte gib einen Benutzernamen ein.");
            return false;
          }
        }
        else {
          self::$msg.=style::warn("captcha vergessen oder etwas stimmt nicht...");  //captcha fail
          $_POST["user"]="";
        }
      }
    }
    
    //create AES key for Session Cookie
    private function cryptokey($uid,$cok=false,$debug=false) { //return session-cryptokey
      $ssecret=self::getsecret();//get Server Secret from file
      if($uid!=0) {
        esql::dbc("cryptokey");
        $usec=esql::dbq("cryptokey","select","usecret","users",'uid = '.$uid.'');//get usersecret from DB
        $usec=esql::dbres($usec,0);//get user secret from DB Result
      }
      else {
        $usec="";  //safety fallback
      }
      if(config::$stimer!=0) {
      if(!$cok) //check old key=false -> check current key
        $t=time();
      else
        $t=time()-(config::$stimer); //go back one cycle to get an old key in case of older session
      $base=floor($t/(config::$stimer*4)); //clculate session base
      $offset=$base%4*32; //offset des Hash =1/4 base, weil AES max keylength-> 32 char und hash = 128 char -> 4 mögliche keys
      }
      else {
        $base=0;
        $offset=0;
      }
      $aeshash=hash("sha512",$ssecret.$usec.$base.$_SERVER['HTTP_USER_AGENT']);//berechne Basis-Hash aus Tag, Server Secret und User Secret
      $aeskey=substr($aeshash,($offset),32); //sortiere key aus Hash
      if($debug) 
        echo ("ssec=".$ssecret."<br>usec=".$usec."<br>base=".$base."<br>browser=".$_SERVER['HTTP_USER_AGENT']."<br>off=".$offset."<br>hash=".$aeshash."<br>key=".$aeskey."<br>"); //debug
      return $aeskey; //gib key zurück
    }
    
    //Write session
    private function wsession($luid=0,$type="") {  //returns nothing
      esql::dbc("wsession");
      if(self::n($luid)) {
        if(config::$scount) {
          $cnt=esql::dbq("wsession","select","count(*),sid","session","suid=".$luid." ORDER BY void asc");
          if(esql::dbres($cnt,0,0)>=config::$scount)
          esql::dbq("wsession","delete","","session","sid=".esql::dbres($cnt,0,1));
        }
        if(config::$sipact)
          $ip=self::ip();
        else
          $ip="0.0.0.0";
        $chash=self::createRandomKey();
        if(config::$stimer)
          $duration=time()+config::$stimer;
        else
          $duration=2147483647; //cookies always expire, this is January 2038, value cannot be higher because of overflow.
        $logon2=hash("sha512",$chash).hash("sha512",$ip);
        esql::dbq("wsession","insert","cid,void,suid,type","session",'"'.$logon2.'",'.$duration.','.$luid.',"'.$type.'"');//create new session in db
        unset($logon2);
        $chash=self::aescrypt($chash,self::cryptokey($luid));
        setcookie('key',$luid.":".$chash,$duration,config::$ckpath,"",config::$chttps,true);
      }
      esql::dbclose();
    }
    
    //check for and verify Session
    public static function verify($s=false) {  //return uid, "clef:<clef_id>" or false
      esql::dbc("verify");
      if(config::$stimer) //if session timer off then no session cleanup
        esql::dbq("verify","delete","","session",'void<'.time());  //delete old cIDs
      //check cookie
      if(isset($_COOKIE["key"])){ //if cookie exists
        //get IP
        if(config::$sipact)
          $ip=self::ip();
        else
          $ip="0.0.0.0";
        //clean key
        $key=self::cdec($_COOKIE["key"]); //decode Cookie -> get client uID+sID
        if($key) { //if decoding worked
          $uid=explode(":",$key)[0]; // get client uID
          $key=explode(":",$key)[1]; // get client sID
          $hkey=hash("sha512",$key);//.hash("sha512",$ip); //get DB sID by hashing of Client sID and IP
          //check key
          if(strlen($key)==256){ //ckeck key length
            $num=esql::dbres(esql::dbq("verify","select","count(sID)","session",'cid LIKE"'.$hkey.'%"'),0);//count server sIDs for session Key
            if ($num>0){  //if an ID exists
              $query=esql::dbq("verify","select","suid,type,sid,cid","session",'cid LIKE"'.$hkey.'%"');
              $login=esql::dbres($query,0);//get server session-uID
              if(substr(esql::dbres($query,0,3),128,128)==hash("sha512",$ip)) {
                if($login==$uid) { //if both IDs match
                  if(!$s) { //if asking for sID or type
                    if(config::$stimer)
                      $extend=time()+config::$stimer; //create new session endtime
                    else
                      $extend=2147483647; //cookies always expire, this is January 2038, value cannot be higher because of overflow.
                    esql::dbq('verify','update','void='.$extend,'session','cid="'.$hkey.'"');//update new session end in db
                    setcookie('key',$uid.":".self::aescrypt($key,self::cryptokey($uid)),$extend,config::$ckpath,"",config::$chttps,true); //create/replace AES-crypted cookie
                  }
                  else if($s=="type")
                    return esql::dbres($query,0,1);
                  else if($s===true)
                    return esql::dbres($query,0,2);
                }
                else{ //ids dont match
                  $login=false;
                  setcookie('key','',time()-3600,config::$ckpath,"",config::$chttps,true);
                  unset($_COOKIE["key"]);
                  self::$msg.=style::error('Sitzungsschlüssel defekt.<br />Bitte neu einloggen<br />Info für Administratoren: Session uIDs falsch.');
                }
              }
              else { //different IP
                $login=false;
                setcookie('key','',time()-3600,config::$ckpath,"",config::$chttps,true);
                unset($_COOKIE["key"]);
                self::$msg.=style::warn('Deine IP-Adresse hat sich geändert.<br />Bitte neu einloggen<br />');
              }
            }
            else{ //session not in db
              $login=false;
              setcookie('key','',time()-3600,config::$ckpath,"",config::$chttps,true);
              unset($_COOKIE["key"]);
              self::$msg.=style::warn("deine Session wurde gelöscht. Bitte neu einloggen.");
            }
          }
          else{ //key too short
            setcookie('key','',time()-3600,config::$ckpath,"",config::$chttps,true);
            unset($_COOKIE["key"]);
            $login=false;
            self::$msg.=style::error('Sitzungsschlüssel defekt.<br />Bitte neu einloggen<br />Info für Administratoren: Sessionschlüssel zu kurz.');
          }
        }
        else{ //decoding messed up.
          setcookie('key','',time()-3600,config::$ckpath,"",config::$chttps,true);
          unset($_COOKIE["key"]);
          self::$msg.=style::error('Sitzungsschlüssel defekt.<br />Bitte neu einloggen (hat sich möglicherweise deine IP geändert?)<br />Info für Administratoren: Sessionschlüssel konnte nicht entschlüsselt werden.');
          $login=false;
        }
      }
      else {
      $login=false;
      }
      esql::dbclose();
      return $login;
    }
    
    //decrypt Session Cookie
    private function cdec($cookie){  //return decrypted session key from cookie (256 a-zA-Z0-9 string) or false
      if(substr_count($cookie,":")==1) { //cookie sections intact -> 2 sections seperated with :
        $id=explode(":",$cookie)[0];
        $cipher=explode(":",$cookie)[1];
        if(self::n($id) && $cipher==self::clean($cipher,64)) { //cookie data intact -> userID=number,cipher=base64string
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
    public static function isadmin($uid){ //return content of isadmin col
      if(self::n($uid)){
        esql::dbc("isadmin");
        return esql::dbres(esql::dbq("isadmin","select","admin","users","uid=".$uid),0);
        esql::dbclose();
      }
    }
    
    public static function getcdata($uid,$data) {
      if(self::n($uid)){
        esql::dbc("cdata");
        $res=esql::dbq("cdata","select",$data,"users","uid=".$uid);
        esql::dbclose();
        if($res)
          return esql::dbres($res,0);
        else
          return false;
      }
    }
    //logout user
    public static function logout($type="",$id="") {  //return nothing
      if($type!="clfdis") {
        self::getsecret();
        if(isset($_COOKIE["key"])){
          $key=self::cdec($_COOKIE["key"]); //decode Cookie -> get client uID+sID
          if($key) {
            $key=explode(":",$key)[1]; // get client sID
            esql::dbc("logout");
            if(config::$sipact)
              $ip=self::ip();
            else
              $ip="0.0.0.0";
            $logondata=hash("sha512",$key.hash("sha512",$ip));
            esql::dbq('logout','delete','','session','cid="'.$logondata.'"');
            unset($logondata);
            esql::dbclose();
          }
        }
      }
      if($type=="ghost") {
        esql::dbc("ghost");
        esql::dbq('ghost','delete','','session','suid='.$id);
        esql::dbclose();
      }
      if($type=="clef") {
        $id=self::clefout($id);
        if($id!==false && self::n($id)) {
          esql::dbc("clefout");
          $uid=esql::dbq("clefout","select","uid","users",'clid='.$id);
          $uid=esql::dbres($uid,0);
          esql::dbq("clefout","delete","","session","suid=".$uid.' AND type="clef"');
          esql::dbclose();
          die();
        }
      }
      if($type=="clfdis") {
        if($id!==false && self::n($id)) {
          esql::dbc("logout-clfdis");
          esql::dbq("logout-clfdis","delete","","session","suid=".$id.' AND type="clef"');
          esql::dbclose();
        }
        return;
      }
      setcookie('key','',time()-3600,config::$ckpath,"",config::$chttps,true);
      unset($_COOKIE["key"]);
      session_start();
      if($type=="norel") {
        return 1;
      }
      else {
        if ($type!="ghost")
          $_SESSION["msg"].=style::info("Du wurdest erfolgreich ausgeloggt");
        else
          $_SESSION["msg"].=style::success("Du wurdest erfolgreich auf allen Geräten ausgeloggt");
      }
      header("Location: login.php");
      die();
    }
  }
?>
