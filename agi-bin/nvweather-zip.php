#!/usr/bin/php -q 
<?php 
 ob_implicit_flush(false); 
 error_reporting(0); 
 set_time_limit(300); 
 $ttsengine[0] = "flite" ;
 $ttsengine[1] = "swift" ;

//   Nerd Vittles ZIP Weather ver. 4.1, (c) Copyright Ward Mundy, 2007-2008. All rights reserved.

//-------- DON'T CHANGE ANYTHING ABOVE THIS LINE ----------------

 $debug = 0; 
 $newlogeachdebug = 1;
 $emaildebuglog = 0;
 $email = "user@domain" ;
 $ttspick = 0 ;
//-------- DON'T CHANGE ANYTHING BELOW THIS LINE ----------------

/// code to get ttspick from db

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



//----  end of db config code

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
  fputs($stdlog, "Nerd Vittles ZIP Weather ver. 4.1 (c) Copyright 2007-2008, Ward Mundy. All Rights Reserved.\n\n" . date("F j, Y - H:i:s") . "  *** New session ***\n\n" ); 
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


$link = mysql_connect("localhost", $dsn['username'], $dsn['password'])
    or die("Data base connection failed");
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
// system("mime-construct --to $email --subject " . chr(34) . "Nerd Vittles Weather by Zip ver. 4.1 Session Log" . chr(34) . " --attachment $log --type text/plain --file $log") ;
  system($txt) ;
 endif ;
 exit ;
endif ;

//echo $city . ": LAT " ;
//echo $latitude ;
//echo "  LONG: " ;
//echo $longitude ;

//$query = "http://www.srh.noaa.gov/ifps/MapClick.php?FcstType=text&textField1=$latitude&textField2=$longitude&site=ffc&Radius=0&CiTemplate=0&TextType=1" ;

$query = "http://www.srh.noaa.gov/port/port_zc.php?inputstring=$zip" ;

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


//$thetext = "National Weather Service" ;
//$start= strpos($value, $thetext);
//$newvalue=substr($value, $start);

//$thetext = "<div align=\"center\">" ;
$thetext = "Current Local Conditions";
$start= strpos($value, $thetext);
//$newvalue="This National Weather Service update provided for " . substr($newvalue, $start+26);
$value = substr($value,$start);
$thetext = "<br>" ;
$start= strpos($value, $thetext);
$cityupdate="This National Weather Service update provided for ". $city . ". ";
$newvalue="Current local conditions at " . substr($value, $start);

$finish= strpos($newvalue, "<hr>");  
$newvalue=substr($newvalue, 0, $finish);

// new code to delete Latitude, Longitude, and Elevation info from report
$start = strpos($newvalue,"<br>") ;
$start2 = strpos($newvalue,"<br>",$start+1) ;
$start3 = strpos($newvalue,"<br>",$start2+1) ;
$newvalue = substr($newvalue,0,$start2) . " " . substr($newvalue,$start3) ;
// new code ends here

$thetext = "Last Update:" ;
$start = strpos($newvalue, $thetext);
$thedate = substr($newvalue,$start+13,8) ;

$themo = substr($thedate,0,2);
$theda = substr($thedate,3,2);
switch ($themo) {
 case "01":
  $themo="January ";
  break;
 case "02":
  $themo="February ";
  break;
 case "03":
  $themo="March ";
  break;
 case "04":
  $themo="April ";
  break;
 case "05":
  $themo="May ";
  break;
 case "06":
  $themo="June ";
  break;
 case "07":
  $themo="July ";
  break;
 case "08":
  $themo="August ";
  break;
 case "09":
  $themo="September ";
  break;
 case "10":
  $themo="October ";
  break;
 case "11":
  $themo="November ";
  break;
 case "12":
  $themo="December ";
  break;
}

if (substr($theda,0,1)=="0") :
 $theda=substr($theda,1,1) ;
endif ;

$newdate = $themo . $theda . " at " ;

$newvalue=substr($newvalue,0,$start) . "As last reported on: " . $newdate . substr($newvalue,$start+22) ;

