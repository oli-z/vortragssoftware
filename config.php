<?php
class config{
  // MySQL-Einstellungen
  public static $dbhost = "localhost"; // DB-Server
  public static $dbuser = "schuletest"; // DB-Nutzername
  public static $dbpass = ""; // DB-Passwort
  public static $dbname = "schuletest"; // DB-Name
  public static $prefix = "";
  // allgemeine Einstellungen
  public static $lang = "de"; // Sprache - Sprachdatei muss im "lang"-Ordner liegen
  public static $stimer = 172800;  //session duration in seconds (or 0 for almost infinite -> ATTENTION: in combination with high or infinite session count this can lead to a huge session table)
  public static $scount = 2; //number of simultaneous sessions per user (or 0 for infinite -> ATTENTION: in combination with high or infinite session time this can lead to a huge session table)
  public static $chttps = false; //cookie only over https
  public static $ckpath = "/";   //cookie path
  public static $otpact = false; //otp enabled -> true or false
  public static $sipact = false; //use IP in session
  public static $capact = false; //reCaptcha enabled -> true or false
  public static $cappub = ""; //reCaptcha-sitekey
  public static $capkey = ""; //reCaptcha-secretkey
  public static $clfact = true;
  public static $clfpub = ''; //clef public key
  public static $clfkey = ''; //clef private key
  public static $clfred = 'http://domain.tld/clef.php?type=clef';
}
class style{
  public static function warn($text="") { 
   return '<div class="alert wise wise-w alert-warning">'.$text.'</div>';
  }
  
  public static function info($text="") { 
   return '<div class="alert wise wise-i alert-info">'.$text.'</div>';
  }
  
  public static function success($text="") { 
   return '<div class="alert wise wise-s alert-success">'.$text.'</div>';
  }
  
  public static function error($text="") { 
   return '<div class="alert wise wise-e alert-danger">'.$text.'</div>';
  }
}
?>
