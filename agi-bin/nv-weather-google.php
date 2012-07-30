#!/usr/bin/php -q
<?
 ob_implicit_flush(false);
 error_reporting(0);
 set_time_limit(300);

//   Nerd Vittles ZIP Weather ver. 5.0, (c) Copyright Ward Mundy, 2007-2012. All rights reserved.

//                    This software is licensed under the GPL2 license.
//
//   Material alteration of the spoken content provided by this application is strictly prohibited.
//
//   For a copy of license, visit http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
//
//    For additional information, contact us: http://pbxinaflash.com/about/comment.php



//-------- DON'T CHANGE ANYTHING ABOVE THIS LINE ----------------


// You can change the Canadian cities and Postal Codes below to meet your needs.
// 0-9 are used to match ZIP Codes 00000 through 00009 entered by telephone.
// No ZIP codes above 00009 are safe to tinker with. Reserved for use by USPS.
// Make sure you pick legitimate Canadian postal codes. There's NO error checking.
// Test code with a browser first, e.g. http://www.google.com/ig/api?weather=K1N6N5

 $canada[0] = "K1N6N5" ; // Ottawa
 $canada[1] = "V5K0A1" ; // Vancouver
 $canada[2] = "T1L1B8" ; // Banff
 $canada[3] = "T6P1X2" ; // Edmonton
 $canada[4] = "B3P2L5" ; // Halifax
 $canada[5] = "N5V0A5" ; // London
 $canada[6] = "H2Y1C6" ; // Montreal
 $canada[7] = "G1C2X4" ; // Quebec City
 $canada[8] = "M3H6A7" ; // Toronto
 $canada[9] = "R2C0A1" ; // Winnipeg

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


$log = "/var/log/asterisk/nv-weather-google.txt" ;
if ($debug and $newlogeachdebug) :
 if (file_exists($log)) :
  unlink($log) ;
 endif ;
endif ;

 $stdlog = fopen($log, 'a'); 
 $stdin = fopen('php://stdin', 'r'); 
 $stdout = fopen( 'php://stdout', 'w' ); 

if ($debug) :
  fputs($stdlog, "Nerd Vittles Google Weather ver. 5.0 (c) Copyright 2007-2012, Ward Mundy. All Rights Reserved.\n\n" . date("F j, Y - H:i:s") . "  *** New session ***\n\n" ); 
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

$zip = str_replace( "letter eye", "i", $zip);
$zip = str_replace( "letter to", "q", $zip);
$zip = str_replace( "letter im", "m", $zip);
$zip = str_replace( "letter in", "n", $zip);
$zip = str_replace( "letter and", "n", $zip);
$zip = str_replace( "letter page", "h", $zip);
$zip = str_replace( "letter you", "u", $zip);
$zip = str_replace( "letter text", "x", $zip);

$zip = str_replace( "zero ", "0 ", $zip);
$zip = str_replace( "one ", "1 ", $zip);
$zip = str_replace( "won ", "1 ", $zip);
$zip = str_replace( "two ", "2 ", $zip);
$zip = str_replace( "too ", "2 ", $zip);
$zip = str_replace( "to ", "2 ", $zip);
$zip = str_replace( "tube ", "2 ", $zip);
$zip = str_replace( "three ", "3 ", $zip);
$zip = str_replace( "four ", "4 ", $zip);
$zip = str_replace( "fore ", "4 ", $zip);
$zip = str_replace( "five ", "5 ", $zip);
$zip = str_replace( "six ", "6 ", $zip);
$zip = str_replace( "sex ", "6 ", $zip);
$zip = str_replace( "sixth ", "6 ", $zip);
$zip = str_replace( "seven ", "7 ", $zip);
$zip = str_replace( "eight ", "8 ", $zip);
$zip = str_replace( "ate ", "8 ", $zip);
$zip = str_replace( "nine ", "9 ", $zip);

$zip = str_replace( "letters", "", $zip);
$zip = str_replace( "letter", "", $zip);

