<?php
// ------------------------------------------------------------------------------------------
// Title          fritz!dect read
// Author         (c) 2022-2025 Stephan Slabihoud
// License        This is free software and you may redistribute it under the GPL.
//                This software comes with absolutely no warranty. 
//                Use at your own risk. For details, see the license at
//                http://www.gnu.org/licenses/gpl.txt
// ------------------------------------------------------------------------------------------

date_default_timezone_set("Europe/Berlin");
include_once "./config.php";

// ------------------------------------------------------------------------------------------
// Kommandozeile auswerten
// ------------------------------------------------------------------------------------------

foreach ($argv as $arg) {
    $e = explode( "=", $arg );
	if ( $e[0]=="duration" ) {
		if ( count($e)==2 )
			$duration = (int)$e[1];
	}
	if ( $e[0]=="withtime" ) {
		if ( count($e)==2 )
			$withtime = (int)$e[1];
	}
	if ( $e[0]=="withwh" ) {
		if ( count($e)==2 )
			$withwh = (int)$e[1];
	}
	if ( $e[0]=="maxwatt" ) {
		if ( count($e)==2 )
			$maxwatt = (int)$e[1];
	}
	if ( $e[0]=="scale" ) {
		if ( count($e)==2 )
			$scale = (float)$e[1];
	}
	if ( $e[0]=="sensor" ) {
		if ( count($e)==2 )
			$sensor = $e[1];
	}
	if ( $e[0]=="prefix" ) {
		if ( count($e)==2 )
			$prefix = $e[1];
	}
}

if (!file_exists("./pictures"))
	mkdir("./pictures");

if ($prefix) {
	$file    = "./pictures/" . $prefix . "-" . date("Y-m-d", time());
	$filecsv = "./pictures/" . $prefix;
} else {
	$file    = "./pictures/" . $sensor . "-" . date("Y-m-d", time());
	$filecsv = "./pictures/" . $sensor;
}

$ain = $sensor;

// ------------------------------------------------------------------------------------------
// Ausgabe der Konfiguration
// ------------------------------------------------------------------------------------------

echo "Configuration\n";
echo "Running : ".$duration." hour(s)   [duration]\n";
echo "Add time: ".$withtime."   [withtime]\n";
echo "Add Wh  : ".$withwh."   [withwh]\n";
echo "Max Watt: ".$maxwatt."   [maxwatt]\n";
echo "Scale   : ".$scale."   [scale]\n";
echo "Sensor   : ".$sensor."   [sensor]\n";
echo "Prefix  : ".$prefix."   [prefix]\n";
echo "File    : ".$file.".png\n";
echo "\n";

$sun_info = date_sun_info(time(), $latitude, $longitude);
foreach ($sun_info as $key => $val) {
    echo "$key: " . date("H:i:s", $val) . "\n";
}
$sunrise = $sun_info["sunrise"];
$sunset  = $sun_info["sunset"];
echo "Sunrise : ".$sunrise."\n";
echo "Time    : ".time()."\n";
echo "Sunset  : ".$sunset."\n";
echo "\n";

// ------------------------------------------------------------------------------------------
// SID für Zugriff auf Fritz!Box ermitteln
// ------------------------------------------------------------------------------------------

$sid = get_sid($host, $user, $password);

// ------------------------------------------------------------------------------------------
// Zeichenfläche
// ------------------------------------------------------------------------------------------

$borderx = 16;					// horizontaler Rahmen
$bordery = 16;					// vertikaler Rahmen
$width = 24*60;					// Breite = 24 Stunden * 60 Minuten
$height = $maxwatt * $scale;	// Höhe = maximale Leistung x Skalierung

$alignx = (int) ((60 / count($slots)) / 2);		// Text mittig ausrichten
$xold = 0;
$yold = 0;
$resolution = 20;				// zeitliche Auflösung in Sekunden (10, 15, 20 Sekunden)
$rescounter = (int)(59 / $resolution);

