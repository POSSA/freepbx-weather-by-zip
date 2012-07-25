#!/usr/bin/php -q 
<?php
 ob_implicit_flush(false); 
 error_reporting(0); 
 set_time_limit(300); 
 $ttsengine[0] = "flite" ;
 $ttsengine[1] = "swift" ;

//   Nerd Vittles ZIP Weather ver. 4.1, (c) Copyright Ward Mundy, 2007-2012. All rights reserved.
//   #module  All areas changed from the original Nerd Vittles script are marked with #module
//-------- DON'T CHANGE ANYTHING ABOVE THIS LINE ----------------

 $debug = 1; 
 $newlogeachdebug = 1;
 $emaildebuglog = 0;
 $email = "yourname@yourdomain" ;
 $ttspick=0 ;
//-------- DON'T CHANGE ANYTHING BELOW THIS LINE ----------------

// #module - START CODE ADDED TO NV SCRIPT FOR FREEPBX MODULE COMPATIBILITY ---

require_once 'DB.php';

define("AMP_CONF", "/etc/amportal.conf");

$amp_conf = parse_amportal_conf(AMP_CONF);
if (count($amp_conf) == 0) {
	fatal("FAILED");
}

function parse_amportal_conf($filename) {
	$file = file($filename);
	foreach ($file as $line) {
		if (preg_match("/^\s*([a-zA-Z0-9_]+)\s*=\s*(.*)\s*([;#].*)?/",$line,$matches)) { 
			$conf[ $matches[1] ] = $matches[2];
		}
	}
	return $conf;
}

$dsn = array(
    'phptype'  => 'mysql',
    'username' => $amp_conf['AMPDBUSER'],
    'password' => $amp_conf['AMPDBPASS'],
    'hostspec' => $amp_conf['AMPDBHOST'],
    'database' => $amp_conf['AMPENGINE'],
);
$options = array();
$db =& DB::connect($dsn, $options);
if (PEAR::isError($db)) {
    die($db->getMessage());
}

$res =&  $db->query("SELECT * FROM `weatheroptions` LIMIT 1");
$conf =& $res->fetchRow();

$engine = $conf[0];
$defaultzip = $conf[1];

//echo $engine;


if ($engine == "swift"){
	$ttspick = "1";
}
if ($engine == "flite") {
	$ttspick = "0";
}

//#module - END CODE ADDED TO NV SCRIPT FOR FREEPBX MODULE COMPATIBILITY ---



$log = "/var/log/asterisk/nv-weather-zip.txt" ;
if ($debug and $newlogeachdebug) :
 if (file_exists($log)) :
  unlink($log) ;
 endif ;
endif ;

 $stdlog = fopen($log, 'a'); 
 $stdin = fopen('php://stdin', 'r'); 
 $stdout = fopen( 'php://stdout', 'w' ); 

if ($debug) :
  fputs($stdlog, "Nerd Vittles ZIP Weather ver 4.1 (c) Copyright 2007-2012, Ward Mundy. All Rights Reserved.\n\n" . date("F j, Y - H:i:s") . "  *** New session ***\n\n" ); 
endif ;

function read() {  
 global $stdin;  
 $input = str_replace("\n", "", fgets($stdin, 4096));  
 dlog("read: $input\n");  
 return $input;  
}  

function write($line) {  
 dlog("write: $line\n");  
 echo $line."\n";  
}  

function dlog($line) { 
 global $debug, $stdlog; 
 if ($debug) fputs($stdlog, $line); 
} 

function execute_agi( $command ) 
{ 
GLOBAL $stdin, $stdout, $stdlog, $debug; 
 
fputs( $stdout, $command . "\n" ); 
fflush( $stdout ); 
if ($debug) 
fputs( $stdlog, $command . "\n" ); 
 
$resp = fgets( $stdin, 4096 ); 
 
if ($debug) 
fputs( $stdlog, $resp ); 
 
if ( preg_match("/^([0-9]{1,3}) (.*)/", $resp, $matches) )  
{ 
if (preg_match('/result=([-0-9a-zA-Z]*)(.*)/', $matches[2], $match))  
{ 
$arr['code'] = $matches[1]; 
$arr['result'] = $match[1]; 
if (isset($match[3]) && $match[3]) 
$arr['data'] = $match[3]; 
return $arr; 
}  
else  
{ 
if ($debug) 
fputs( $stdlog, "Couldn't figure out returned string, Returning code=$matches[1] result=0\n" );  
$arr['code'] = $matches[1]; 
$arr['result'] = 0; 
return $arr; 
} 
}  
else  
{ 
if ($debug) 
fputs( $stdlog, "Could not process string, Returning -1\n" ); 
$arr['code'] = -1; 
$arr['result'] = -1; 
return $arr; 
} 
}  

