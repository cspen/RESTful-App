<?php 
/**
 * Functions to encode array data into
 * xml, html, or json. 
 *
 * THIS IS NOT A GENERAL ENCODER. These functions are designed
 * to accept database result sets in array form.
 */


function xml_encode($data, $type) {
	$type = ucfirst($type);
	$xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
	
	foreach($data as $key => $value) {
		if(is_array($value)) {
			$xml .= '<'.$type.'>'."\n";
			foreach($value as $val_key => $val) {
				$xml .= "\t".'<'.$val_key.'>'.$val.'</'.$val_key.'>'."\n";
			}
			$xml .= '</'.$type.'>'."\n";
		} else {
			$xml .= '<'.$type.'>'."\n";
			$xml .= "\t".'<'.$key.'>'.$value.'</'.$key.'>'."\n";
			$xml .= '</'.$type.'>'."\n";
		}		
	}	
	echo $xml;
}

function html_encode($data, $caption) {
	$keys = array_keys($data[0]);
	$values = array_values($data);
	
	$html = "<!DOCTYPE html><html><head><meta charset=\"UTF-8\">";
	$html .= "<title>Title of the document</title></head><body>";
	$html .= "<table><caption>".$caption."</caption>";
	$html .= "<tr>";
	// Assemble table header
	foreach($keys as $k) {
		$html .= "<th>".ucwords(str_replace("_", " ", $k))."</th>";
	}
	$html .= "</tr>";
	// Assemble table body
	foreach($values as $key => $value) {
		if(is_array($value)) {
			$html .= "<tr>";
			foreach($value as $v) {
				$html .= "<td>".$v."</td>";
			}
			$html .= "</tr>";
		} else {
		}
	}
	
	$html .= "</table>";
	$html .= "</body></html>";
	
	echo $html;
}



?>