// ------------------------------------------------------------------------------------------
// Datei erzeigen, wenn noch nicht vorhanden, sonst vorhandene Datei laden
// ------------------------------------------------------------------------------------------

if (file_exists($file.".png")) {
	$im = imagecreatefrompng($file.".png");
} else {
	$im = imagecreatetruecolor($width + 2 * $borderx, $height + 2 * $bordery) or die("Cannot Initialize new GD image stream");
}

// ------------------------------------------------------------------------------------------
// Farben
// ------------------------------------------------------------------------------------------

$white = ImageColorAllocate($im, 255, 255, 255);
$black = ImageColorAllocate($im, 0, 0, 0);
$red   = ImageColorAllocate($im, 255, 0, 0);
$gray  = ImageColorAllocate($im, 128, 128, 128);
$back  = ImageColorAllocate($im, 240, 240, 240);
$style = array($gray, $gray, $gray, $gray, $gray, $gray, IMG_COLOR_TRANSPARENT, IMG_COLOR_TRANSPARENT, IMG_COLOR_TRANSPARENT, IMG_COLOR_TRANSPARENT, IMG_COLOR_TRANSPARENT, IMG_COLOR_TRANSPARENT);

// ------------------------------------------------------------------------------------------
// Hintergrund füllen und Zeichenbereich vorbereiten, wenn Datei neu angelegt
// ------------------------------------------------------------------------------------------

if (!file_exists($file.".png")) {
	ImageFill($im, 0, 0, $white);
	imagefilledrectangle($im, xpos(0), ypos(0), xpos($width-1), ypos($height-1), $back);

	// draw drawing area border
	ImageLine($im, $borderx-1, $bordery-1, $borderx + $width, $bordery-1, $black);
	ImageLine($im, $borderx-1, $height + $bordery, $borderx + $width, $height + $bordery, $black);
	ImageLine($im, $borderx-1, $bordery-1, $borderx-1, $height + $bordery, $black);
	ImageLine($im, $borderx + $width, $bordery-1, $borderx + $width, $height + $bordery, $black);

	// draw x and y scales
	imagesetstyle($im, $style);
	for ($i = 0; $i < $height; $i += 100 * $scale) {
		$y = $i;
		if ($i)
			ImageLine($im, xpos(0), ypos($y), xpos($width-1), ypos($y), IMG_COLOR_STYLED);
		ImageLine($im, $borderx-1, ypos($y), $borderx - 8, ypos($y), $black);
		ImageStringUp($im, 1, 0, ypos($y), $i / $scale, $black);
	}

	for ($i = 0; $i < 24; $i += 1) {
		if ($i)
			ImageLine($im, xpos($i*60), ypos(0), xpos($i*60), ypos($height-1), IMG_COLOR_STYLED);
		ImageLine($im, xpos($i*60), $bordery+$height , xpos($i*60), $bordery+$height+8, $black);
		$tim = sprintf("%02d", $i);
		ImageString($im, 1, xpos($i*60), $bordery+$height+8, $tim, $black);
	}
}

$srise_s = date("H:i", $sun_info["sunrise"]);
$sset_s  = date("H:i", $sun_info["sunset"]);
$srise   = "Sonnenaufgang:   " . date("H:i", $sun_info["sunrise"]);
$sset    = "Sonnenuntergang: " . date("H:i", $sun_info["sunset"]);
$w = imagefontwidth(2) * (strlen($srise));
$h = imagefontheight(2);
ImageFilledRectangle($im, xpos(8), ypos($height-8), xpos(8+$w), ypos($height - 8 - 2*$h), $white);
ImageString($im, 2, xpos(8), ypos($height-8), $srise, $black);
ImageString($im, 2, xpos(8), ypos($height-8-$h), $sset, $black);

// ------------------------------------------------------------------------------------------
// Programmschleife über $duration * 60 Minuten
// ------------------------------------------------------------------------------------------

$xmlstring       = getswitchcmd($host, "getswitchenergy", $ain, $sid);
$energyold       = (int)(intval($xmlstring)/$scalewh);		// ggf. Skalierung in kWh
$energystart     = intval($xmlstring);
$energystarttime = "";

