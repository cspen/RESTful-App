<?php

class Encode {
	
	/**
	 *
 	 *
	 */
	public static function xml_encode($data, $type) {
		$type = ucfirst($type); // Upper case first letter		
	
		$xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
		$xml .= '<'.$type.'>'."\n";
		$type = substr($type, 0, strlen($type)-1); // Remove plural
		
		foreach($data as $key => $value) {
			if(is_array($value)) {
				$xml .= '<'.$type.'>'."\n";
				foreach($value as $val_key => $val) {
					$xml .= "\t".'<'.$val_key.'>'.$val.'</'.$val_key.'>'."\n";
				}
				$xml .= '</'.$type.'>'."\n";
			} else {

				$xml .= '<'.$type.'>';
				$xml .= $value;
				$xml .= '</'.$type.'>'."\n";


				/* OLD CODE 
				$xml .= '<'.$type.'>'."\n";
				$xml .= "\t".'<'.$key.'>'.$value.'</'.$key.'>'."\n";
				$xml .= '</'.$type.'>'."\n";
				*/
			}
		}
		$xml .= '</'.$type.'s>'."\n";

		return $xml;
	}
	
	public static function html_encode($data, $caption) {
		$keys = array_keys($data[0]);
		$values = array_values($data);
		
		$html = <<<EOT
	<!DOCTYPE html><html>
		<head>
			<meta charset="UTF-8">
			<title>$caption</title>
	 		<link rel="stylesheet" type="text/css" href="../styles/style.css">
		</head>
		<body>
			<ul id=\"nav\">
				<li><a href="javascript:void(0)">New</a></li>
				<li><a href="javascript:void(0)">Delete</a></li>
				<li><a href="javascript:void(0)">Search</a></li>
			</ul>
			<table id="theTable" onclick="tm.clickedCell(event)">
				<caption>$caption</caption>
				<thead><tr>
EOT;
		
		// Assemble table header
		foreach($keys as $k) {
			if($k === "etag" || $k === "last_modified") {
				$html .= '<th style="display:none">'.ucwords(str_replace("_", " ", $k)).'</th>';
			} else {
				$html .= "<th>".ucwords(str_replace("_", " ", $k))."</th>";
			}
		}
		$html .= "</tr></thead><tbody>";
		// Assemble table body (first 10 rows)
		for($i = 0; $i < 10; $i++) {
			if(is_array($values[$i])) {
				$html .= "<tr>";

				$count = 0; // Hide etag and last_modified values
				foreach($values[$i] as $v) {
					if($count > 6) {
						$html .= '<td style="display:none">'.$v.'</td>';
					} else {
						if($count == 4) { // Display checkbox for boolean value
							if($v) {
								$html .= '<td><input type="checkbox" checked="true"></td>';
							} else {
								$html .= '<td><input type="checkbox"></td>';
							}
						} elseif($count == 6) {
							$html .= '<td>$'.number_format($v).'</td>';
						} else {
							$html .= "<td>".$v."</td>";
						}
					}
					$count++;
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
				$html .= '<a id="'.$count.'" href="javascript:void(0);" onclick="javascript:pm.updatePages('.$count.')">'.$count.'</a>';
				$endPage = $count;
			} else {
				$html .= '<a id="'.$count.'" style="display:none;" href="javascript:void(0);" onclick="javascript:pm.updatePages('.$count.')">'.$count.'</a>';
			}
			$count++;
		}
		$html .= "<script>var number_of_pages = ".$pages."; var endPage = ".$endPage.";</script>";
		
		// if($pages > 4)
		// 	$html .= '<a id="extra" href="javascript:void(0);" onclick="javascript:pm.updatePages(\'jump\')">...</a>';
		if($pages > 1)
			$html .= '<a id="rarrow" href="javascript:void(0);" onclick="javascript:pm.updatePages(\'rarrow\')">&raquo;</a>';
			
			
			
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
			<div id="search" class="overlaycontent" style="display: none;">
				<h3>Search</h3>
				<table class="overlaytable"><tr><td>Search: </td><td><input type="text"></td></tr></table>
				<button id="cancel" class="button2">Cancel</button>
				<button id="okDelete" class="button2">OK</button>
			</div>					
		</div>
	</div>
					
	<script src="../scripts/script.js"></script>
	</body></html>
EOT;
			
			return $html;
	}
}