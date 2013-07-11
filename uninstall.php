Un Installing U.S. Weather by Zip Code.<br>
<?php

if ( (isset($amp_conf['ASTVARLIBDIR'])?$amp_conf['ASTVARLIBDIR']:'') == '') {
	$astlib_path = "/var/lib/asterisk";
} else {
	$astlib_path = $amp_conf['ASTVARLIBDIR'];
}

if ( file_exists($astlib_path."/agi-bin/propolys-tts.agi") ) {
	if ( !unlink($astlib_path."/agi-bin/propolys-tts.agi") ) {
		echo _("TTS AGI script cannot be removed.");
	}
}
print 'Deleting the cron manager entries for this module.<br>';
$sql = "DELETE FROM cronmanager WHERE module = 'weatherzip'";
$check = $db->query($sql);
if (DB::IsError($check))
{
	die_freepbx( "Can not delete values in cronmanager table: " . $check->getMessage() .  "\n");
}

?>