$newvalue = str_replace( "<br>", ". ", $newvalue ); 
$newvalue = str_replace( "at:.", "at: ", $newvalue );
$newvalue = str_replace( "/", " ", $newvalue );
$newvalue = str_replace( " am ", " a.m. ", $newvalue ); 
$newvalue = str_replace( " pm ", " p.m. ", $newvalue ); 
$newvalue = str_replace( " AM ", " A.M. ", $newvalue );
$newvalue = str_replace( " PM ", " P.M. ", $newvalue );
$newvalue = str_replace( "Special Weather Statement", " ", $newvalue ); 
$newvalue = str_replace( "AST", "Atlantic Standard Time. ", $newvalue );
$newvalue = str_replace( "ADT", "Atlantic Daylight Time. ", $newvalue );
$newvalue = str_replace( "EST", "Eastern Standard Time. ", $newvalue ); 
$newvalue = str_replace( "EDT", "Eastern Daylight Time. ", $newvalue ); 
$newvalue = str_replace( "PST", "Pacific Standard Time. ", $newvalue ); 
$newvalue = str_replace( "PDT", "Pacific Daylight Time. ", $newvalue ); 
$newvalue = str_replace( "CST", "Central Standard Time. ", $newvalue ); 
$newvalue = str_replace( "CDT", "Central Daylight Time. ", $newvalue ); 
$newvalue = str_replace( "MST", "Mountain Standard Time. ", $newvalue ); 
$newvalue = str_replace( "MDT", "Mountain Daylight Time. ", $newvalue ); 
$newvalue = str_replace( "HST", "Hawaii Standard Time. ", $newvalue ); 
$newvalue = str_replace( "HDT", "Hawaii Daylight Time. ", $newvalue ); 
$newvalue = str_replace( "&deg;F", " degrees fair in height. ", $newvalue ); 
$newvalue = str_replace( "&deg;C", " degrees centigrade. ", $newvalue ); 
$newvalue = str_replace( "mb)", " millibars. ) ", $newvalue ); 
$newvalue = str_replace( "MPH", " miles per hour. ", $newvalue ); 
$newvalue = str_replace( "mph", " miles per hour. ", $newvalue ); 
$newvalue = str_replace( " E ", " East ", $newvalue ); 
$newvalue = str_replace( " NE ", " North East ", $newvalue ); 
$newvalue = str_replace( " W ", " West ", $newvalue ); 
$newvalue = str_replace( " NW ", " North West ", $newvalue ); 
$newvalue = str_replace( " N ", " North ", $newvalue ); 
$newvalue = str_replace( " S ", " South ", $newvalue ); 
$newvalue = str_replace( " SE ", " South East ", $newvalue ); 
$newvalue = str_replace( " SW ", " South West ", $newvalue ); 
$newvalue = str_replace( "&nbsp;", " ", $newvalue ); 
$newvalue = str_replace( "So.", "Southern ", $newvalue );
$newvalue = str_replace( " Usc", "", $newvalue ); 
$newvalue = str_replace( "Intnl", "International ", $newvalue ); 
$newvalue = str_replace( "Intl", "International ", $newvalue ); 
$newvalue = str_replace( "Term.", "Terminal.", $newvalue );
$newvalue = str_replace( "Arpt", "Airport ", $newvalue ); 
$newvalue = str_replace( "01:", "1:", $newvalue );
$newvalue = str_replace( "02:", "2:", $newvalue );
$newvalue = str_replace( "03:", "3:", $newvalue );
$newvalue = str_replace( "04:", "4:", $newvalue );
$newvalue = str_replace( "05:", "5:", $newvalue );
$newvalue = str_replace( "06:", "6:", $newvalue );
$newvalue = str_replace( "07:", "7:", $newvalue );
$newvalue = str_replace( "08:", "8:", $newvalue );
$newvalue = str_replace( "09:", "9:", $newvalue );
$newvalue = str_replace( ". .", ".", $newvalue );
$newvalue = str_replace( " DC", " D.C.", $newvalue );
$newvalue = str_replace( " District of Columbia", " D.C.", $newvalue );
$newvalue = str_replace( "..", ".", $newvalue );
$newvalue = str_replace( "(awos)", " Automated Weather Observation System", $newvalue );
$newvalue = str_replace( "(aw)", " Automated Weather Station", $newvalue );
$newvalue = str_replace( "(was", " (Automated Weather Station", $newvalue );
$newvalue = str_replace( "NA:", " ", $newvalue ); 
$newvalue = str_replace( "windy", "wendy", $newvalue ); 

$newvalue = str_replace( "Lat:", "Latitude:  ", $newvalue ); 
$newvalue = str_replace( "Lon:", ". Longitude:  ", $newvalue ); 
$newvalue = str_replace( "Elev:", ". Elevation:  ", $newvalue ); 

$newvalue = $cityupdate . $newvalue ;

$errcode= strpos($newvalue, "NULL");
$errcod2= strpos($newvalue, "meta");

if ($errcode>0 or $errcod2>0 ) :
 $value="I'm sorry. No weather information is currently available for $city. Have a nice day. Good bye." ;
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
// system("mime-construct --to $email --subject " . chr(34) . "Nerd Vittles Weather ver. 2.1 Session Log" . chr(34) . " --attachment $log --type text/plain --file $log") ;
  system($txt) ;
 endif ;
 exit ;
else  :
 $welcomereport =  $newvalue ;
endif ;