// $duration = 0;	// TEST

for ($min = 0; $min < $duration*60; $min++) {

	// --------------------------------------------------------------------------------------
	// Ermittelt die aktuelle Uhrzeit (bzw. Minute)
	// Sieht umständlich aus (was es auch ist), damit läuft das Skript aber auch unter
	// PHP 7.4 (aktuell bei pihole verwendet) und PHP 8.x (-> "null" Parameter bei mktime)
	// --------------------------------------------------------------------------------------

	$now = time();

	$hour   = (int)date('H',$now);
	$minute = (int)date('i',$now);
	$second = (int)date('s',$now);

	$day    = (int)date('d',$now);
	$month  = (int)date('m',$now);
	$year   = (int)date('Y',$now);


	// $todayminute = (int) (($now - mktime(0,0,0,null,null,null))/60);		// für PHP 8.x reicht das aus
	$todayminute = (int) (($now - mktime(0,0,0,$month,$day,$year))/60);

	echo date("H:i:s Y-m-d", $now)."\n";
	echo "Minute: ".$todayminute."\n";

	// --------------------------------------------------------------------------------------
	// Jetzt 3x alle 15 Sekunden die Leistung anfragen und den Maximalwert bestimmen
	// --------------------------------------------------------------------------------------

	$max = 0;
	$sum = 0;
	for ($i = 0; $i < $rescounter; $i++) {
		echo ".";
		$xmlstring = getswitchcmd($host, "getswitchpower", $ain, $sid);
		$leistung = (int)(intval($xmlstring)/1000);
		
		$sum = $sum + $leistung;
		if ($leistung>$max)
			$max = $leistung;
		sleep($resolution);
	}

	$max = (int)$max;
	$avg = (int)($sum / $rescounter);
	echo " [".$max."/".$avg."] Watt\n";

	$x = $todayminute;
	$y = (int)($max * $scale);

	if ( !$energystarttime && $max>0)
		$energystarttime = date("H:i", $now);

	// --------------------------------------------------------------------------------------
	// Ausgabe der Uhrzeit, wenn gewünscht
	// --------------------------------------------------------------------------------------

	if ( $withtime && !($minute % 15) ) {
		$tim = date("H:i", $now);
		ImageStringUp($im, 1, xpos($x-4), ypos($y+16), $tim, $black);
	}
	
	// --------------------------------------------------------------------------------------
	// Ausgabe der Energie, wenn gewünscht (die Auflösung ist 1 Minute, also bestenfalls
	// eine Schätzung, kein genauer Wert)
	// --------------------------------------------------------------------------------------

	if ($withwh && in_array($minute, $slots)) {

		$xmlstring = getswitchcmd($host, "getswitchenergy", $ain, $sid);
		$energy = (int)(intval($xmlstring)/$scalewh);		// ggf. Skalierung in kWh

		$wh = (int)($energy - $energyold);
		$energyold = $energy;

		$w = imagefontwidth(2) * (strlen($wh)+3);
		$h = imagefontheight(2);

		$ytext = ypos($height-16 - 8*($hour % 2) - ($minute>>1));
		ImageLine($im, xpos($x - $alignx*2), $ytext, xpos($x), $ytext, $gray);
		ImageString($im, 2, xpos($x - $alignx - ($w>>1)), $ytext, $wh . " Wh", $black);
	}

	// --------------------------------------------------------------------------------------
	// Wert zeichnen
	// --------------------------------------------------------------------------------------
	
	if ($xold>0)
		ImageLine($im, xpos($xold), ypos($yold), xpos($x), ypos($y), $black);

	ImageSetPixel($im, xpos($x)  , ypos($y)  , $red);
	ImageSetPixel($im, xpos($x-1), ypos($y-1), $red);
	ImageSetPixel($im, xpos($x-1), ypos($y+1), $red);
	ImageSetPixel($im, xpos($x+1), ypos($y-1), $red);
	ImageSetPixel($im, xpos($x+1), ypos($y+1), $red);

	$xold = $x;
	$yold = $y;

	// --------------------------------------------------------------------------------------
	// Bild speichern
	// --------------------------------------------------------------------------------------

	ImagePNG($im, $file.".png");

	if ($docsv) {
		$fout = fopen($file.".csv", "a");
		if ($fout>=0) {	
			$line = date("d.m.Y", $now) . ";" . date("H:i", $now) . ";" . $max . "\n";
			fputs($fout, $line);
			fclose($fout);
		}
	}

	if ($pubcurfile) {
		$texts = "Aktuell: " . $max . " Wh";
		$ws = imagefontwidth(5) * (strlen($texts));
		$hs = imagefontheight(5);
		$ims = imagecreatetruecolor($ws, $hs) or die("Cannot Initialize new GD image stream");
		ImageFill($ims, 0, 0, $white);
		ImageString($ims, 5, 0, 0, $texts, $black);
		ImagePNG($ims, $pubcurfile);
	}

	// --------------------------------------------------------------------------------------
	// Warten bis zur nächsten Abfrage
	// --------------------------------------------------------------------------------------

	// $now60 = mktime($hour,null,0,null,null,null)+60;		// für PHP 8.x reicht das aus
	$now60 = mktime($hour,$minute,0,$month,$day,$year)+60;
	echo "Sleep until ";
	echo date("H:i:s Y-m-d", $now60)."\n";

	while (time() < $now60)
	   time_sleep_until($now60);

	if (time() > $sunset+1800)	// forced exit 30 minutes after sunset
		break;
}