$zip = str_replace( "numbers", "", $zip);
$zip = str_replace( "number", "", $zip);
$zip = str_replace( "x ray", "xray", $zip);
$zip = str_replace( "x-ray", "xray", $zip);
$zip = str_replace( "fanatic", "phonetic", $zip);
$zip = str_replace( "fanatics", "phonetic", $zip);

if ( strpos($zip,"phonetic")>0 or substr($zip,0,8)=="phonetic"  ) :
 $zip = str_replace( "phonetic ", "", $zip);
 $zip = str_replace( "phonetic", "", $zip);
 $pos=0;
 $newzip=$zip." ";
 $m=strpos($newzip," ");
 while ($m<>0){
  $testword = substr($newzip,0,$m);
  echo $testword;
  echo chr(10);
  if (strlen($testword)>1):
   $zip = str_replace( $testword, substr($testword,0,1), $zip);
  endif;
  $newzip=substr($newzip,$m+1);
  $m=strpos($newzip," ");
 }
 $zip = str_replace( " ", "", $zip);
 if (strlen($zip)>6) :
  $zip=substr($zip,0,6);
 endif ;
endif;

if ( $zip=="0" or $zip=="1" or $zip=="2" or $zip=="3" or $zip=="4" or $zip=="5" or $zip=="6" or $zip=="7" or $zip=="8" or $zip=="9"  ):
 $zip2=$canada[$zip];
 $zip=$zip2;
endif;

if ($debug) :
fputs($stdlog, "Location: " . $zip . "\n" );
endif ;


$query = "http://www.google.com/ig/api?weather=$zip";
$query = trim(str_replace( " ", "%20", $query));


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

$found=strpos($value,"problem_cause");
if ($found>0) :
 if ( substr($zip,0,4)=="0000" ) :
  $city = substr($zip,4,1);
  $zip = $canada[$city];
  $query = "http://www.google.com/ig/api?weather=$zip";
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
 else :
  $zip=substr($zip,0,1).".".substr($zip,1,1).".".substr($zip,2,1).".".substr($zip,3,1).".".substr($zip,4,1);
  $msg=chr(34)."I'm sorry but no weather data is available for $zip. Thank you for calling. Goodbye.".chr(34);
//  echo $msg;
//  echo chr(10);
  execute_agi("SET VARIABLE WEATHER $msg");
  exit;
 endif;
endif;

//echo $value;
//echo chr(10).chr(10);

$thetext="<city data=";
$endtext=chr(34)."/>";
$start= strpos($value, $thetext);
//echo $start . chr(10);
$tmptext = substr($value,$start+strlen($thetext)+1);
//echo $start+strlen($thetext)+1;
//echo chr(10);
$end=strpos($tmptext, $endtext);
//echo $end . chr(10);
$location = substr($tmptext,0,$end);
//echo $location;
$abbrev = substr($location,strlen($location)-2);
$location = substr($location,0,strlen($location)-2).state($abbrev);

$location = "This weather forecast for $location brought to you by Google and Nerd Vittles. ";
//echo $location.chr(10);


$thetext="<temp_f data=";
$endtext=chr(34)."/>";
$start= strpos($value, $thetext);
//echo $start . chr(10);
$tmptext = substr($value,$start+strlen($thetext)+1);
//echo $start+strlen($thetext)+1;
//echo chr(10);
$end=strpos($tmptext, $endtext);
//echo $end . chr(10);
$tempf = substr($tmptext,0,$end);
//echo $tempf;
//echo chr(10);

$thetext="<temp_c data=";
$endtext=chr(34)."/>";
$start= strpos($value, $thetext);
//echo $start . chr(10);
$tmptext = substr($value,$start+strlen($thetext)+1);
//echo $start+strlen($thetext)+1;
//echo chr(10);
$end=strpos($tmptext, $endtext);
//echo $end . chr(10);
$tempc = substr($tmptext,0,$end);
//echo $tempc;
//echo chr(10);

$temperature="Current temperature: $tempf degrees fahrenheit. $tempc degrees centigrade. ";
//echo $temperature;
//echo chr(10);

