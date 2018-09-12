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
                
                $html = <<<EOT
        <!DOCTYPE html><html>
                <head>
                        <meta charset="UTF-8">
						<meta http-equiv="cache-control" content="no-cache" />
						<meta http-equiv="Pragma" content="no-cache" />
						<meta http-equiv="Expires" content="-1" />
                        <title>$caption</title>
                        <link rel="stylesheet" type="text/css" href="../styles/main.css">
                </head>
                <body>
                        <ul id=\"nav\">
                                <li><a href="javascript:tm.newRow()">New</a></li>
                                <li><a href="javascript:tm.deleteRow()">Delete</a></li>
                                <!-- <li><a href="javascript:tm.search()">Search</a></li> -->
								<li style="float: right"><a href="javascript:tm.help()">Help</a></li>
                        </ul>
						
                        <table id="theTable" onclick="tm.clickedCell(event)">
                                <caption>$caption</caption>
                                <thead><tr>
EOT;
                
                
                if(count($data) > 0) {                	
                	// Assemble table header
                	$keys = array_keys($data[0]);
               	 	$values = array_values($data);
                
                	foreach($keys as $k) {
                        if($k === "etag" || $k === "last_modified") {
                                $html .= '<th style="display:none">'.ucwords(str_replace("_", " ", $k)).'</th>';
                        } else {
                                $html .= "<th>".ucwords(str_replace("_", " ", $k))."</th>";
                        }
                	}
                	$html .= "</tr></thead><tbody>";
                	// Assemble table body (first 10 rows)
                	$vcount = count($values);
                	for($i = 0; $i < $vcount; $i++) { 
                		if(is_array($values[$i])) {
                                $html .= "<tr>";

                                $count = 0; // Hide etag and last_modified values
                                foreach($values[$i] as $v) {
                                        if($count > 6) {
                                                $html .= '<td style="display:none">'.$v.'</td>';
                                        } else {
                                                if($count == 4) { // Display checkbox for boolean value
                                                        if($v == "1") {
                                                                $html .= '<td><input type="checkbox" checked="checked"></td>';
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
                }
                
                $html .= <<<EOT
        </tbody></table>
        <div class="pagination">
                <a id="larrow" href="javascript:tm.updatePages('larrow')" style="background:lightgrey;">&laquo;</a>
                <a id="1" href="javascript:tm.updatePages(1)" class="active">1</a>
                                
EOT;
                
                // Default is 10 results per page
                $pages = ceil(count($data)/10);
                $endPage = $count = 2;
                
                while($count <= $pages) {
                        if($count < 4) {
                                $html .= '<a id="'.$count.'" href="javascript:void(0);" onclick="javascript:tm.updatePages('.$count.')">'.$count.'</a>';
                                $endPage = $count;
                        } else {
                                $html .= '<a id="'.$count.'" style="display:none;" href="javascript:void(0);" onclick="javascript:tm.updatePages('.$count.')">'.$count.'</a>';
                        }
                        $count++;
                }
                $html .= "<script>var number_of_pages = ".$pages."; var endPage = ".$endPage.";</script>";
                
                // if($pages > 4)
                //      $html .= '<a id="extra" href="javascript:void(0);" onclick="javascript:pm.updatePages(\'jump\')">...</a>';
                if($pages > 1)
                        $html .= '<a id="rarrow" href="javascript:void(0);" onclick="javascript:tm.updatePages(\'rarrow\')">&raquo;</a>';
                        
                        
                        
                $html .= <<<EOT
        </div>
        <footer>
                <p><a href="https://github.com/cspen/">Source Code</a></p>
				 <p>Created by: Craig Spencer</p>
                <p>Contact: <a href="mailto:craigspencer@modintro.com">craigspencer@modintro.com</a>
					 | <a href="https://linkedin.com/">LinkedIn</a> |
					<a href="">Github</a></p>
        </footer>
                                        
        <div id="overlay">
                <div id="overlaytop">
                        <div id="new" class="overlaycontent" style="display: none;">
                                <h3>New Employee</h3>
								<form id="newRowForm" action="javascript:void(0);">
                                <table class="overlaytable">
                                        <tr><td>Last Name:</td><td><input type="text" id="newlname"></td></tr>
                                        <tr><td>First Name:</td><td><input type="text" id="newfname"></td></tr>
                                        <tr><td>Department:</td><td><select id="newdept">
										</select></td></tr>
                                        <tr><td>Full Time:</td><td><input type="checkbox" id="newftime"></td></tr>
                                        <tr><td>Hire Date:</td><td>
										<select id="newyear">
											
EOT;

			for($i = date("Y") - 75; $i <= date("Y"); $i++) {
				$html  .= '<option>'.$i.'</option>';
			}
			$html .= '</select> - <select id="newmonth">';
			for($i = 1; $i <= 12; $i++) {
				if($i < 10) {
					$html  .= '<option>0'.$i.'</option>';
				} else {
					$html  .= '<option>'.$i.'</option>';
				}
			}
			$html .= '</select> - <select id="newday">';
			for($i = 1; $i <= 31; $i++) {
				if($i < 10) {
					$html  .= '<option>0'.$i.'</option>';
				} else {
					$html  .= '<option>'.$i.'</option>';
				}
			}
			$html .= '</select>';

$html .= <<< EOT

										</td></tr>
                                        <tr><td>Salary:</td><td><input type="text" id="newsalary"></td></tr>
                                </table>
                                <button class="button2" onclick="javascript:tm.cancel()">Cancel</button>
                                <button id="okNew" class="button2" onclick="javascript:tm.newRowSubmit()">OK</button>
								</form>
                        </div>
                        <div id="delete" class="overlaycontent" style="display: none;">
                                <h3>Delete</h3>
                                <table class="overlaytable"><tr><td>EmployeeID: </td><td><input type="text" id="deleteInput"></td></tr></table>
                                <button class="button2" onclick="javascript:tm.cancel()">Cancel</button>
                                <button id="okDelete" class="button2" onclick="javascript:tm.deleteRowSubmit();">OK</button>
                        </div>
                        <div id="search" class="overlaycontent" style="display: none;">
                                <h3>Search</h3>
                                <table class="overlaytable"><tr><td>Search: </td><td><input type="text"></td></tr></table>
                                <button class="button2" onclick="javascript:tm.cancel()">Cancel</button>
                                <button id="okDelete" class="button2">OK</button>
                        </div>
						<div id="help" class="overlaycontent" style="display: none;">
								<div id="helpdisplay" class="scrollable">
                                <h2>Help</h2>
								<hr>
								<h3>Edit a Cell</h3>
								<p>To edit a cell click the cell you wish to edit. If the
								cell is editable an input element will appear. Make the
								desired changes then press the "Enter" button. If the record
								on the server has been updated the change will not occur and
								the row will be highlighted in blue. Press esc to
								abort making any changes.</p>

								<h3>Sort by Column</h3>
								<p>To sort by column single click the column header you wish to
								sort by. Single click the column header again to sort in 
								opposite order.</p>

								<h3>Create a New Record</h3>
								<p>To create a new record click "New" on the menu bar. The new item
								dialog box appears. Complete the "new record" form. Click the submit
								button.</p>

								<h3>Delete a Record</h3>
								<p>To delete a record click "Delete" on the menu bar. The delete item
								dialog box appears. Complete the "delete" form. Click the submit button.</p>
								</div>
                                <button class="button2" onclick="javascript:tm.cancel()">Cancel</button>
                        </div>                                 
                </div>
        </div>
                                        
        <script src="../scripts/main.js"></script>
        </body></html>
EOT;
                        
        		return $html;
        }
}