// ------------------------------------------------------------------------------------------
// Gesamtenergie (nur wenn Skript komplett durchläuft)
// ------------------------------------------------------------------------------------------

$xmlstring = getswitchcmd($host, "getswitchenergy", $ain, $sid);
$energyend = intval($xmlstring);
$energyday = "Gesamt: " . ($energyend - $energystart) . " Wh [" . $energystarttime . "-" . date("H:i", time()) . "]";

$w = imagefontwidth(2) * (strlen($energyday));
$h = imagefontheight(2);
ImageFilledRectangle($im, xpos(8), ypos(8), xpos(8+$w), ypos(8+$h), $white);
ImageString($im, 2, xpos(8), ypos(8+$h), $energyday, $black);

// ------------------------------------------------------------------------------------------
// Gesamtenergie im letzten Jahr (nur wenn Skript komplett durchläuft)
// ------------------------------------------------------------------------------------------

$xmlstring = getswitchcmd($host, "getbasicdevicestats", $ain, $sid);
$xml = simplexml_load_string($xmlstring);
$energy = $xml->energy;
foreach($energy->stats as $stat){
	$att = $stat->attributes();
	
	$valuecnt = $att->count;
	$values   = explode(",", $stat[0]);
	$valuesum = round(array_sum($values)/1000);

	// echo "Werte: $valuecnt\n\r";      // 31=letzten 31 Tage
	// echo "Summe: $valuesum kWh\n\r";  // 12=letzten 12 Monate
	if ($valuecnt == 12) {
		$energyyear = "" . $valuesum . " kWh [Jahr]";

		$w = imagefontwidth(2) * (strlen($energyyear));
		$h = imagefontheight(2);
		ImageFilledRectangle($im, xpos(8), ypos(8 + 2*$h), xpos(8+$w), ypos(8+$h + 2*$h), $white);
		ImageString($im, 2, xpos(8), ypos(8+$h + 2*$h), $energyyear, $black);
	}
}

// ------------------------------------------------------------------------------------------
// Bild speichern
// ------------------------------------------------------------------------------------------

ImagePNG($im, $file.".png");
ImageDestroy($im);

