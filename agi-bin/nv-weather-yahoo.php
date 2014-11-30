#!/usr/bin/php -q
<?php
 ob_implicit_flush(false);
 error_reporting(0);
 set_time_limit(300);

//   Nerd Vittles Weather by Yahoo ver. 5.1, (c) Copyright Ward Mundy, 2007-2012. All rights reserved.

//                    This software is licensed under the GPL2 license.
//
//   Material alteration of the spoken content provided by this application is strictly prohibited.
//
//   For a copy of license, visit http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
//
//    For additional information, contact us: http://pbxinaflash.com/about/comment.php



//-------- DON'T CHANGE ANYTHING ABOVE THIS LINE ----------------

 $metric = false ;

 $debug = 1;
 $newlogeachdebug = 1;
 $emaildebuglog = 0;
 $email = "yourname@yourdomain" ;

//-------- DON'T CHANGE ANYTHING BELOW THIS LINE ----------------

$states_name  = array('AL'=>"Alabama",'AK'=>"Alaska",'AZ'=>"Arizona",'AR'=>"Arkansas",'CA'=>"California",'CO'=>"Colorado",'CT'=>"Connecticut",'DE'=>"Delaware",'FL'=>"Florida",'GA'=>"Georgia",'HI'=>"Hawaii",'ID'=>"Idaho",'IL'=>"Illinois", 'IN'=>"Indiana", 'IA'=>"Iowa",  'KS'=>"Kansas",'KY'=>"Kentucky",'LA'=>"Louisiana",'ME'=>"Maine",'MD'=>"Maryland", 'MA'=>"Massachusetts",'MI'=>"Michigan",'MN'=>"Minnesota",'MS'=>"Mississippi",'MO'=>"Missouri",'MT'=>"Montana",'NE'=>"Nebraska",'NV'=>"Nevada",'NH'=>"New Hampshire",'NJ'=>"New Jersey",'NM'=>"New Mexico",'NY'=>"New York",'NC'=>"North Carolina",'ND'=>"North Dakota",'OH'=>"Ohio",'OK'=>"Oklahoma", 'OR'=>"Oregon",'PA'=>"Pennsylvania",'RI'=>"Rhode Island",'SC'=>"South Carolina",'SD'=>"South Dakota",'TN'=>"Tennessee",'TX'=>"Texas",'UT'=>"Utah",'VT'=>"Vermont",'VA'=>"Virginia",'WA'=>"Washington",'DC'=>"Washington D.C.",'WV'=>"West Virginia",'WI'=>"Wisconsin",'WY'=>"Wyoming",'AB'=>"Alberta",'BC'=>"British Columbia",'MB'=>"Manitoba",'NB'=>"New Brunswick",'WY'=>"Wyoming",'NL'=>"Newfoundland",'WY'=>"Wyoming",'NT'=>"Northwest Territories",'NS'=>"Nova Scotia",'NU'=>"Nunavut",'ON'=>"Ontario",'PE'=>"Prince Edward Island",'QC'=>"Quebec",'SK'=>"Saskatchewan",'YT'=>"Yukon");
$states_abbr = array();
foreach ($states_name as $abbr => $state) {
    $states_abbr[$state] = $abbr ;
}
$day_of_week = array('Sunday'=>"Sun",'Monday'=>"Mon",'Tuesday'=>"Tue",'Wednesday'=>"Wed",'Thursday'=>"Thu",'Friday'=>"Fri",'Saturday'=>"Sat");


function fulldow($val) {
global $day_of_week;
$value = array_keys($day_of_week,$val);
$val= $value[0] ;
return $val ;
}

function state($val) {
global $states_name, $states_abbr;
$value = array_keys($states_abbr,$val);
$val= $value[0] ;
return $val ;
}


$log = "/var/log/asterisk/nv-weather-yahoo.txt" ;
if ($debug and $newlogeachdebug) :
 if (file_exists($log)) :
  unlink($log) ;
 endif ;
endif ;

 $stdlog = fopen($log, 'a'); 
 $stdin = fopen('php://stdin', 'r'); 
 $stdout = fopen( 'php://stdout', 'w' ); 

