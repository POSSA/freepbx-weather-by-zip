Installing U.S. Weather by Zip Code<br>
<h5>Zip Code Database (v1.0)</h5>
This zip database is provided free to anyone who wants<br>
it with the request that anyone who benefits from it<br>
please drop me a quick email and let me know how<br>
you're using it. Also, if you wish to contribute to<br>
this project or improve upon the database that is<br> 
also welcome.<br>novak@linenoize.com<br>
<?php
if ( (isset($amp_conf['ASTVARLIBDIR'])?$amp_conf['ASTVARLIBDIR']:'') == '') {
	$astlib_path = "/var/lib/asterisk";
} else {
	$astlib_path = $amp_conf['ASTVARLIBDIR'];
}


?><br>Installing Default Configuration values.<br>
<?php

$sql ="INSERT INTO weatheroptions (engine, defaultzip) ";
$sql .= "               VALUES ('flite',        '12345')";

$check = $db->query($sql);
if (DB::IsError($check)) {
        die_freepbx( "Can not create default values in `weatheroptions` table: " . $check->getMessage() .  "\n");
}

$filename = '/etc/asterisk/extensions_custom.conf';
$includecontent = "#include custom_weatherzip.conf\n";

// Let's make sure the file exists and is writable first.
if (is_writable($filename)) {

 
    if (!$handle = fopen($filename, 'a')) {
         echo "Cannot open file ($filename)";
         exit;
    }
    // Write $somecontent to our opened file.
    if (fwrite($handle, $includecontent) === FALSE) {
        echo "Cannot write to file ($filename)";
        exit;
    }
    echo "<br>Success, wrote ($includecontent)<br> to file ($filename)<br><br>";

    fclose($handle);

} else {
    echo "The file $filename is not writable";
}
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