$thetext="<humidity data=";
$endtext=chr(34)."/>";
$start= strpos($value, $thetext);
//echo $start . chr(10);
$tmptext = substr($value,$start+strlen($thetext)+1);
//echo $start+strlen($thetext)+1;
//echo chr(10);
$end=strpos($tmptext, $endtext);
//echo $end . chr(10);
$humidity = substr($tmptext,0,$end);
$humidity = "Relative ".str_replace( "%", " per cent. ", $humidity );
//echo $humidity;
//echo chr(10);

$thetext="<wind_condition data=";
$endtext=chr(34)."/>";
$start= strpos($value, $thetext);
//echo $start . chr(10);
$tmptext = substr($value,$start+strlen($thetext)+1);
//echo $start+strlen($thetext)+1;
//echo chr(10);
$end=strpos($tmptext, $endtext);
//echo $end . chr(10);
$wind = substr($tmptext,0,$end);
$wind = str_replace( "Wind", "Wend Direction and Speed", $wind).". ";
$wind = str_replace( " mph", " miles per hour", $wind );
$wind = str_replace( " E ", " From the East ", $wind );
$wind = str_replace( " NE ", " From the North East ", $wind );
$wind = str_replace( " W ", " From the West ", $wind );
$wind = str_replace( " NW ", " From the North West ", $wind );
$wind = str_replace( " N ", " From the North ", $wind );
$wind = str_replace( " S ", " From the South ", $wind );
$wind = str_replace( " SE ", " From the South East ", $wind );
$wind = str_replace( " SW ", " From the South West ", $wind );
//echo $wind;
//echo chr(10);

$thetext="<icon data=";
$endtext=chr(34)."/>";
$start= strpos($value, $thetext);
//echo $start . chr(10);
$tmptext = substr($value,$start+strlen($thetext)+1);
//echo $start+strlen($thetext)+1;
//echo chr(10);
$end=strpos($tmptext, $endtext);
//echo $end . chr(10);
$conditions = "Current weather conditions: ".substr($tmptext,19,$end-23).". ";
$conditions = str_replace( "_", " ", $conditions );
//echo $conditions;
//echo chr(10);

$thetext="<forecast_conditions>";
$endtext="</forecast_conditions>";
$start= strpos($value, $thetext);
$tmptext = substr($value,$start+strlen($thetext));
$end=strpos($tmptext, $endtext);
$forecast1 = substr($tmptext,0,$end);

//echo $forecast1;
//echo chr(10);

$value=substr($value,$start+10);

$thetext="day_of_week data=";
$endtext=chr(34)."/>";
$start= strpos($value, $thetext);
$tmptext = substr($value,$start+strlen($thetext)+1);
$end=strpos($tmptext, $endtext);
$dow1 = fulldow(substr($tmptext,0,$end));
//echo $dow1;
//echo chr(10);

$thetext="low data=";
$endtext=chr(34)."/>";
$start= strpos($value, $thetext);
$tmptext = substr($value,$start+strlen($thetext)+1);
$end=strpos($tmptext, $endtext);
$low1 = substr($tmptext,0,$end);
//echo $low1;
//echo chr(10);

$thetext="high data=";
$endtext=chr(34)."/>";
$start= strpos($value, $thetext);
$tmptext = substr($value,$start+strlen($thetext)+1);
$end=strpos($tmptext, $endtext);
$high1 = substr($tmptext,0,$end);
//echo $high1;
//echo chr(10);

$thetext="condition data=";
$endtext=chr(34)."/>";
$start= strpos($value, $thetext);
$tmptext = substr($value,$start+strlen($thetext)+1);
$end=strpos($tmptext, $endtext);
$cond1 = substr($tmptext,0,$end);
//echo $cond1;
//echo chr(10);

$value= substr($value,$start+10);

$thetext="<forecast_conditions>";
$endtext="</forecast_conditions>";
$start= strpos($value, $thetext);
$tmptext = substr($value,$start+strlen($thetext));
$end=strpos($tmptext, $endtext);
$forecast2 = substr($tmptext,0,$end);

//echo $forecast2;
//echo chr(10);