if ($debug) :
  fputs($stdlog, "Nerd Vittles Weather by Yahoo ver. 5.1 (c) Copyright 2007-2012, Ward Mundy. All Rights Reserved.\n\n" . date("F j, Y - H:i:s") . "  *** New session ***\n\n" ); 
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

$zip = $_SERVER["argv"][1];
$zip=trim($zip);

if ($debug) :
fputs($stdlog, "Location: " . $zip . "\n" );
endif ;


$place = $zip;




$query ="http://query.yahooapis.com/v1/public/yql?q=select%20*%20from%20weather.bylocation%20where%20location%3D'$place'%20and%20unit%3D'c'%3B&diagnostics=true&env=store%3A%2F%2Fdatatables.org%2Falltableswithkeys";

$query = trim(str_replace( " ", "%20", $query));



$fd = fopen($query, "r");
if (!$fd) {
 echo "<p>Unable to open web connection. \n";
 $msg=chr(34)."I'm sorry. No weather information currently is available for $place. Please try again later.".chr(34);
 execute_agi("SET VARIABLE WEATHER $msg");
 exit;
}
$value = "";
while(!feof($fd)){
        $value .= fread($fd, 4096);
}
fclose($fd);

$pos = strpos($value,"City not found");
if ($pos===false) :
 $pos="good2go";
else :
 $msg=chr(34)."No weather information currently is available for $place: Please try again later.".chr(34);
 execute_agi("SET VARIABLE WEATHER $msg");
 exit;
endif ;


//==============

$thetext="sunrise=".chr(34);
$endtext=chr(34);
$start= strpos($value, $thetext);
#echo $start . chr(10);
$tmptext = substr($value,$start+strlen($thetext),20);
#echo $tmptext.chr(10);
#echo $start+strlen($thetext)+1;
#echo chr(10);
$end=strpos($tmptext, $endtext);
#echo $end . chr(10);
$tmptext = substr($tmptext,0,$end);
$tmptext = trim(str_replace( "am", "a.m. ", $tmptext));
$tmptext = trim(str_replace( "pm", "p.m. ", $tmptext));
$sunrise=$tmptext;
#echo "Sunrise: ".$sunrise . chr(10);

$thetext="sunset=".chr(34);
$endtext=chr(34);
$start= strpos($value, $thetext);
#echo $start . chr(10);
$tmptext = substr($value,$start+strlen($thetext),20);
#echo $tmptext.chr(10);
#echo $start+strlen($thetext)+1;
#echo chr(10);
$end=strpos($tmptext, $endtext);
#echo $end . chr(10);
$tmptext = substr($tmptext,0,$end);
$tmptext = trim(str_replace( "am", "a.m. ", $tmptext));
$tmptext = trim(str_replace( "pm", "p.m. ", $tmptext));
$sunset=$tmptext;
#echo "Sunset: ".$sunset . chr(10);

$thetext="humidity=".chr(34);
$endtext=chr(34);
$start= strpos($value, $thetext);
#echo $start . chr(10);
$tmptext = substr($value,$start+strlen($thetext),50);
#echo $tmptext.chr(10);
#echo $start+strlen($thetext)+1;
#echo chr(10);
$end=strpos($tmptext, $endtext);
#echo $end . chr(10);
$humidity = substr($tmptext,0,$end);
#echo "Humidity: ".$humidity ." per cent". chr(10);

$thetext="pressure=".chr(34);
$endtext=chr(34);
$start= strpos($tmptext, $thetext)+strlen($thetext);
$tmptext=substr($tmptext,$start);
$end=strpos($tmptext, $endtext);
#echo $end . chr(10);
$mbarometer = substr($tmptext,0,$end);
$barometer = round(29.92 * 1034 / $mbarometer,2) ;
#if ( $metric ):
# echo "Barometric Pressure: ".$mbarometer ." millibars". chr(10);
#else :
# echo "Barometric Pressure: ".$barometer ." inches". chr(10);
#endif;


