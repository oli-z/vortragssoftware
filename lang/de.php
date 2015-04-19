<?php
//Language file for auth.php
//Authors: My1, orzm
class lang{
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
public static $clefconnected="Dein Account ist mit der Clef-ID <{id}> verbunden.";
public static $clefdisconnerr="Die Verbindung zu Clef konnte nicht getrennt werden, da du noch in Clef eingeloggt bist.";
public static $clefiderr="Die angegebene Clef-ID wird bereits von einem anderem Benutzer verwendet.";
public static $clefnodis="um deine clef-ID zu trennen, logge dich bitte mit Benutzername und Passwort ein.";
public static $clefcon="Wenn du deinen Account mit clef verbinden möchtest, klicke <{here}>";
public static $clefin="Anmelden über Clef";
public static $clefsuccess="Der Benutzeraccount wurde erfolgreich mit Clef verbunden.";
public static $clefunknown="Die angegebene Clef-ID wurde in der Datenbank leider nicht gefunden.";
public static $clefdisabled="Clef wurde deaktiviert. Bitte wende dich an den Admin oder nutze die normale Login-Funktion.";
public static $clefdiscon="Zum trennen bitte <{here}> klicken.";
public static $otpfalse="Der eingegebene OTP-Schlüssel ist falsch oder mit dem eingegebenen OTP-Schlüssel stimmt etwas nicht. Bitte überprüfe deine Eingabe oder kontaktiere den Administrator.";
public static $foradmins="Info für Administratoren: ";
public static $otpaeserr="AES-Fehler";
public static $otpdberr="Es gibt einen Fehler mit der Datenbank.";
public static $otprequired="Es ist ein Einmalpasswort (OTP) nötig um sich in diesen Account einzuloggen.";
public static $wrongpw="Da eingegebene Passwort ist falsch.";
public static $nopw="Das Passwortfeld darf nicht leer sein. Bitte gib ein Passwort ein.";
public static $wronguser="Der eingegebene Benutzer existiert nicht in der Datenbank. Bitte überprüfe deine Eingabe.";
public static $emptyuser="Der Benutzername darf nicht leer sein. Bitte gib einen Benutzernamen an.";
public static $nocaptcha="DU hast das Captcha vergessen oder etwas stimmt mit dem Captcha nicht.";
public static $sessionkeyerr="Der Sitzungsschlüssel ist defekt. Bitte logge dich neu ein.";
public static $sessionkeyiperr="Deine IP-Adresse hat sich geändert. Bitte logge dich neu ein.";
public static $sessionexpired="Deine Sitzung ist abgelaufen. Bitte logge dich neu ein.";
public static $loggedout="Du wurdest erfolgreich ausgeloggt.";
public static $loggedouteverywhere="Du wurdest erfolgreich auf allen Geräten ausgeloggt.";
public static $here="hier";
public static $loginok="Du bist korrekt eineloggt. <{here}> kommst du zur Auswahl.";
public static $adminok="Du bist Administrator. Um zur Administration fortzuschreiten, klicke bitte <{here}>";
}
?>