// ------ Code execution begins here
// parse agi headers into array  
//while ($env=read()) {  
// $s = split(": ",$env);  
// $agi[str_replace("agi_","",$s0)] = trim($s1); 
// if (($env == "") || ($env == "\n")) {  
//   break;  
// }  
//}  

while ( !feof($stdin) )  
{ 
$temp = fgets( $stdin ); 
 
if ($debug) 
fputs( $stdlog, $temp ); 
 
// Strip off any new-line characters 
$temp = str_replace( "\n", "", $temp ); 
 
$s = explode( ":", $temp ); 
$agivar[$s[0]] = trim( $s[1] ); 
if ( ( $temp == "") || ($temp == "\n") ) 
{ 
break; 
} 
}  

$dialcode = $_SERVER["argv"][1];

if ($debug) :
fputs($stdlog, "ZIP Code: " . $dialcode . "\n" );
endif ;

$tts = $ttsengine[$ttspick] ;


$token = md5 (uniqid (""));
$tmptext = "/tmp/tts-$token.txt" ;
$tmpwave = "/var/lib/asterisk/sounds/tts/tts-$token.wav" ;


//$fd = fopen("http://pdaweather.org/text.php?city=$dialcode", "r"); 
//if (!$fd) {
// echo "<p>Unable to open web connection. \n"; 
// exit; 
//} 

$value = "";
//while(!feof($fd)){
//	$value .= fread($fd, 4096);	
//}
//fclose($fd);

//--------------

//  #module - Following line has been changed from the original NV script
$link = mysql_connect("localhost", $dsn['username'], $dsn['password'])
    or die("Data base connection failed");
//  #module - Following line has been changed from the original NV script
mysql_select_db("asterisk")
    or die("data base open failed");

//$airport = "DEN" ;

//$code=$_REQUEST['code'];
//$dialcode=$_REQUEST['dialcode'];
$code = "" ;

 $zip = substr($dialcode,0,5) ;
 $query = "SELECT *  FROM `zipcodes` WHERE `zip` = " . chr(34) . $zip . chr(34);

if ($debug) :
fputs($stdlog, "\nQuery: " . $query . "\n\n" );
endif ;


//echo $query ;

$result = mysql_query($query)
    or die("Web site query failed");

$reccount = mysql_num_rows($result) ;

if ($debug) :
fputs($stdlog, "\nMatching Records: " . $reccount . "\n\n" );
endif ;



//echo $reccount ;

while ($row = mysql_fetch_array($result)) {
//  $latitude =  $row["la_g"] + ($row["la_p"]+$row["la_s"]/60)/60 ;
//  $longitude = -1*($row["lo_g"] + ($row["lo_p"]+$row["lo_s"]/60)/60) ;
  $city  = $row["city"] . ", " . $row["fullstate"] ;
  $city2 = $row["city"] . ", " . $row["state"] ;
  $latitude = $row["latitude"] ;
  $longitude = $row["longitude"] ;
}

if ($debug) :
fputs($stdlog, "\nCity: " . $city . "\n\n" );
endif ;

mysql_close($link);

if (strlen($city)<1) :
 $value= "I'm sorry. No weather information is available for the zip code you have selected. Have a nice day. Good bye." ;
 $fd = fopen($tmptext, "w");
 if (!$fd) {
  echo "<p>Unable to open temporary text file in /tmp for writing. \n";
  exit;
 }
 $retcode = fwrite($fd,$value);
 fclose($fd);
 $retcode2 = system ("$tts -f  $tmptext -o $tmpwave") ;
 unlink ("$tmptext") ;
 $tmpwave = "tts/tts-$token" ;
 execute_agi("SET VARIABLE TMPWAVE $tmpwave");
 $txt = "mime-construct --file $log --to $email" ;
 if ($debug) :
  fputs($stdlog, "\nSend to Email: " . $txt . "\n\n" );
 endif ;
 fclose($stdin);
 fclose($stdout);
 fclose($stdlog);
 if ($emaildebuglog) :