$thetext="direction=".chr(34);
$endtext=chr(34);
$start= strpos($value, $thetext);
#echo $start . chr(10);
$tmptext = substr($value,$start+strlen($thetext),50);
#echo $tmptext.chr(10);
#echo $start+strlen($thetext)+1;
#echo chr(10);
$end=strpos($tmptext, $endtext);
#echo $end . chr(10);
$degrees = substr($tmptext,0,$end);
if ($degrees>=349) :
 $direction="north";
elseif ($degrees>=326 ) :
 $direction="north northwest";
elseif ($degrees>=304 ) :
 $direction="northwest";
elseif ($degrees>=281 ) :
 $direction="west northwest";
elseif ($degrees>=259 ) :
 $direction="west";
elseif ($degrees>=236 ) :
 $direction="west southwest";
elseif ($degrees>=214 ) :
 $direction="southwest";
elseif ($degrees>=191 ) :
 $direction="south southwest";
elseif ($degrees>=169 ) :
 $direction="south";
elseif ($degrees>=146 ) :
 $direction="south southeast";
elseif ($degrees>=124 ) :
 $direction="southeast";
elseif ($degrees>=101 ) :
 $direction="east southeast";
elseif ($degrees>=79 ) :
 $direction="east";
elseif ($degrees>=56 ) :
 $direction="east northeast";
elseif ($degrees>=34 ) :
 $direction="northeast";
elseif ($degrees>=11 ) :
 $direction="north northeast";
else :
 $direction="north";
endif ;
#echo "Wind Direction: ".$direction . chr(10);

$thetext="speed=".chr(34);
$endtext=chr(34)."/";
$start= strpos($tmptext, $thetext)+strlen($thetext);
$tmptext=substr($tmptext,$start);
$end=strpos($tmptext, $endtext);
$tmptext = substr($tmptext,0,$end);
$kspeed=$tmptext;
$speed = round($kspeed * .621,1);
#if ($metric) :
# echo "Speed: ".$kspeed." kilometers per hour".chr(10);
#else :
# echo "Speed: ".$speed." miles per hour".chr(10);
#endif;

$thetext="visibility=".chr(34);
$endtext=chr(34);
$start= strpos($value, $thetext);
#echo $start . chr(10);
$tmptext = substr($value,$start+strlen($thetext),50);
#echo $tmptext.chr(10);
#echo $start+strlen($thetext)+1;
#echo chr(10);
$end=strpos($tmptext, $endtext);
#echo $end . chr(10);
$kvisibility = substr($tmptext,0,$end);
$visibility = round($kvisibility * .621,1);
#if ($metric) :
# echo "Visibility: ".$kvisibility ." kilometers". chr(10);
#else :
# echo "Visibility: ".$visibility ." miles". chr(10);
#endif;

$thetext="Current Conditions:";
$endtext="<BR />";
$start= strpos($value, $thetext);
#echo $start . chr(10);
$tmptext = substr($value,$start+strlen($thetext),40);
#echo $tmptext.chr(10);
#echo $start+strlen($thetext)+1;
#echo chr(10);
$end=strpos($tmptext, $endtext);
#echo $end . chr(10);
$tmptext = substr($tmptext,11,$end-11);
$tmptext = str_replace( ",", ".", $tmptext);
$sp1 = strpos($tmptext,". ");
$sp2 = strrpos($tmptext," ");
$mcurrent=$tmptext . "entigrade";
$mcurrent=str_replace( "Centigrade", "degrees centigrade. ", $mcurrent);;
$current = substr($tmptext,0,$sp1);
$currenttemp = substr($tmptext,$sp1+1,$sp2-$sp1-1);
$currenttemp = round((9/5)*$currenttemp+32,1);
$current = $current . ". " . $currenttemp . " degrees fahrenheit. ";

#if ($metric) :
# echo "Current Conditions: ".$mcurrent . chr(10);
#else :
# echo "Current Conditions: ".$current . chr(10);
#endif;


