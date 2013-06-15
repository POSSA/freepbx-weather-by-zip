Installing U.S. Weather by Zip Code Module<br>
<h5>Zip Code Database (v1.0)</h5>
This zip database is provided free to anyone who wants
it with the request that anyone who benefits from it
please drop me a quick email and let me know how
you're using it. Also, if you wish to contribute to
this project or improve upon the database that is 
also welcome.<br>novak@linenoize.com<br>
<?php
if ( (isset($amp_conf['ASTVARLIBDIR'])?$amp_conf['ASTVARLIBDIR']:'') == '') {
	$astlib_path = "/var/lib/asterisk";
} else {
	$astlib_path = $amp_conf['ASTVARLIBDIR'];
}
// Need to add check here to check existing mysql table, get rid of zipcode and add wgroundkey
// add primary key index 


?><br>Installing Default Configuration values.<br>
<?php

$sql ="INSERT INTO weatheroptions (engine, wgroundkey) ";
$sql .= "               VALUES ('noaa-flite',        '')";
$check = $db->query($sql);
if (DB::IsError($check)) {
        die_freepbx( "Can not create default values in `weatheroptions` table: " . $check->getMessage() .  "\n");
}



// Past versions of this module used to edit extensions_custom.conf directly to add dialplan
// we need to look for existing occurances of the include line from past sloppy uninstall/upgrade and remove them
$filename = '/etc/asterisk/extensions_custom.conf';
$includecontent = "#include custom_weatherzip.conf\n";
function replace_file($path, $string, $replace)
{
    set_time_limit(0);
    if (is_file($path) === true)
    {
        $file = fopen($path, 'r');
        $temp = tempnam('./', 'tmp');
        if (is_resource($file) === true)
        {
            while (feof($file) === false)
            {
                file_put_contents($temp, str_replace($string, $replace, fgets($file)), FILE_APPEND);
            }
            fclose($file);
        }
        unlink($path);
    }
    return rename($temp, $path);
}

replace_file($filename, $includecontent, '');


?>Verifying / Installing cronjob into the FreePBX cron manager.<br>
<?php
$sql = "SELECT * FROM `cronmanager` WHERE `module` = 'weatherzip' LIMIT 1;";

$res = $db->query($sql);

if($res->numRows() != 1)
{
$sql = "INSERT INTO	cronmanager (module,id,time,freq,command) VALUES ('weatherzip','every_day',23,24,'/usr/bin/find /var/lib/asterisk/sounds/tts -name \"*.wav\" -mtime +1 -exec rm {} \\\;')";

$check = $db->query($sql);
if (DB::IsError($check))
	{
	die_freepbx( "Can not create values in cronmanager table: " . $check->getMessage() .  "\n");
	}
}
?>Verifying / Creating TTS Folder.<br>
<?php
$parm_tts_dir = '/var/lib/asterisk/sounds/tts';
if (!is_dir ($parm_tts_dir)) mkdir ($parm_tts_dir, 0775);
?>Creating Feature Code.<br>
<?php
// Register FeatureCode - Weather by Zip;
$fcc = new featurecode('weatherzip', 'weatherzip');
$fcc->setDescription('Weather by Zip Code');
$fcc->setDefault('*947');
$fcc->update();
unset($fcc);
?>
