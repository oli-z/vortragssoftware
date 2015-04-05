<?php
class config{
// MySQL-Einstellungen
public static $dbhost = "localhost"; // DB-Server
public static $dbuser = "schuletest"; // DB-User
public static $dbpass = ""; // DB-Password
public static $dbname = "schuletest"; // DB-Name
// allgemeine Einstellungen
public static $lang = "de"; // Sprache - Sprachdatei muss im "lang"-Ordner liegen
public static $cappub = "6Lfzct4SAAAAAMpwqKGnuyvPitY2QWcE101EfwX_"; //reCaptcha-sitekey -> leave empty to disable
public static $capkey = "6Lfzct4SAAAAAEikbCIE7xMupWfEolC_VeMsTTwu"; //reCaptcha-secretkey -> leave empty to disable
public static $clfpub = ''; //clef public key -> leave empty to disable
public static $clfkey = ''; //clef private key -> leave empty to disable
public static $clfred = 'http://localhost/oli/clef.php?type=clef';
}
?>
