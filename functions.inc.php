<?php /* $Id: $ */
// Xavier Ourciere xourciere[at]propolys[dot]com
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


if ( (isset($amp_conf['ASTVARLIBDIR'])?$amp_conf['ASTVARLIBDIR']:'') == '') {
	$astlib_path = "/var/lib/asterisk";
} else {
	$astlib_path = $amp_conf['ASTVARLIBDIR'];
}
$tts_astsnd_path = $astlib_path."/sounds/tts/";

function weatherzip_weatherzip($c) {
	global $ext;
	global $asterisk_conf;

	$date = weatheroptions_getconfig();
	$ttsengine = $date[0];

	$id = "app-weatherzip"; // The context to be included
	$ext->addInclude('from-internal-additional', $id); // Add the include from from-internal
	$ext->add($id, $c, '', new ext_answer(''));
	$ext->add($id, $c, '', new ext_wait('1'));
	$ext->add($id, $c, '', new ext_setvar('TIMEOUT(digit)', '7'));
	$ext->add($id, $c, '', new ext_setvar('TIMEOUT(response)', '10'));

	switch ($ttsengine) {
		case "noaa-flite":
			$ext->add($id, $c, '', new ext_flite('At the beep enter the five digit zip code for the weather report you wish to retrieve'));
			$ext->add($id, $c, '', new ext_read('ZIPCODE', 'beep','5'));
			$ext->add($id, $c, '', new ext_flite('Contacting the National Weather Service'));
			$ext->add($id, $c, '', new ext_agi('nv-weather-noaa.php,${ZIPCODE}'));
			$ext->add($id, $c, '', new ext_noop('Wave file: ${TMPWAVE}'));
			$ext->add($id, $c, '', new ext_playback('${TMPWAVE}'));
			$ext->add($id, $c, '', new ext_macro('hangupcall'));
		break;
		case "wunderground-flite":
			$ext->add($id, $c, '', new ext_flite('At the beep enter the five digit zip code for the weather report you wish to retrieve'));
			$ext->add($id, $c, '', new ext_read('ZIPCODE', 'beep','5'));
			$ext->add($id, $c, '', new ext_flite('Please hold a moment while we retrieve your report'));
			$ext->add($id, $c, '', new ext_agi('nv-weather-wunderground.php,${ZIPCODE}'));
			$ext->add($id, $c, '', new ext_noop('Forecast: ${WEATHER}'));
			$ext->add($id, $c, '', new ext_flite('${WEATHER}'));
			$ext->add($id, $c, '', new ext_macro('hangupcall'));
		break;
		case "wunderground-googletts":
			$ext->add($id, $c, '', new ext_agi('googletts.agi,"At the beep enter the zip code of the weather report you wish to retrieve",en'));
			$ext->add($id, $c, '', new ext_read('ZIPCODE', 'beep','5'));
			$ext->add($id, $c, '', new ext_agi('googletts.agi,"Please hold a moment while we retrieve your report",en'));
			$ext->add($id, $c, '', new ext_agi('nv-weather-wunderground.php,${ZIPCODE}'));
			$ext->add($id, $c, '', new ext_noop('Forecast: ${WEATHER}'));
			$ext->add($id, $c, '', new ext_agi('googletts.agi,"${WEATHER}",en'));
			$ext->add($id, $c, '', new ext_macro('hangupcall'));
		break;
	}	
}

function weatherzip_get_config($engine) {
	$modulename = 'weatherzip';
	
	// This generates the dialplan
	global $ext;  
	global $asterisk_conf;
	switch($engine) {
		case "asterisk":
			if (is_array($featurelist = featurecodes_getModuleFeatures($modulename))) {
				foreach($featurelist as $item) {
					$featurename = $item['featurename'];
					$fname = $modulename.'_'.$featurename;
					if (function_exists($fname)) {
						$fcc = new featurecode($modulename, $featurename);
						$fc = $fcc->getCodeActive();
						unset($fcc);
						
						if ($fc != '')
							$fname($fc);
					} else {
						$ext->add('from-internal-additional', 'debug', '', new ext_noop($modulename.": No func $fname"));
					}	
				}
			}
		break;
	}
}


function weatheroptions_getconfig() {
	require_once 'DB.php';
	$sql = "SELECT * FROM weatheroptions LIMIT 1";
	$results= sql($sql, "getAll");
	$tmp = $results[0][4];
	$tmp = eregi_replace('"', '', $tmp);
	$tmp = eregi_replace('>', '', $tmp);
	$res = explode('<', $tmp);
	$results[0][] = trim($res[1]);
	$results[0][] = trim($res[0]);
	return $results[0];
}

