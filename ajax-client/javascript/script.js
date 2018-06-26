/**
 * Table Manager makes an HTML table editable.
 * 
 * Created By: Craig Spencer
 * Date: May 14, 2018
 * Last Modified: June 21, 2018
 */

// Table Manager
var tm = tm || {};

tm.glbs = {
		oval : null,	// The original value of the cell
		flag : false,	// For determining if element already exists
		elem : null,	// The element
		col : -1,	// Table column
		row : 0,	// Table row
		// The base url for the data in the table
		url : "http://modintro.com/employees/",	

		sortByCol : "employeeID",	// Default
		sortOrder: "asc"		// Default sort order (false = descending order)
}

tm.clickedCell = function (e) {	
	// Update the table
	if(!tm.glbs.flag) {
		var table = document.getElementById("theTable");
		
		var elem = e.target;
		tm.glbs.row = elem.parentNode.rowIndex;
		tm.glbs.col = elem.cellIndex;
		
		// Row 0 is the table column headers. Columns 0 and 5 are uneditable
		if(tm.glbs.row != 0 && tm.glbs.col != 0 && tm.glbs.col != 5 && e.target.tagName != "CAPTION") { 		
					
			if(tm.glbs.col != 4 && (typeof tm.glbs.col != 'undefined')) {
				if(tm.glbs.col == 3) {
					tm.glbs.elem = tools.createDepartmentSelect("Cheers");					
				} else {
					tm.glbs.elem = document.createElement("INPUT");
					tm.glbs.elem.type = "text";
				}
			} else { 
				// The table cell was clicked - not the checkbox
				return;
			}
			
			// Get current content in cell and put in text input
			tm.glbs.oval = e.target.textContent;
			if(tm.glbs.col != 6) { 
				if(e.target.tagName != "INPUT") { // Check that input element not already present
					tm.glbs.elem.value = table.rows[tm.glbs.row].cells[tm.glbs.col].textContent;
				} else {
					alert("CHECK BOX");
					return;			
				}			
			} else {
				tm.glbs.elem.value = tools.strip_num_formatting(table.rows[tm.glbs.row].cells[tm.glbs.col].textContent);				
			}
			
			tm.glbs.elem.id = "edit";

			tm.glbs.elem.addEventListener("keydown", function(event) { 
			   event.stopImmediatePropagation();
			   event.preventDefault();

			   if (event.keyCode == 13) {  
			    	// Validate edit 
				var value = this.value;

				// Check that name columns do not contain numbers
				if(tm.glbs.col == 1 || tm.glbs.col == 2) {
					
				} else if(tm.glbs.col == 6) { // Check that salary is numeric
					if(isNaN(parseFloat(value)) && !isFinite(value)) {
						// NEED TO CHANGE TO DIALOG BOX
						alert("Salary must be a numeric value");
						return;
					}
				}
					
				// If valid edit, update server
			    	// tm.AJAX(value);
				
				// Update the table
				table.rows[tm.glbs.row].cells[tm.glbs.col].removeChild(this);

				// Convert Salary column to currency format
			    	if(tm.glbs.col === 6) {
					if(tools.isNumber(value)) {
						value = tools.format_nondecimal_currency(value);
					}
				}
			    	table.rows[tm.glbs.row].cells[tm.glbs.col].textContent = value;
			    	tm.glbs.flag = false;
			    	tm.glbs.column = -1;
			    	tm.glbs.row = 0;
			    }
			});

			table.rows[tm.glbs.row].cells[tm.glbs.col].innerHTML = "";
			table.rows[tm.glbs.row].cells[tm.glbs.col].appendChild(tm.glbs.elem);
			tm.glbs.elem.focus();
			// tm.glbs.elem.select();
			tm.glbs.flag = true;
		} else if(tm.glbs.row == 0) { // Column header clicked
			// Sort by column
			var colHead = table.rows[0].cells[tm.glbs.col].innerHTML; 

			// Translate column header into db row item
			if(colHead === "EmployeeID") {
				colHead = "employeeID";
			} else {
				colHead = colHead.toLowerCase();
				colHead = colHead.replace(/ /g,"_");
			}

			if(tm.glbs.sortByCol === colHead) {
				// Same header - change sort order
				if(tm.glbs.sortOrder === "asc") {
					tm.glbs.sortOrder = "desc";
				} else {
					tm.glbs.sortOrder = "asc";
				}
			} else {
				tm.glbs.sortByCol = colHead;
				tm.glbs.sortOrder = "asc";
			}

			// Make ajax request 
			var request = tm.glbs.url + "?page=" + pm.glbs.currentPage + "&pagesize=10&sort=" + colHead + "&order=" + tm.glbs.sortOrder;
			console.log(request);
			ajax.request("GET", request, pm.ajax.func1, pm.glbs.currentPage);
		}	
	}
};





