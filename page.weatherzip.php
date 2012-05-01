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
#print_r($selected);
#	die();

$module_info = xml2array("modules/weatherzip/module.xml");

?>
<form method="POST" action="">
	<br><h2><?php echo _("U.S. Weather by Zipcode")?><hr></h5></td></tr>
Weather by Zip Code allow you to retrieve current weather information from any touchtone phone using nothing more than your PBX connected to the Internet.  When prompted, you key in any of 42,740 U.S. Zip Codes using a touchtone phone. The report is downloaded, converted to an audio file, and played back to you.<br><br>
Current conditions and a seven-day forecast for the chosen city then will be retrieved from the National Weather Service and played back to your telephone using the Flite or Swift text-to-speech engine. <br>

	<tr><td colspan="2"><br><h5><?php echo _("TTS Engine")?>:<hr></h5></td></tr>
<tr>Select the Text To Speach engine you wish the Weather by Zip program to use to audio render your reports.<br><br>
		<td><a href="#" class="info"><?php echo _("Choose an engine")?>:<span><?php echo _("List of TTS engines detected on the server. Choose the one you want to use for the current sentence.")?></span></a></td>
		<td>
		<select size="1" name="engine">
<?php
echo "<option".(($date[0]==flite)?' selected':'').">flite</option>\n";
echo "<option".(($date[0]==swift)?' selected':'').">swift</option>\n";
?>
</select>
		</td>
	</tr><hr><br><br><input type="submit" value="Submit" name="B1"><br><br><br>
<small><center>Weather by Zip was put into FreePBX Module format by Tony Shiffer.<br>
The module is maintained by the developer community at <a target="_blank" href="https://github.com/POSSA"> PBX Open Source Software Alliance</a></center></small>
<?php
print '<p align="center" style="font-size:11px;">The Original Weather by Zip Script was created by <a target="_blank" href="http://www.nerdvittles.com">Ward Mundy.</a>';
print '<br>Module version '.$module_info['module']['version'].'</p>';
?>