// system("mime-construct --to $email --subject " . chr(34) . "Nerd Vittles Weather ver. 4.1 Session Log" . chr(34) . " --attachment $log --type text/plain --file $log") ;
  system($txt) ;
 endif ;
 exit ;
endif ;

echo $city . ": LAT " ;
echo $latitude ;
echo "  LONG: " ;
echo $longitude ;

//$query = "http://www.srh.noaa.gov/ifps/MapClick.php?FcstType=text&textField1=$latitude&textField2=$longitude&site=ffc&Radius=0&CiTemplate=0&TextType=1" ;

//$query = "http://www.srh.noaa.gov/port/port_zc.php?inputstring=$zip" ;

//$query ="http://mobile.weather.gov/index.php?lat=$latitude&lon=$longitude#text_forecast" ;

$query = "http://forecast.weather.gov/zipcity.php?inputstring=$zip";

$fd = fopen($query, "r");
if (!$fd) {
 echo "<p>Unable to open web connection. \n";
 exit;
}
$value = "";
while(!feof($fd)){
        $value .= fread($fd, 4096);
}
fclose($fd);

if ($debug) :
  fputs($stdlog, "\nQuery Results: " . $value . "\n\n" );
endif ;


$thetext="point-forecast-area-title";
$start= strpos($value, $thetext);
//$newvalue="This National Weather Service update provided for " . substr($newvalue, $start+26);
$value = substr($value,$start);
$thetext = "</div>" ;
$start= strpos($value, $thetext);
$cityupdate="This National Weather Service update for ". $city . " brought to you by Nerd Vittles. ";
$newvalue=$cityupdate."Current local conditions " . substr($value,27, $start-27). ": ";
$newvalue = str_replace( " E ", " East of ", $newvalue );
$newvalue = str_replace( " ENE ", " East North East of ", $newvalue );
$newvalue = str_replace( " NE ", " North East of ", $newvalue );
$newvalue = str_replace( " W ", " West of ", $newvalue );
$newvalue = str_replace( " NW ", " North West of ", $newvalue );
$newvalue = str_replace( " WNW ", " West North West of ", $newvalue );
$newvalue = str_replace( " N ", " North of ", $newvalue );
$newvalue = str_replace( " S ", " South of ", $newvalue );
$newvalue = str_replace( " SE ", " South East of ", $newvalue );
$newvalue = str_replace( " SSE ", " South South East of ", $newvalue );
$newvalue = str_replace( " SW ", " South West of ", $newvalue );
$newvalue = str_replace( " SSW ", " South South West of ", $newvalue );
$newvalue = str_replace(",","",$newvalue);
$finish=$start+6;
$value=substr($value,$finish);

$thetext="current-conditions";
$start= strpos($value, $thetext);
$value = substr($value,$start);
$thetext="myforecast-current";
$start= strpos($value, $thetext);
$value = substr($value,$start+20);
$thetext="</p>";
$start= strpos($value, $thetext);
$conditions=substr($value,0,$start).". ";
$newvalue.=$conditions;

$thetext="myforecast-current-lrg";
$start= strpos($value, $thetext);
$value = substr($value,$start+24);
$thetext="</p>";
$start= strpos($value, $thetext);
$ftemp=substr($value,0,$start).". ";
$ftemp = str_replace( "&deg;F", " degrees Fahrenheit", $ftemp );
$newvalue.=$ftemp;

$thetext="myforecast-current-sm";
$start= strpos($value, $thetext);
$value = substr($value,$start+23);
$thetext="</span>";
$start= strpos($value, $thetext);
$ctemp=substr($value,0,$start).". ";
$ctemp = str_replace( "&deg;C", " degrees Centigrade", $ctemp );
$newvalue.=$ctemp;


$thetext="current-conditions-detail";
$start= strpos($value, $thetext);
$value = substr($value,$start+28);
$thetext="Humidity</span>";
$start= strpos($value, $thetext);
$value = substr($value,$start+15);
$thetext="</li>";
$start= strpos($value, $thetext);
$humidity="Humidity: ".substr($value,0,$start).". ";
$humidity = str_replace( "%", " per cent", $humidity );
$newvalue.=$humidity;