// Page load module
(function() {
	var currentDiv;
	// Capture page link clicks
	window.onclick = function(e) {
		// e.stopPropagation();
		// e.preventDefault();
		
		var clicked = e.target.innerHTML;		
		switch(clicked) {
			case "New":
				var pop = document.getElementById('overlay');
				currentDiv = document.getElementById('new');
				pop.style.display = "block";
				currentDiv.style.display = "block";
				break;
			case "Search":
				var pop = document.getElementById('overlay');
				currentDiv = document.getElementById('search');
				pop.style.display = "block";
				currentDiv.style.display = "block";
				break;
			case "Delete":
				var pop = document.getElementById('overlay');
				currentDiv = document.getElementById('delete');
				pop.style.display = "block";
				currentDiv.style.display = "block";
				break;
			case "Cancel":
				var pop = document.getElementById('overlay');
				pop.style.display = "none";
				currentDiv.style.display = "none";
				break;
			case "OK":
				console.log("YOU CLICKED OK " + e.target.id);
				
		}		
	};
}());






// Page manager for managing multiple page results
var pm = pm || {};

pm.glbs = {
	currentPage: 1,  /* Default */
	startPage: 1
}
pm.updatePages = function(page) { 
	// Determine which "page" to display
	var nextPage = 0;
	if(page === "rarrow") {
		nextPage = pm.glbs.currentPage + 1;
	} else if(page === "larrow") {
		nextPage = pm.glbs.currentPage - 1;
	} else {
		nextPage = page;
	}
	
	var request = tm.glbs.url + "?page=" + nextPage + "&pagesize=10&sort=" + tm.glbs.sortByCol + "&order=" + tm.glbs.sortOrder
	ajax.request("GET", request,  pm.ajax.func1, page);
};