$thetext="day_of_week data=";
$endtext=chr(34)."/>";
$start= strpos($value, $thetext);
$tmptext = substr($value,$start+strlen($thetext)+1);
$end=strpos($tmptext, $endtext);
$dow2 = fulldow(substr($tmptext,0,$end));
//echo $dow2;
//echo chr(10);

$thetext="low data=";
$endtext=chr(34)."/>";
$start= strpos($value, $thetext);
$tmptext = substr($value,$start+strlen($thetext)+1);
$end=strpos($tmptext, $endtext);
$low2 = substr($tmptext,0,$end);
//echo $low2;
//echo chr(10);

$thetext="high data=";
$endtext=chr(34)."/>";
$start= strpos($value, $thetext);
$tmptext = substr($value,$start+strlen($thetext)+1);
$end=strpos($tmptext, $endtext);
$high2 = substr($tmptext,0,$end);
//echo $high2;
//echo chr(10);

$thetext="condition data=";
$endtext=chr(34)."/>";
$start= strpos($value, $thetext);
$tmptext = substr($value,$start+strlen($thetext)+1);
$end=strpos($tmptext, $endtext);
$cond2 = substr($tmptext,0,$end);
//echo $cond2;
//echo chr(10);


$value= substr($value,$start+10);

$thetext="<forecast_conditions>";
$endtext="</forecast_conditions>";
$start= strpos($value, $thetext);
$tmptext = substr($value,$start+strlen($thetext));
$end=strpos($tmptext, $endtext);
$forecast3 = substr($tmptext,0,$end);

//echo $forecast3;
//echo chr(10);

$thetext="day_of_week data=";
$endtext=chr(34)."/>";
$start= strpos($value, $thetext);
$tmptext = substr($value,$start+strlen($thetext)+1);
$end=strpos($tmptext, $endtext);
$dow3 = fulldow(substr($tmptext,0,$end));
//echo $dow3;
//echo chr(10);

$thetext="low data=";
$endtext=chr(34)."/>";
$start= strpos($value, $thetext);
$tmptext = substr($value,$start+strlen($thetext)+1);
$end=strpos($tmptext, $endtext);
$low3 = substr($tmptext,0,$end);
//echo $low3;
//echo chr(10);

$thetext="high data=";
$endtext=chr(34)."/>";
$start= strpos($value, $thetext);
$tmptext = substr($value,$start+strlen($thetext)+1);
$end=strpos($tmptext, $endtext);
$high3 = substr($tmptext,0,$end);
//echo $high3;
//echo chr(10);

$thetext="condition data=";
$endtext=chr(34)."/>";
$start= strpos($value, $thetext);
$tmptext = substr($value,$start+strlen($thetext)+1);
$end=strpos($tmptext, $endtext);
$cond3 = substr($tmptext,0,$end);
//echo $cond3;
//echo chr(10);

$forecast="Here's the three day forecast. $dow1: $cond1 with a Low temperature of $low1 degrees and expected high of $high1 degrees fahrenheit. ";
$forecast=$forecast . "$dow2: $cond2 with a Low temperature of $low2 degrees and expected high of $high2 degrees. ";
$forecast=$forecast . "$dow3: $cond3 with a Low temperature of $low3 degrees and expected high of $high3 degrees. Thank you for calling. Good bye.";

$msg= chr(34).$location.$temperature.$humidity.$wind.$conditions.$forecast.chr(34);
$msg = str_replace( ",", " ", $msg );

if ($debug) :
fputs($stdlog, "Forecast: " . $msg . "\n" );
endif ;

execute_agi("SET VARIABLE WEATHER $msg");

//echo $msg;
//echo chr(10);
//echo chr(10);

if ($emaildebuglog) :
 system("mime-construct --to $email --subject " . chr(34) . "Nerd Vittles ZIP Weather ver. 5.0 Session Log" . chr(34) . " --attachment $log --type text/plain --file $log") ;
endif ;

// clean up file handlers etc.
fclose($stdin);
fclose($stdout);
fclose($stdlog);
exit;

?>