$beginpoint = "port_mp_ns";
$endpoint   = "<u>Forecast" ;
$start= strpos($value, "$beginpoint");  
$finish= strpos($value, "$endpoint");  
$length= $finish-$start;

$forecastglance="<a href=\"http://www.srh.noaa.gov/port/" . substr($value, $start, $length) . "<u>Forecast at a Glance</u></a><br>";

$value = str_replace( "select=1", "select=1", $value );
$forecastdetailed="<a href=\"http://www.srh.noaa.gov/port/" . substr($value, $start, $length) . "<u>Detailed 7-day Forecast</u></a><br>";

$value = str_replace( "select=1", "select=3", $value );
$currentconditions="<a href=\"http://www.srh.noaa.gov/port/" . substr($value, $start, $length) . "<u>Current Conditions</u></a><br>";

// Detailed currentconditions report
$end=strpos($currentconditions,">");
$currentconditions_link=substr($currentconditions,9,$end-10);
$currentconditions_link = str_replace( " ", "%20", $currentconditions_link );
$fd = fopen($currentconditions_link, "r");
if (!$fd) {
 echo "<p>Unable to open web connection. \n";
 exit;
}
$currentconditions_report = "";
while(!feof($fd)){
        $currentconditions_report .= fread($fd, 4096);
}
fclose($fd);
$start= strpos($currentconditions_report, "Weather:");
$currentconditions_report=substr($currentconditions_report,$start);
$end=strpos($currentconditions_report, "<hr>");
$currentconditions_report="Current Weather Conditions". substr($currentconditions_report,7,$end-7);
$currentconditions_report = str_replace( " in<br>", " inches<br>", $currentconditions_report);
$currentconditions_report = str_replace( " in.", " inches ", $currentconditions_report);
$currentconditions_report = str_replace( "mb", "millibars ", $currentconditions_report);
$currentconditions_report = str_replace( "(", ", ", $currentconditions_report);
$currentconditions_report = str_replace( ")", ". ", $currentconditions_report);
$currentconditions_report = str_replace( "Barometer", "Barometric Pressure", $currentconditions_report );
$currentconditions_report = str_replace( "&deg;F", " degrees fair in height ", $currentconditions_report );
$currentconditions_report = str_replace( "&deg;C", " degrees centigrade ", $currentconditions_report );
$currentconditions_report = str_replace( "%", " per cent ", $currentconditions_report );
$currentconditions_report = str_replace( "mi.", " miles. ", $currentconditions_report );
$currentconditions_report = str_replace( "MPH", " miles per hour ", $currentconditions_report );
$currentconditions_report = str_replace( "Vrbl", "Variable at ", $currentconditions_report );
$currentconditions_report = str_replace( "Wind Speed", "Wind Direction and Speed", $currentconditions_report );
$currentconditions_report = str_replace( " E ", " From the East at ", $currentconditions_report );
$currentconditions_report = str_replace( " NE ", " From the North East at ", $currentconditions_report );
$currentconditions_report = str_replace( " W ", " From the West at ", $currentconditions_report );
$currentconditions_report = str_replace( " NW ", " From the North West at ", $currentconditions_report );
$currentconditions_report = str_replace( " N ", " From the North at ", $currentconditions_report );
$currentconditions_report = str_replace( " S ", " From the South at ", $currentconditions_report );
$currentconditions_report = str_replace( " SE ", " From the South East at ", $currentconditions_report );
$currentconditions_report = str_replace( " SW ", " From the South West at ", $currentconditions_report );
$currentconditions_report = str_replace( " G ", " gusting to ", $currentconditions_report );
$currentconditions_report = str_replace( ".00", "", $currentconditions_report );
$currentconditions_report = str_replace( "NA", " currently unavailable ", $currentconditions_report );
$currentconditions_report = str_replace( "NULL", " currently unavailable ", $currentconditions_report );
$currentconditions_report = str_replace( "windy", "wendy", $currentconditions_report );
$currentconditions_report = str_replace( "<br>", ".", $currentconditions_report);
$currentconditions_report = str_replace( "/", " and ", $currentconditions_report );
$currentconditions_report = str_replace( ". ,", ". ", $currentconditions_report);
$currentconditions_report = str_replace( ". .", ". ", $currentconditions_report);
// end of Detailed current conditions report


