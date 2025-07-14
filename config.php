<?php
// ------------------------------------------------------------------------------------------
// Title          fritz!dect read
// Author         (c) 2022-2023 Stephan Slabihoud
// License        This is free software and you may redistribute it under the GPL.
//                This software comes with absolutely no warranty. 
//                Use at your own risk. For details, see the license at
//                http://www.gnu.org/licenses/gpl.txt
// ------------------------------------------------------------------------------------------

// ------------------------------------------------------------------------------------------
// Login für Fritz!Box
// ------------------------------------------------------------------------------------------

$host	   = '192.168.1.1';
$user	   = 'admin';
$password  = 'enter password here';

// ------------------------------------------------------------------------------------------
// Konfiguration
// ------------------------------------------------------------------------------------------

$duration  = 10;				// max. Laufzeit des Skripts in Stunden
$withtime  = 0;					// jeweils nach 15 Minuten die Uhrzeit ausgeben
$withwh    = 1;					// jeweils nach $slots Minuten die verbrauchte Energie ausgeben
$sensor    = "123456789012";	// auszulesender Sensor (Fritz!DECT 210)
$prefix    = "";				// Prefix für Dateiname, wenn nicht angegeben wird die Sensor-ID verwendet
$maxwatt   = 640;				// Maximal zu erwartende Leistung (1 Watt = [1 x Skalierung] Pixel)
$scale     = 1.0;				// Skalierung der y-Achse
$scalewh   = 1;					// Messung in Wh (1) oder kWh (1000)
// $slots  = array(0, 30);		// verbrauchte Energie bei Minute 0 und Minute 30 anzeigen
$slots     = array(0);			// verbrauchte Energie bei Minute 0 anzeigen
$docsv     = 0;                 // CSV-Dateien erzeugen?

$latitude  = 51;
$longitude = 6;

$pubcurfile  = "./image.png";	// "" no file, "./image.png" same directory, "/var/www/html/image.png" webserver directory (set write permission to 0x777)

$bottoken  = "";                // insert Telegram API token when daily statistics should be sent
$chatid    = "";                // insert Telegram Chat ID (always starts with a "-")
