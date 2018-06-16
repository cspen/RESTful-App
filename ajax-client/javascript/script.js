/**
 * Table Manager makes an HTML table editable.
 * 
 * Created By: Craig Spencer
 * Date: May 14, 2018
 * Last Modified: May 17, 2018
 */

// Table Manager
var tm = tm || {};

tm.glbs = {
		oval : null,	// The original value of the cell
		flag : false,	// For determining if element already exists
		elem : null,	// The element
		col : -1,	// Table column
		row : 0		// Table row
}

tm.clickedCell = function (e) {	
	// Update the table
	if(!tm.glbs.flag) {
		var table = document.getElementById("theTable");
		
		var elem = e.target;
		tm.glbs.row = elem.parentNode.rowIndex;
		tm.glbs.col = elem.cellIndex;
		
		// Row 0 is the table column headers. Columns 0 and 5 are uneditable
		if(tm.glbs.row != 0 && tm.glbs.col != 0 && tm.glbs.col != 5) { 		
		
			tm.glbs.oval = e.target.textContent;
			tm.glbs.elem = document.createElement("INPUT");
			
			// Get current content in cell and put in text input
			if(tm.glbs.col == 6) {
				tm.glbs.elem.value = tools.strip_num_formatting(table.rows[tm.glbs.row].cells[tm.glbs.col].textContent);
			} else {
				tm.glbs.elem.value = table.rows[tm.glbs.row].cells[tm.glbs.col].textContent;
			}
			tm.glbs.elem.type = "text";
			tm.glbs.elem.id = "edit";
			tm.glbs.elem.addEventListener("keyup", function(event) {
			    event.preventDefault();
			    if (event.keyCode === 13) { 
			    	// Validate edit
				var value = this.value;

				// Check that name columns do not contain numbers
				if(tm.glbs.col == 1 || tm.glbs.col == 2) {
					
				} else if(tm.glbs.col = 6) { // Check that salary is numeric
					if(isNaN(parseFloat(value)) && !isFinite(value)) {
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
			tm.glbs.elem.select();
			tm.glbs.flag = true;
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
	
	ajax.func("GET", "http://modintro.com/employees/?page=" + nextPage + "&pagesize=10",  pm.ajax.func1, page);
};

pm.ajax = {} || ajax;
pm.ajax.func1 = function(xhttp, page) { 
	
	// Check for error
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
					if(j == 6) { // Format salary
						text = document.createTextNode(tools.format_nondecimal_currency(jrow[keys[j]]));
					} else {
						text = document.createTextNode(jrow[keys[j]]);
					}
					newCell.appendChild(text);
					if(j > 6) {
						newCell.style.display = "none";
					}
				}
			}
		}		

	// Update page navigation
	if(page === "rarrow") {
		// Check if next page exists
		var next = document.getElementById(pm.glbs.currentPage+1);
		if(next != null) {
			document.getElementById(pm.glbs.currentPage).classList.remove('active');
			pm.glbs.currentPage++;
			document.getElementById(pm.glbs.currentPage).classList.add('active');
		}
	} else if(page === "larrow") {
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

var ajax = ajax || {};
ajax.func = function(method, url, callbackFunc, data) { 
	var xmlhttp = new XMLHttpRequest();
	xmlhttp.onreadystatechange = function() {
	    if (this.readyState == 4 && this.status == 200) {
	    	callbackFunc(this, data);
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