$thetext="Wind Speed</span>";
$start= strpos($value, $thetext);
$value = substr($value,$start+17);
$thetext="</li>";
$start= strpos($value, $thetext);
$windspeed="Wend Speed: ".substr($value,0,$start).". ";
$windspeed = str_replace( "mph","miles per hour", $windspeed );
$windspeed = str_replace( "Vrbl", "Variable at ", $windspeed );
$windspeed = str_replace( " E ", " From the East at ", $windspeed );
$windspeed = str_replace( " NE ", " From the North East at ", $windspeed );
$windspeed = str_replace( " W ", " From the West at ", $windspeed );
$windspeed = str_replace( " NW ", " From the North West at ", $windspeed );
$windspeed = str_replace( " N ", " From the North at ", $windspeed );
$windspeed = str_replace( " S ", " From the South at ", $windspeed );
$windspeed = str_replace( " SE ", " From the South East at ", $windspeed );
$windspeed = str_replace( " SW ", " From the South West at ", $windspeed );
$windspeed = str_replace( " G ", " gusting to ", $windspeed );
$windspeed = str_replace( "NA", " currently unavailable ", $windspeed );
$windspeed = str_replace( "NULL", " currently unavailable ", $windspeed );
$newvalue.=$windspeed;

$thetext="Barometer</span>";
$start= strpos($value, $thetext);
$value = substr($value,$start+16);
$thetext="</li>";
$start= strpos($value, $thetext);
$barometer="Barometric Pressure: ".substr($value,0,$start).". ";
$barometer = str_replace( " in ", " inches. ", $barometer);
$barometer = str_replace( " in", " inches", $barometer);
$barometer = str_replace( "mb)", "millibars", $barometer);
$barometer = str_replace( "(", " ", $barometer);
$barometer = str_replace( ".00", "", $barometer );
$newvalue.=$barometer;

$thetext="Visibility</span>";
$start= strpos($value, $thetext);
$value = substr($value,$start+17);
$thetext="</li>";
$start= strpos($value, $thetext);
$visibility="Vizibility: ".substr($value,0,$start).". ";
$visibility = str_replace( " mi", " miles" , $visibility);
$visibility = str_replace( ".00", "", $visibility );
$newvalue.=$visibility;
$newvalue.="Here's the 5 day forecast: ";


$thetext="7-DAY FORECAST";
$start= strpos($value, $thetext);
$value = substr($value,$start+15);

for ( $counter=1; $counter<= 9; $counter += 1) {
$thetext="label".chr(34).">";
$start= strpos($value, $thetext);
$value = substr($value,$start+7);
$thetext="</span>";
$start= strpos($value, $thetext);
$forecast=substr($value,0,$start).": ";
$value = substr($value,$start+7);
$thetext="</li>";
$start= strpos($value, $thetext);
$forecast.=substr($value,0,$start)." ";
$forecast = str_replace( ",", " ", $forecast );
$forecast = str_replace( "pm", " p.m", $forecast);
$forecast = str_replace( "am", " a.m", $forecast);
$forecast = str_replace( "%", " per cent", $forecast);
$forecast = str_replace( "wind", "wends", $forecast );
$forecast = str_replace( "mph", "miles per hour", $forecast );
$newvalue.=$forecast;
}


$newvalue.=" Have a nice day. Good bye.";
//======================== end of new code


// ============ DONT DELETE BELOW HERE


$fd = fopen($tmptext, "w"); 
if (!$fd) {
 echo "<p>Unable to open temporary text file in /tmp for writing. \n"; 
 exit; 
} 
$retcode = fwrite($fd,$newvalue);	
fclose($fd);

$msg = chr(34) . "Your report was successfully downloaded. Please stand bye." . chr(34) ;
execute_agi("exec $tts $msg") ;

//$retcode2 = system ("flite -f  $tmptext -o $tmpwave") ;
$retcode2 = system ("$tts -f  $tmptext -o $tmpwave") ;

unlink ("$tmptext") ;

$tmpwave = "tts/tts-$token" ;

execute_agi("SET VARIABLE TMPWAVE $tmpwave"); 

//$msg = chr(34) . "Here is your report." . chr(34) ;
//execute_agi("exec flite $msg") ;

if ($emaildebuglog) :
 system("mime-construct --to $email --subject " . chr(34) . "Nerd Vittles ZIP Weather ver. 2.1 Session Log" . chr(34) . " --attachment $log --type text/plain --file $log") ;
endif ;

// clean up file handlers etc.  
fclose($stdin);  
fclose($stdout);
fclose($stdlog);  
exit;   
  
?>