if ($docsv) {
	$fout = fopen($filecsv.".csv", "a");
	if ($fout>=0) {	
		$line = date("d.m.Y", $now) . ";" . ($energyend - $energystart) . ";" . $srise_s . ";" . $sset_s . "\n";
		fputs($fout, $line);
		fclose($fout);
	}
}
if ($bottoken) {
	$now = time();
	$text = "PV-Info: " . date("d.m.Y", $now) . " - " . ($energyend - $energystart) . " Wh [" . $srise_s . "-" . $sset_s . "]";
//	$ret = telegramsendtext($bottoken, $chatid, $text);
	$ret = telegramsendimage($bottoken, $chatid, $text, $file.".png");
}
exit;

// ------------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------

function xpos($x) {
	global $borderx, $width;
	return $x + $borderx;
}

function ypos($y) {
	global $bordery, $height;
	return -$y + $bordery + $height;
}

function get_sid($host, $user, $password) {
	$loginurl	= "https://" . $host . "/login_sid.lua";
	$context = stream_context_create(
		array (
			'http' => array (
				'method' => 'GET',
				'header'=> "Content-type: application/x-www-form-urlencoded\r\n"
			),
			'ssl'=>array(
				"verify_peer"=>false,
				"verify_peer_name"=>false,
			),
		)
	);

	$http_response = file_get_contents($loginurl, false, $context);				// get challenge
	$xml = simplexml_load_string($http_response);
	$challenge=(string)$xml->Challenge;
	$sid=(string)$xml->SID;														// check SID

	if ((strlen($sid)>0) && (preg_match("/^[0]+$/",$sid)) && $challenge) {		// SID is "0000000000000000" and we have a 32-bit challenge
		$sid = "";
		$pass = $challenge . "-" . $password;									// build password response
		$pass=mb_convert_encoding($pass, "UTF-16LE");							// UTF-16LE encoding as required
		$md5 = md5($pass);														// md5 hash
		$challenge_response = $challenge."-".$md5;								// final response

		$url = $loginurl . "?response=" . $challenge_response . "&username=" . $user;
		$http_response = file_get_contents($url, false, $context);				// authenticate
		$xml = simplexml_load_string($http_response);
		$sid=(string)$xml->SID;

		if ((strlen($sid)>0) && !preg_match("/^[0]+$/",$sid))
			return $sid;														// response is SID
	} else { 
		if ((strlen($sid)>0) && (preg_match("/^[0-9a-f]+$/",$sid)))
			return $sid;														// use existing SID if $sid matches an hex string
	} 
	return null;
}

function getswitchcmd($host, $cmd, $ain, $sid) {
	$url = "https://" . $host . "/webservices/homeautoswitch.lua";
	$context = stream_context_create(
		array (
			'http' => array (
				'method' => 'GET',
				'header'=> "Content-type: application/x-www-form-urlencoded\r\n"
			),
			'ssl'=>array(
				"verify_peer"=>false,
				"verify_peer_name"=>false,
			),
		)
	);
	
	$ret = rtrim(file_get_contents($url . '?' .
									'ain=' . $ain .
									'&sid=' . $sid .
									'&switchcmd=' . $cmd, false, $context));
	return $ret;
}

function telegramsendtext($bottoken, $chatid, $text) {
	$url = "https://api.telegram.org/bot" . $bottoken . "/sendMessage?chat_id=" . $chatid . "&text=" . $text;
	$ret = rtrim(file_get_contents($url, false, null));
	return $ret;
}

function telegramsendimage($bottoken, $chatid, $text, $image) {
	$url = "https://api.telegram.org/bot" . $bottoken . "/sendPhoto";

	$data = [
		'chat_id' => $chatid,
		'photo' => new CURLFile(realpath($image)),
		'caption' => $text
	];

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , false);

	$response = curl_exec($ch);

	if ($response === false) {
		echo 'cURL Fehler: ' . curl_error($ch);
	} else {
		$responseData = json_decode($response, true);
		if ($responseData['ok']) {
			echo 'Bild erfolgreich gesendet!';
		} else {
			echo 'Fehler beim Senden: ' . $responseData['description'];
		}
	}

	curl_close($ch);
	return $response;
}