$thetext="Forecast:";
$endtext="<a href";
$start= strpos($value, $thetext);
#echo $start . chr(10);
$tmptext = substr($value,$start+strlen($thetext)+11,300);
#echo $start+strlen($thetext)+1;
#echo chr(10);
$end=strpos($tmptext, $endtext);
#echo $end . chr(10);
$tmptext = substr($tmptext,0,$end-8);
$tmptext = trim(str_replace( "<br />", " . ", $tmptext));
$tmptext = trim(str_replace( "Sun -", "Sunday:", $tmptext));
$tmptext = trim(str_replace( "Mon -", "Monday:", $tmptext));
$tmptext = trim(str_replace( "Tue -", "Tuesday:", $tmptext));
$tmptext = trim(str_replace( "Wed -", "Wednesday:", $tmptext));
$tmptext = trim(str_replace( "Thu -", "Thursday:", $tmptext));
$tmptext = trim(str_replace( "Fri -", "Friday:", $tmptext));
$tmptext = trim(str_replace( "Sat -", "Saturday:", $tmptext));
$tmptext = trim(str_replace( chr(10), "", $tmptext));
$tmptext = trim(str_replace( "Low", ". Low", $tmptext));

if (! $metric) :
 $thistext=$tmptext;
 for ($i = 1; $i <= 6; $i++) {
    $sp1 = strpos($thistext,": ");    
    $thistext = substr($thistext,$sp1+2);
    $sp2 = strpos($thistext," ");
   if ( $i<>1 and $i<>4 ) :
    $tmp=substr($thistext,0,$sp2);
    $ftmp=round((9/5)*$tmp+32,1);
    $tmptext = str_replace( " $tmp ", " $ftmp ", $tmptext);
   endif  ;
 }
endif;

$tmptext = str_replace( " .", ".", $tmptext);
$tmptext = str_replace( " PM ", " afternoon ", $tmptext);
$tmptext = str_replace( " AM ", " morning ", $tmptext);

#echo $tmptext.chr(10);


$forecast = "Here are the latest weather conditions and forecast for $place. Brought to you by Yahoo and Nerd Viddles. ";
$forecast = $forecast . "Currently: ". $current . "Humidity: " . $humidity . " per cent. ". "Barometric pressure: ". $barometer ." inches. "."Wend direction and speed: from the " . $direction . " at " . $speed . " miles per hour. " . "Visibility: ". $visibility ." miles. Sunrise today at ".$sunrise. " Sunset tonight at " . $sunset   ;
$forecast = $forecast . " Here is the latest forecast. ".$tmptext . " Have a great day. Goodbye.";

$mforecast = "Here are the latest weather conditions and forecast for $place. Brought to you by Yahoo and Nerd Vittles. ";
$mforecast = $mforecast . "Currently: ". $mcurrent . "Humidity: " . $humidity . " per cent. ". "Barometric pressure: ". $mbarometer ." millibars. "."Wend direction and speed: from the " . $direction . " at " . $kspeed . " kilometers per hour. " . "Visibility: ". $kvisibility ." kilometers. Sunrise today at ".$sunrise. " Sunset tonight at " . $sunset   ;
$mforecast = $mforecast . " Here is the latest forecast. ".$tmptext. " Have a great day. Goodbye.";

if ($metric) :
 $forecast = $mforecast ;
endif ;

$forecast = str_replace( " 1 miles", " 1 mile", $forecast);
$forecast = str_replace( " 1 kilometers", " 1 kilometer", $forecast);



//==============

$msg= chr(34) .$forecast. chr(34);
$msg = str_replace( ",", " ", $msg );

if ($debug) :
fputs($stdlog, "Forecast: " . $msg . "\n" );
endif ;

execute_agi("SET VARIABLE WEATHER $msg");

//echo $msg;
//echo chr(10);
//echo chr(10);

if ($emaildebuglog) :
 system("mime-construct --to $email --subject " . chr(34) . "Nerd Vittles Weather by Yahoo ver. 5.1 Session Log" . chr(34) . " --attachment $log --type text/plain --file $log") ;
endif ;

// clean up file handlers etc.
fclose($stdin);
fclose($stdout);
fclose($stdlog);
exit;

?>
