<?php
//
//
//This program is free software; you can redistribute it and/or
//modify it under the terms of the GNU General Public License
//as published by the Free Software Foundation; either version 2
//of the License, or (at your option) any later version.
//
//This program is distributed in the hope that it will be useful,
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//GNU General Public License for more details.

//tts_findengines()
if(count($_POST)){
	weatheroptions_saveconfig();
}
	$date = weatheroptions_getconfig();
	$selected = ($date[0]);


//  Get current featurecode from FreePBX registry
$fcc = new featurecode('weatherzip', 'weatherzip');
$featurecode = $fcc->getCodeActive(); 


?>
<form method="POST" action="">
	<br><h2><?php echo _("U.S. Weather by Zipcode")?><hr></h5></td></tr>
Weather by Zip Code allow you to retrieve current weather information from any touchtone phone using nothing more than your PBX connected to the Internet.  When prompted, you key in any of 42,740 U.S. Zip Codes using a touchtone phone. The report is downloaded, converted to an audio file, and played back to you.<br><br>
Current conditions and a seven-day forecast for the chosen city then will be retrieved from the National Weather Service and played back to your telephone using the selected text-to-speech engine. <br><br>
The feature code to access this service is currently set to <b><?PHP print $featurecode; ?></b>.  This value can be changed in Feature Codes. <br>

	<tr><td colspan="2"><br><h5><?php echo _("TTS Engine")?>:<hr></h5></td></tr>
<tr>Select the Text To Speach engine and Forecast source combination you wish the Weather by Zip program to use.<br>The module does not check to see if the selected TTS engine is present, ensure to choose an engine that is installed on the system.<br><br>
		<td><a href="#" class="info"><?php echo _("Choose a service and engine")?>:<span><?php echo _("List of TTS engines and weather services.")?></span></a></td>
		<td>
		<select size="1" name="engine">
<?php
echo "<option".(($date[0]=='noaa-flite')?' selected':'').">noaa-flite</option>\n";
echo "<option".(($date[0]=='noaa-swift')?' selected':'').">noaa-swift</option>\n";
echo "<option".(($date[0]=='googlew-flite')?' selected':'').">googlew-flite</option>\n";
echo "<option".(($date[0]=='googlew-swift')?' selected':'').">googlew-swift</option>\n";
echo "<option".(($date[0]=='googlew-googletts')?' selected':'').">googlew-googletts</option>\n";
?>
</select>
<br><br>key:<br>
<b>noaa</b> - National Oceanic and Atmospheric Administration (USA weather service)<br>
<b>googlew</b> - Free Google Weather API<br>
<b>flite</b> - Asterisk Flite Text to Speech Engine<br>
<b>swift</b> - Cepstral Swift Text to Speech Engine<br>
<b>googletts</b> - Google text to speech engine by Lefteris Zafiris<br>
		</td>
	</tr><hr><br><br><input type="submit" value="Submit" name="B1"><br><br><br>
<center><br>
The module is maintained by the developer community at <a target="_blank" href="https://github.com/POSSA/"> PBX Open Source Software Alliance</a>.  Support, documentation and current versions are available at the module <a target="_blank" href="https://github.com/POSSA/freepbx-weather-by-zip">dev site</a></center>
<?php
print '<p align="center" style="font-size:11px;">The Weather by Zip and Google Weather scripts were created and are currently maintaned by <a target="_blank" href="http://www.nerdvittles.com">Nerd Vittles</a>.';

?>