function weatheroptions_saveconfig() {

	require_once 'DB.php';

	# clean up
	$engine = mysql_escape_string($_POST['engine']);
	$wgroundkey = mysql_escape_string($_POST['wgroundkey']);
	


	# Make SQL thing
	$sql = "UPDATE `weatheroptions` SET";
	$sql .= " `engine`='{$engine}',";
	$sql .= " `wgroundkey`='{$wgroundkey}'";
	$sql .= " LIMIT 1;";

	sql($sql);
	needreload();
}



function weatherzip_vercheck() {
// compare version numbers of local module.xml and remote module.xml 
// returns true if a new version is available
	$newver = false;
	
	$module_local = weatherzip_xml2array("modules/weatherzip/module.xml");
	$module_remote = weatherzip_xml2array("https://raw.github.com/POSSA/freepbx-weather-by-zip/master/module.xml");
	if ( $foo= empty($module_local) or $bar = empty($module_remote) ) {
		//  if either array is empty skip version check
	}
	else if ( $module_remote[module][version] > $module_local[module][version]) {
		$newver = true;
	}
	return ($newver);

}

//Parse XML file into an array
function weatherzip_xml2array($url, $get_attributes = 1, $priority = 'tag')  {
	$contents = "";
	if (!function_exists('xml_parser_create'))
	{
		return array ();
	}
	$parser = xml_parser_create('');
	if(!($fp = @ fopen($url, 'rb')))
	{
		return array ();
	}
	while(!feof($fp))
	{
		$contents .= fread($fp, 8192);
	}
	fclose($fp);
	xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
	xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
	xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
	xml_parse_into_struct($parser, trim($contents), $xml_values);
	xml_parser_free($parser);
	if(!$xml_values)
	{
		return; //Hmm...
	}
	$xml_array = array ();
	$parents = array ();
	$opened_tags = array ();
	$arr = array ();
	$current = & $xml_array;
	$repeated_tag_index = array ();
	foreach ($xml_values as $data)
	{
		unset ($attributes, $value);
		extract($data);
		$result = array ();
		$attributes_data = array ();
		if (isset ($value))
		{
			if($priority == 'tag')
			{
				$result = $value;
			}
			else
			{
				$result['value'] = $value;
			}
		}
		if(isset($attributes) and $get_attributes)
		{
			foreach($attributes as $attr => $val)
			{
				if($priority == 'tag')
				{
					$attributes_data[$attr] = $val;
				}
				else
				{
					$result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
				}
			}
		}
		if ($type == "open")
		{
			$parent[$level -1] = & $current;
			if(!is_array($current) or (!in_array($tag, array_keys($current))))
			{
				$current[$tag] = $result;
				if($attributes_data)
				{
					$current[$tag . '_attr'] = $attributes_data;
				}
				$repeated_tag_index[$tag . '_' . $level] = 1;
				$current = & $current[$tag];
			}
			else
			{
				if (isset ($current[$tag][0]))
				{
					$current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
					$repeated_tag_index[$tag . '_' . $level]++;
				}
				else
				{
					$current[$tag] = array($current[$tag],$result);
					$repeated_tag_index[$tag . '_' . $level] = 2;
					if(isset($current[$tag . '_attr']))
					{
						$current[$tag]['0_attr'] = $current[$tag . '_attr'];
						unset ($current[$tag . '_attr']);
					}
				}
				$last_item_index = $repeated_tag_index[$tag . '_' . $level] - 1;
				$current = & $current[$tag][$last_item_index];
			}
		}
		else if($type == "complete")
		{
			if(!isset ($current[$tag]))
			{
				$current[$tag] = $result;
				$repeated_tag_index[$tag . '_' . $level] = 1;
				if($priority == 'tag' and $attributes_data)
				{
					$current[$tag . '_attr'] = $attributes_data;
				}
			}
			else
			{
				if (isset ($current[$tag][0]) and is_array($current[$tag]))
				{
					$current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
					if ($priority == 'tag' and $get_attributes and $attributes_data)
					{
						$current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
					}
					$repeated_tag_index[$tag . '_' . $level]++;
				}
				else
				{
					$current[$tag] = array($current[$tag],$result);
					$repeated_tag_index[$tag . '_' . $level] = 1;
					if ($priority == 'tag' and $get_attributes)
					{
						if (isset ($current[$tag . '_attr']))
						{
							$current[$tag]['0_attr'] = $current[$tag . '_attr'];
							unset ($current[$tag . '_attr']);
						}
						if ($attributes_data)
						{
							$current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
						}
					}
					$repeated_tag_index[$tag . '_' . $level]++; //0 and 1 index is already taken
				}
			}
		}
		else if($type == 'close')
		{
			$current = & $parent[$level -1];
		}
	}
	return ($xml_array);
}