// Detailed weather forecast
$end=strpos($forecastdetailed,">");
$forecastdetailed_link=substr($forecastdetailed,9,$end-10);
$forecastdetailed_link = str_replace( " ", "%20", $forecastdetailed_link );
$fd = fopen($forecastdetailed_link, "r");
if (!$fd) {
 echo "<p>Unable to open web connection. \n";
 exit;
}
$forecastdetailed_report = "";
while(!feof($fd)){
        $forecastdetailed_report .= fread($fd, 4096);
}
fclose($fd);
$start= strpos($forecastdetailed_report, "</div>");
$forecastdetailed_report=substr($forecastdetailed_report,$start);
$end=strpos($forecastdetailed_report, "<hr>") ;
$forecastdetailed_report="Here's the latest forecast. " . substr($forecastdetailed_report,7,$end-7);
$forecastdetailed_report = str_replace( "<b>", " ", $forecastdetailed_report);
$forecastdetailed_report = str_replace( "</b>", " ", $forecastdetailed_report);
$forecastdetailed_report = str_replace( " in<br>", " inches<br>", $forecastdetailed_report);
$forecastdetailed_report = str_replace( " in.", " inches ", $forecastdetailed_report);
$forecastdetailed_report = str_replace( "mb", "millibars ", $forecastdetailed_report);
$forecastdetailed_report = str_replace( "(", ", ", $forecastdetailed_report);
$forecastdetailed_report = str_replace( ")", ". ", $forecastdetailed_report);
$forecastdetailed_report = str_replace( "Barometer", "Barometric Pressure", $forecastdetailed_report );
$forecastdetailed_report = str_replace( "&deg;F", " degrees fair in height ", $forecastdetailed_report );
$forecastdetailed_report = str_replace( "&deg;C", " degrees centigrade ", $forecastdetailed_report );
$forecastdetailed_report = str_replace( "%", " per cent ", $forecastdetailed_report );
$forecastdetailed_report = str_replace( "mi.", " miles. ", $forecastdetailed_report );
$forecastdetailed_report = str_replace( "MPH", " miles per hour ", $forecastdetailed_report );
$forecastdetailed_report = str_replace( "mph", " miles per hour ", $forecastdetailed_report );
$forecastdetailed_report = str_replace( "Vrbl", "Variable at ", $forecastdetailed_report );
$forecastdetailed_report = str_replace( "Wind Speed", "Wind Direction and Speed", $forecastdetailed_report );
$forecastdetailed_report = str_replace( " E ", " From the East at ", $forecastdetailed_report );
$forecastdetailed_report = str_replace( " NE ", " From the North East at ", $forecastdetailed_report );
$forecastdetailed_report = str_replace( " W ", " From the West at ", $forecastdetailed_report );
$forecastdetailed_report = str_replace( " NW ", " From the North West at ", $forecastdetailed_report );
$forecastdetailed_report = str_replace( " N ", " From the North at ", $forecastdetailed_report );
$forecastdetailed_report = str_replace( " S ", " From the South at ", $forecastdetailed_report );
$forecastdetailed_report = str_replace( " SE ", " From the South East at ", $forecastdetailed_report );
$forecastdetailed_report = str_replace( " SW ", " From the South West at ", $forecastdetailed_report );
$forecastdetailed_report = str_replace( " G ", " gusting to ", $forecastdetailed_report );
$forecastdetailed_report = str_replace( ".00", "", $forecastdetailed_report );
$forecastdetailed_report = str_replace( "NA", " currently unavailable ", $forecastdetailed_report );
$forecastdetailed_report = str_replace( "NULL", " currently unavailable ", $forecastdetailed_report );
$forecastdetailed_report = str_replace( "windy", "wendy", $forecastdetailed_report );
$forecastdetailed_report = str_replace( "<br>", ".",$forecastdetailed_report);
$forecastdetailed_report = str_replace( "/", " and ", $forecastdetailed_report );
$forecastdetailed_report = str_replace( "...", ". ", $forecastdetailed_report);
$forecastdetailed_report = str_replace( ". .", ".", $forecastdetailed_report);
$forecastdetailed_report = str_replace( ". ,", ".", $forecastdetailed_report);
$forecastdetailed_report = str_replace( ".. .", ".", $forecastdetailed_report);
$forecastdetailed_report = str_replace( "..", ".", $forecastdetailed_report);
$forecastdetailed_report = substr($forecastdetailed_report,0,-2) . " That concludes the detailed weather forecast. Have a nice day. Goodbye." ;
// end of Detailed weather forecast

if ($debug) :
fputs($stdlog, "\nSummary: " . $welcomereport . "\n\n" );
endif ;

if ($debug) :
fputs($stdlog, "\nCurrent Conditions: " . $currentconditions_report . "\n\n" );
endif ;

if ($debug) :
fputs($stdlog, "\nForecast: " . $forecastdetailed_report . "\n\n" );
endif ;


// ------------

$value = $welcomereport . $currentconditions_report . $forecastdetailed_report ;
$value = ereg_replace("  *([,.:])", "\\1", $value);


//--------------


$fd = fopen($tmptext, "w"); 
if (!$fd) {
 echo "<p>Unable to open temporary text file in /tmp for writing. \n"; 
 exit; 
} 
$retcode = fwrite($fd,$value);	
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
