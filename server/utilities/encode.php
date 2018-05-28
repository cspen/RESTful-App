<?php 
/**
 * Functions to encode array data into
 * xml, html.
 *
 * @author Craig Spencer craigspencer@modintro.com
 */

/**
 * Convert data stored in an array into a string of
 * xml.
 *
 * @param array	data to be converted to xml
 * @param String the type of data (name of collection)
 * @return String
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
	return $xml;
}


/**
 * Convert data stored in an array into an html document 
 * stored in a string.
 *
 * @param String $data data to be converted to HTML
 * @param String $caption caption for the HTML table
 * @return String
 */
function html_encode($data, $caption) {
	$keys = array_keys($data[0]);
	$values = array_values($data);
	
$html = <<<EOT
	<!DOCTYPE html><html>
		<head>
			<meta charset="UTF-8">
			<title>Title of the document</title>
	 		<link rel="stylesheet" type="text/css" href="style.css">
		</head>
		<body>
			<ul id=\"nav\">
				<li><a href="javascript:void(0)">New</a></li>
				<li><a href="javascript:void(0)">Delete</a></li>
				<li><a href="javascript:void(0)">Refresh</a></li>
			</ul>
			<table id="theTable" onclick="tm.clickedCell(event)">
				<caption>$caption</caption>
				<thead><tr>
EOT;
	
	// Assemble table header
	foreach($keys as $k) {
		$html .= "<th>".ucwords(str_replace("_", " ", $k))."</th>";
	}
	$html .= "</tr></thead><tbody>";
	// Assemble table body (first 10 rows)
	for($i = 0; $i < 10; $i++) {
		if(is_array($values[$i])) {
			$html .= "<tr>";
			foreach($values[$i] as $v) {
				$html .= "<td>".$v."</td>";
			}
			$html .= "</tr>";
		}
	}
	
$html .= <<<EOT
	</tbody></table>
	<div class="pagination">
  		<a id="larrow" href="javascript:pm.updatePages('larrow')" style="background:lightgrey;">&laquo;</a>
  		<a id="1" href="javascript:pm.updatePages(1)" class="active">1</a>
  		
EOT;

	// Default is 10 results per page
	$pages = ceil(count($data)/10);
	$endPage = $count = 2;
	
	while($count <= $pages) {
		if($count < 4) {
			$html .= '<a id="'.$count.'" href="javascript:pm.updatePages('.$count.')">'.$count.'</a>';
			$endPage = $count;
		} else {
			$html .= '<a id="'.$count.'" style="display:none;" href="javascript:pm.updatePages('.$count.')">'.$count.'</a>';			
		}
		$count++;
	}
	$html .= "<script>var number_of_pages = ".$pages."; var endPage = ".$endPage.";</script>";
	
	// if($pages > 4)
	// 	$html .= '<a id="extra" href="javascript:pm.updatePages(\'jump\')">...</a>';
	if($pages > 1)
		$html .= '<a id="rarrow" href="javascript:pm.updatePages(\'rarrow\')">&raquo;</a>';

	

$html .= <<<EOT
	</div>
	<footer>
		<p>Created by: Craig Spencer</p>
		<p>Contact: <a href="mailto:craigspencer@modintro.com">craigspencer@modintro.com</a></p>
	</footer>

	<div id="overlay">
		<div id="overlaytop">
			<div id="new" class="overlaycontent" style="display: none;">
				<h3>New Employee</h3>
				<table class="overlaytable">				
					<tr><td>Last Name:</td><td><input type="text" id="ln"></td></tr>
					<tr><td>First Name:</td><td><input type="text" id="fn"></td></tr>
					<tr><td>Department:</td><td><select id="dep"></select></td></tr>
					<tr><td>Full Time:</td><td><input type="checkbox"></td></tr>
					<tr><td>Hire Date:</td><td><input type="text"></td></tr>
					<tr><td>Salary:</td><td><input type="text"></td></tr>
				</table>	
				<button id="cancel" class="button2">Cancel</button>
				<button id="okNew" class="button2">OK</button>		
			</div>
			<div id="delete" class="overlaycontent" style="display: none;">
				<h3>Delete</h3>
				<table class="overlaytable"><tr><td>EmployeeID: </td><td><input type="text"></td></tr></table>
				<button id="cancel" class="button2">Cancel</button>
				<button id="okDelete" class="button2">OK</button>
			</div>
			
		</div>
	</div>

	<script src="script.js"></script>
	</body></html>
EOT;
	
	return $html;
}



?>