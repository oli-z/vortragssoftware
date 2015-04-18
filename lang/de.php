<?php
//Language file for auth.php
//Authors: My2, orzm
class lang{
public static $title=
public static $dberror="<strong>Fehler:</strong> Es konnte keine Verbindung zur MySQL-Datenbank hergestellt werden.";
public static $sqlerror="<strong>Fehler:</strong> es konnte keine Verbindung zum MySQL-Server hergestellt werden.";
public static $loginform="Login-Formular";
public static $loginformtext="Bitte logge dich zuerst ein:";
public static $user="Benutzername";
public static $pass="Passwort";
public static $pass2="Passwort (wiederholen)";
public static $otp="OTP (Wenn du nicht weißt, was das ist, bitte freilassen.)";
public static $login="einloggen";
public static $logout="ausloggen";
public static $ghost="überall ausloggen";
public static $choose="Vortragswahl";
public static $choosetext="Bitte wähle einen Vortrag von jeder Vortragszeit aus:";
public static $notloggedin="Du bist nicht eingeloggt oder deine Session ist abgelaufen. Bitte erneut einloggen.";
public static $otperr="Der OTP-Schlüssel ist nicht gültig oder OTP wird für diesen Benutzer nicht unterstützt.";
public static $userexists="Der gewählte Nutzername existiert bereits.";
public static $nouser="Es wurde kein oder ein ungültiger Benutzername angegeben.";
public static $clefdisconnected="Die Verbindung zu Clef wurde getrennt. Du bist nun ausgeloggt.";
public static $clefdisconnerr="Die Verbindung zu Clef konnte nicht getrennt werden, da du noch in Clef eingeloggt bist.";
public static $clefiderr="Die angegebene Clef-ID wird bereits von einem anderem Benutzer verwendet.";
public static $clefsuccess="Der Benutzeraccount wurde erfolgreich mit Clef verbunden.";
public static $clefunknown="Die angegebene Clef-ID wurde in der Datenbank leider nicht gefunden."
public static $clefdisabled="Clef wurde deaktiviert. Bitte wende dich an den Admin oder nutze die normale Login-Funktion.";
public static $otpfalse="Der eingegebene OTP-Schlüssel ist falsch. Bitte überprüfe deine Eingabe.";

}
?>
