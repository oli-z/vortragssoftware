<?php
class config{
// MySQL-Einstellungen
public static $dbhost = "localhost"; // DB-Server
public static $dbuser = "schuletest"; // DB-User
public static $dbpass = ""; // DB-Password
public static $dbname = "schuletest"; // DB-Name
public static $prefix = "";
// allgemeine Einstellungen
public static $lang = "de"; // Sprache - Sprachdatei muss im "lang"-Ordner liegen
public static $stimer = 0;  //session duration in seconds (or 0 for almost infinite -> ATTENTION: in combination with high or infinite session count this can lead to a huge session table)
public static $scount = 2; //number of simultaneous sessions per user (or 0 for infinite -> ATTENTION: in combination with high or infinite session time this can lead to a huge session table)
public static $otpact = false; //otp enabled -> true or false
public static $sipact = false; //use IP in session
public static $capact = false; //reCaptcha enabled -> true or false
public static $cappub = "6Lfzct4SAAAAAMpwqKGnuyvPitY2QWcE101EfwX_"; //reCaptcha-sitekey -> leave empty to disable
public static $capkey = "6Lfzct4SAAAAAEikbCIE7xMupWfEolC_VeMsTTwu"; //reCaptcha-secretkey -> leave empty to disable
public static $clfact = true;
public static $clfpub = ''; //clef public key -> leave empty to disable
public static $clfkey = ''; //clef private key -> leave empty to disable
public static $clfred = 'http://domain.tld/path/clef.php?type=clef';
}
?>