pm.ajax = {} || ajax;
pm.ajax.func1 = function(xhttp, page) {  console.log("AJAX METHOD");
	
	// TO-DO: Check for error before modifying table
 	// alert(xhttp.responseText);

	// Update the data table 
		// Remove existing rows
		var theTable = document.getElementById('theTable');

		// Remove any active input elements in the table
		tm.glbs.flag = false;

		// Row zero is the column headings
		while(theTable.rows.length > 1) {
			theTable.deleteRow(1);
		}

		// Add new rows
		var obj = JSON.parse(xhttp.responseText);
		if(Array.isArray(obj)) {
			var obLength = obj.length;
			for(var i = 0; i < obLength; i++) {
				var newRow = theTable.insertRow(theTable.rows.length);
				var jrow = obj[i]; 
				var length = Object.keys(jrow).length;
				var keys = Object.keys(jrow); 
				for (var j = 0; j < length; j++) {
    					// Add row to table						
					var newCell = newRow.insertCell(-1);
					var text = null;
					if(j != 4 && j != 6) {
						text = document.createTextNode(jrow[keys[j]]);
					} else if(j == 4) {
						text = document.createElement('INPUT');
						text.type = "checkbox";
						if(jrow[keys[j]] == "1") {
							text.checked = "true";							
						} 
					} else if(j == 6) { // Format salary
						text = document.createTextNode(tools.format_nondecimal_currency(jrow[keys[j]]));
					}
					newCell.appendChild(text);
					if(j > 6) {
						newCell.style.display = "none";
					}
				}
			}
		}		

	// Update page navigation
	if(page === "rarrow") { // Right arrow
		// Check if next page exists
		var next = document.getElementById(pm.glbs.currentPage+1);
		if(next != null) {
			document.getElementById(pm.glbs.currentPage).classList.remove('active');
			pm.glbs.currentPage++;
			document.getElementById(pm.glbs.currentPage).classList.add('active');
		}
	} else if(page === "larrow") { // Left arrow
		var prev = document.getElementById(pm.glbs.currentPage-1);
		if(prev != null) {
			document.getElementById(pm.glbs.currentPage).classList.remove('active');
			pm.glbs.currentPage--;
			document.getElementById(pm.glbs.currentPage).classList.add('active');		
		}
	} else if(page === "jump") {
		// Not implemented - intended to be a multiple page navigation
	} else if(!isNaN(page)) { // Clicked a number
		if(page !== pm.glbs.currentPage) {
			document.getElementById(pm.glbs.currentPage).classList.remove('active');
			pm.glbs.currentPage = page;
			document.getElementById(pm.glbs.currentPage).classList.add('active');			
		}
	}
	
	if(pm.glbs.currentPage > endPage) {
		document.getElementById(pm.glbs.startPage).style.display = "none";
		pm.glbs.startPage++;
		endPage++;
		document.getElementById(endPage).style.display = "block";		
	} else if(pm.glbs.currentPage < pm.glbs.startPage) {
		document.getElementById(endPage).style.display = "none";
		pm.glbs.startPage--;
		endPage--;
		document.getElementById(pm.glbs.startPage).style.display = "block";
	}
	
	// Grey out arrow keys when reach either start or end of pages
	if(pm.glbs.currentPage > 1) {
		document.getElementById('larrow').style.background = "white";
	} else {
		document.getElementById('larrow').style.background = "lightgrey";
	}
	
	if(pm.glbs.currentPage < number_of_pages) {
		document.getElementById('rarrow').style.background = "white";
	} else {
		document.getElementById('rarrow').style.background = "lightgrey";
	}	
};





// Contact the server
var ajax = ajax || {};
ajax.request = function(method, url, callbackFunc, data) { console.log("AJAX " + url);
	var xmlhttp = new XMLHttpRequest();
	xmlhttp.onreadystatechange = function() {
	    if (this.readyState == 4 && this.status == 200) {
	    	callbackFunc(this, data);
	    } else {
		// alert("Ready State: " + this.readyState + " Status: " + this.status);
	    }
	};
	xmlhttp.open(method, url, true);
	xmlhttp.setRequestHeader("Accept", "application/json");
	xmlhttp.send();
};







/**
 * Helper methods that didn't fit in anywhere else
 */
var tools = tools || {};
tools.isNumber = function(num) {
	if(isNaN(parseFloat(num)) && !isFinite(num)) {
		return false;
	}
	return true;
};
tools.format_nondecimal_currency = function(num) {
	var len = num.length;
	var newNum = "";
	for(var i = 0; i < len; i++) {
		if((len - i)%3 == 0 && i != 0)
			newNum += ",";
		newNum += num.charAt(i);
	}
	return "$" + newNum;
};

tools.strip_num_formatting = function(num) {
	var len = num.length;
	var newNum = "";
	for(var i = 0; i < len; i++) {
		var n = num.charAt(i);
		if(tools.isNumber(n))
			newNum += n;
	}
	return newNum;
};

tools.createDepartmentSelect = function(current) {
	var select = document.createElement("SELECT");

	// TO-DO: Make ajax call for values to populate list with
	

	for (var i = 0; i < 5; i++) {
    		var option = document.createElement("option");
    		option.value = "Option " + i;
    		option.text = "Option " + i;
    		select.appendChild(option);
	}

	return select;
};
