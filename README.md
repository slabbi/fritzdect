## Inhalt
* [Allgemeine Informationen](#Allgemeine_Informationen)
* [Technologie](#technologie)
* [Installation](#Installation)
* [Konfiguration](#Konfiguration)
* [Bemerkungen](#Bemerkungen)

## Allgemeine Informationen
Das Skript erlaubt die Verbrauchsdaten eines an einer Fritz!DECT 200 bzw. 210 Steckdose angeschlossenen Geräts auszulesen und erzeugt jeweils pro Tag ein Diagram.
Entstanden ist das Skript, um die mit einem 600 Watt Balkonkraftwerk erzeute Leistung zu visualisieren; es kann aber auch zur Visualisierung von Verbräuchen an der Steckdose 
verwendet werden.
	
## Technologie

Benötigt wird
* Fritz!DECT 200 oder Fritz!DECT 210 Steckdose,
* ein Server, der Zugriff auf die Steckdose hat (hier wird ein vorhandener "pihole" dazu verwendet),
* PHP muss auf dem Server installiert sein.

## Installation

Die Installation ist relativ einfach. Sofern ein Server mit PHP bereits vorhanden ist, muss das Skript nur konfiguriert werden und einmal am Tag (per cron) gestartet werden.

Falls ein "pihole" vorhanden ist (z.B. auf einem Raspberry Pi), so kann dieser verwendet werden, muss aber noch entsprechend vorbereitet werden:

Installation (auf "pihole") vorbereiten:
> sudo apt-get update

FTP Server installieren:
> sudo apt-get install vsftpd
> 
> sudo nano /etc/vsftpd.conf

Folgende Zeilen der vsftpd.conf ändern:
```
anonymous_enable=NO
local_enable=YES
write_enable=YES
local_umask=022
```

FTP Server neu starten:
> sudo service vsftpd restart

PHP Pakete installieren:
> sudo apt-get install php-mbstring
> 
> sudo apt-get install php-gd
>
> sudo apt-get install curl
>
> sudo apt-get install php7.4-curl

PHP Konfiguration anpassen:
> sudo chmod 0666 /etc/php/7.4/cli/php.ini
>
> sudo chmod 0666 /etc/php/7.4/cgi/php.ini
und ";extension=curl" in "extension=curl" ändern.

Per ftp das Skript fritzdect2xx.php und config.php auf pihole kopieren.

Prüfen, ob alle PHP Libraries vorhanden sind:

> php dectread.php

Es sollte keine Fehlermeldung ausgegeben werden. Mit "^C" das Skript abbrechen.

Bei Fehlern:
> sudo nano /etc/php/7.4/cli/php.ini

und die fehlenden Libraries aktivieren.


Das folgende Shell-Script per ftp im "pi"-Home ablegen:

```
start.sh:
php dectread.php prefix=solar duration=12 withwh=1 withtime=0
```

Die Rechte für das Shellskript setzen:
> chmod 0744 start.sh 

Cron-Jobs setzen:
> crontab -e

Folgende Zeile hinzufügen:
```
00 07 * * * /home/pi/start.sh
```

Das Skript wird jeden Tag um 7 Uhr gestartet und läuft 12 Stunden (duration=12). Erst nach 12 Stunden ist das Bild finalisiert und enthält alle Daten.

## Konfiguration

Es kann relativ viel konfiguriert werden:

Zunächst das Login für die Fritzbox:

```
$host	   = '192.168.1.1';
$user	   = 'admin';
$password  = 'admin';
```

Weitere Konfigurationsmöglichkeiten:

```
$duration  = 10;              // max. Laufzeit des Skripts in Stunden
$withtime  = 0;               // jeweils nach 15 Minuten die Uhrzeit ausgeben
$withwh    = 1;               // jeweils nach $slots Minuten die verbrauchte Energie ausgeben
$sensor    = "123456789012";  // auszulesender Sensor (Fritz!DECT 210)
$prefix    = "";              // Prefix für Dateiname, wenn nicht angegeben wird die Sensor-ID verwendet
$maxwatt   = 640;             // Maximal zu erwartende Leistung (1 Watt = [1 x Skalierung] Pixel)
$scale     = 1.0;             // Skalierung der y-Achse
$scalewh   = 1;               // Messung in Wh (1) oder kWh (1000)
// $slots  = array(0, 30);    // verbrauchte Energie bei Minute 0 und Minute 30 anzeigen
$slots     = array(0);        // verbrauchte Energie bei Minute 0 anzeigen

$latitude  = 51.529086;       // benötigt für die Berechnung von Sonnenaufgang und Sonnenuntergang
$longitude = 6.9446888;

$pubcurfile  = "./image.png";	// "" keine Datei, "./image.png" gleiches Verzeichnis, "/var/www/html/image.png" Webserver (write permission 0x777)
```

Das "$pubcurfile" enthält nur den aktuell gemessenen Wert und kann als Statusanzeige verwendet werden.
Im Ordner ".\pictures" wird pro Tag jeweils eine Grafik erzeugt und eine CSV-Datei mit den gemessenen Werten.
Die Parameter können auch per Kommandozeile übergeben werden und überschreiben dann die in der config.php vorhandenen Werte.

Wenn das Bild als Telegram Bot versendet werden soll, die folgenden zwei Zeilen ausfüllen:

```
$bottoken  = "";              // Telegram API Token einfügen, wenn die Statistik einmal am Tag versendet werden soll
$chatid    = "";              // Telegram Chat ID (beginnt immer mit einem "-")
```

## Bemerkungen

Wer größere Verbrucher, wie z.B. eine Waschmaschine, auswerten möchte, kann z.B. folgende Konfiguration verwenden:

```
$duration  = 10;
$withtime  = 0;
$withwh    = 1;
$sensor    = "123456789012";
$prefix    = "wm";
$maxwatt   = 2500;
$scale     = 0.25;
$scalewh   = 1;
$slots     = array(0, 30);    // verbrauchte Energie bei Minute 0 und Minute 30 anzeigen
```

Wer schnell die erzeugten Grafiken herunterladen möchte, kann dieses z.B. wie folgt (Beispiel: Monat April 2023)

> set remote="ftp://pihole/pictures/solar-2023-04-[01-31].png"
> 
> curl --user "pihole_user":"pihole_password" --insecure %remote% -O --output-dir .\pictures\
