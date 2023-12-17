/**
 * Javascript Application: 
 *      Makes an HTML functional/editable
 *      	Update individual cells
 *      	Delete rows
 *      	Add new rows
 *      	Sort by column
 *      
 * By Craig Spencer <craigspencer@modintro.com>
 * November 2018
 */

/* The Table Manager */
var tm = tm || {};
tm.globals = {
		col : -1,			// Table column
		row : 0,			// Table row
		active : null,			// Is a cell currently being edited
		url : "https://modintro.com/",	
		cbox : null,
		sortByCol : "employeeID",
		sortOrder : "asc",
		currentPage : 1,
		startPage : 1,
		currentDiv : null,		// For pop-overs
		deptList : null,		// Department list for new row form
		dlEtag : null,			// Etag for department list
		dlLastMod : null,		// Last-Modified for department list
}

/**
 * 
 */
tm.clickedCell = function(e) {
	if(tm.globals.active == null) {
		// Get the clicked table cell
		var elem = e.target;
        	tm.globals.row = elem.parentNode.rowIndex;
        	tm.globals.col = elem.cellIndex;
        
        	// Column header clicked - sort by column
        	if(tm.globals.row == 0) {
        		var table = document.getElementById('theTable');
        		var colHead = table.rows[0].cells[tm.globals.col].innerHTML;
        	
        		// Translate column header into db row item
            		if(colHead === "EmployeeID") {
                    		colHead = "employeeID";	// Server requires it this way
           		} else {
                    		colHead = colHead.toLowerCase();
                    		colHead = colHead.replace(/ /g,"_");
            		}

            		if(tm.globals.sortByCol === colHead) {
                    		// Same header - change sort order
                    		if(tm.globals.sortOrder === "asc") {
                           		tm.globals.sortOrder = "desc";
                    		} else {
                            		tm.globals.sortOrder = "asc";
                    		}
           		} else {
                    		tm.globals.sortByCol = colHead;
                    		tm.globals.sortOrder = "asc";
           		 }
            
            		// Make ajax request 
            		var request = tm.globals.url + "employees/?page=" + tm.globals.currentPage + "&pagesize=10&sort=" +
            			colHead + "&order=" + tm.globals.sortOrder;
			ajax.request("GET", request, tm.colCallBack, tm.globals.currentPage, null, null);        	 
        		return;
        	} 
        
        	// Uneditable columns and rows
        	if(tm.globals.col == 0 || tm.globals.col == 5 || e.target.tagName == "CAPTION")
        		return;
        
        	// Handle checkbox click - full time column
        	if(e.target.type == "checkbox") {
        		e.stopImmediatePropagation();
        		e.preventDefault();
        		tm.globals.row = elem.parentNode.parentNode.rowIndex;
        		tm.globals.col = elem.parentNode.cellIndex;
        		tm.globals.cbox = e.target;
        	
        		var table = document.getElementById('theTable');
        		var empId = table.rows[tm.globals.row].cells[0].textContent;  
        		var data = tm.createJSONString(table, tm.globals.row, "fulltime", e.target.checked);
        		var etag = table.rows[tm.globals.row].cells[7].textContent;
            		var lsmod = table.rows[tm.globals.row].cells[8].textContent; 
        		ajax.request("PUT", tm.globals.url+"employees/"+empId,
        			tm.checkboxCallback, data, etag, lsmod);
   	
        		return;
        	} 
		// If made it this far, not a checkbox
		// Disable all checkboxes
		tools.disableCheckboxes();
        
        	// Create the element for editing
        	var curVal = e.target.textContent;
        	if(tm.globals.col == 3) {
        		var url = "http://localhost/GEM/rest/departments/";
        		tm.createSelectElement(url, curVal);
       		} else {
        		if(tm.globals.col != 4)
        			tm.createInputElement(curVal);
        	}        
        	tm.globals.active = curVal;
	} 
};

/**
 * Create a text input element.
 */
tm.createInputElement = function(content) {
	// Create the element
	var input = document.createElement("INPUT");
	input.type = "text";
        input.onblur = tm.escape; 

    	if(tm.globals.col == 6) {
    		input.value = tools.strip_num_formatting(content);
    		input.id = "salary";
    	} else { 
    		if(tm.globals.col == 1) {
    			input.id = "lastName";
    		} else if(tm.globals.col == 2) {
    			input.id = "firstName";
    		}
    		input.value = content;
    	}
	
    	tm.setElement(input);    
};

/**
 * Create a select list with the selected option.
 */
tm.createSelectElement = function(url, selected) { 
	ajax.request("GET", tm.globals.url + "departments/",
			tm.createSelectCallback, selected, null, null);
};
tm.createSelectCallback = function(serverResponse, selected) {
	try {
		var items = JSON.parse(serverResponse.responseText);	
		var select = document.createElement("SELECT");
		select.onblur = tm.escape;
		select.id = "department";
		var length = items["Departments"].length;
		for (var i = 0; i < length; i++) {
            var option = document.createElement("option");
            option.value = items['Departments'][i];
            option.text = items['Departments'][i];
            if(items['Departments'][i] == selected) {
            	option.selected = true;
            }
            select.appendChild(option);
		}
		tm.setElement(select);
		select.addEventListener("keydown", tm.editorEventListener);    
	} catch(e) { alert(e);
		// TO-DO: Display error message		
	}
};

tm.setElement = function(elem) {
	var table = document.getElementById('theTable');
	elem.addEventListener("keydown", tm.editorEventListener);    
    tools.removeChildren(table.rows[tm.globals.row].cells[tm.globals.col]);
    table.rows[tm.globals.row].cells[tm.globals.col].appendChild(elem);
    elem.focus();
    
    if(elem.tagName == "INPUT") {
    	elem.select();
    }    
};

/**
 * Event listener for table.
 */
tm.editorEventListener = function(event) {
	if (event.keyCode == 13) {  // Enter key
    		event.stopImmediatePropagation();
        	event.preventDefault();       

        	// Validate edit 
        	var value = this.value;
        	var table = document.getElementById('theTable');
        
        	if(value == "" || value === tm.globals.active) {
        		// If input field is empty or hasn't changed, return the original content
        		table.rows[tm.globals.row].cells[tm.globals.col].textContent = tm.globals.active;
        		tm.globals.row = -1;
        		tm.globals.col = 0;
        		tm.globals.active = null;
			tools.enableCheckboxes();
        		return;
        	}        
        
        	if(tm.globals.col == 1 || tm.globals.col == 2) {
        		var exp = /[!"\#$%&'()*+,\-./:;<=>?@\[\\\]^_`{|}~0-9]/;
        		if(value.match(exp)) {
        			// TO-DO: NEET TO CHANGE TO DIALOG BOX
        			alert("First and last name fields must contain only letters");
        			return;
        		}
        	} else if(tm.globals.col == 6) { // Check that salary is numeric
                	if(isNaN(parseFloat(value)) && !isFinite(value)) {
                        	// TO-DO: NEED TO CHANGE TO DIALOG BOX
                        	alert("Salary must be a numeric value");
                        	return;
                	}
        	}
        
        	var empId = table.rows[tm.globals.row].cells[0].textContent;
        
        	var data = null;
        	if(this.id == "department") { // Select list
        		var v = this.options[this.selectedIndex].value;
        		data = tm.createJSONString(table, tm.globals.row, this.id, v);
        	} else {
        		data = tm.createJSONString(table, tm.globals.row, this.id, this.value);
        	}
        
        	// Get etag and last_modified header values from table (hidden columns)
        	var etag = table.rows[tm.globals.row].cells[7].textContent;
        	var lsmod = table.rows[tm.globals.row].cells[8].textContent;
	        
        	ajax.request("PUT", tm.globals.url+"employees/"+empId,
        		tm.editorEventListenerCallback, data, etag, lsmod);
    	} else if(event.keyCode == 27) {
    		// Escape key pressed
		tm.escape();

       	}
};
/**
 * Method to escape or cancel an input.
 */
tm.escape = function() { 
	var table = document.getElementById('theTable');
    	var node = table.rows[tm.globals.row].cells[tm.globals.col];
	node.removeChild(node.firstChild);
	node.textContent = tm.globals.active;
	tm.globals.active = null;
	tools.enableCheckboxes();
};
tm.editorEventListenerCallback = function(serverResponse, data, url) { 
	if(serverResponse.status == 200 || serverResponse.status == 204) {
		var table = document.getElementById('theTable');
         
        	// Update the table
		var node = table.rows[tm.globals.row].cells[tm.globals.col];
		var value = node.firstChild.value;
		node.removeChild(node.firstChild);

        	// Convert Salary column to currency format
        	if(tm.globals.col === 6) {
                	if(tools.isNumber(value)) {
                        	value = tools.format_nondecimal_currency(value);
                	}
        	}
        	// value = tools.capitalize(value);
        	// value = value.charAt(0).toUpperCase() + value.slice(1);
        	// table.rows[tm.globals.row].cells[tm.globals.col].textContent = value;
        
        	// UPDATE ETAG AND LAST MODIFIED FEILDS
	
       		ajax.request("GET", url, tm.updateRow, null, null, null);
	} else {
		if(serverResponse.status == "412") {
			alert("Unable to update - More recent copy on server");
			// Update the row
			ajax.request("GET", url, tm.updateRow, null, null, null);
		} else {
			// Do nothing
		}
	}
	tools.enableCheckboxes();
};
tm.checkboxCallback = function(serverResponse, data, url) { 
	if(serverResponse.status == "200" || serverResponse.status == "204") {
		if(tm.globals.cbox.checked) {
			tm.globals.cbox.checked = false;
		} else {
			tm.globals.cbox.checked = true;
		}
		
		 // UPDATE ETAG AND LAST MODIFIED FEILDS
		ajax.request("GET", url, tm.updateHeaderFields, null, null, null);

	} else { 
		// Check for 412 status
		if(serverResponse.status == "412") { 
			// Update the row
			alert("Unable to update - More recent copy on server");
			ajax.request("GET", url, tm.updateRow, null, null, null);
		} else {
			alert("STATUS " + serverResponse.status);
		}
	}
	return;
};
tm.colCallBack = function(xhttp, page) {  
		if(xhttp.status == "204") {
			return;
		}
    // TO-DO: Check for error before modifying table
    // alert(xhttp.responseText);

    // Update the data table 
            // Remove existing rows
            var theTable = document.getElementById('theTable');

            // Remove any active input elements in the table
            tm.globals.active = null;

            // Row zero is the column headings
            while(theTable.rows.length > 1) {
                    theTable.deleteRow(1);
            }

            // Add new rows
            // alert(xhttp.repsonseText + " STATUS: " + xhttp.status);
            var obj = JSON.parse(xhttp.responseText);
            if(Array.isArray(obj.Employees)) {
                    var obLength = obj.Employees.length;
                    for(var i = 0; i < obLength; i++) {
                            var newRow = theTable.insertRow(theTable.rows.length);
                            var jrow = obj.Employees[i]; 
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
            var next = document.getElementById(tm.globals.currentPage+1);
            if(next != null) {
                    document.getElementById(tm.globals.currentPage).classList.remove('active');
                    tm.globals.currentPage++;
                    document.getElementById(tm.globals.currentPage).classList.add('active');
            }
    } else if(page === "larrow") { // Left arrow
    	var prev = document.getElementById(tm.globals.currentPage-1);
            if(prev != null) {
                    document.getElementById(tm.globals.currentPage).classList.remove('active');
                    tm.globals.currentPage--;
                    document.getElementById(tm.globals.currentPage).classList.add('active');           
            }
    } else if(page === "jump") {
            // Not implemented - intended to be a multiple page navigation
    } else if(!isNaN(page)) { // Clicked a number
            if(page !== tm.globals.currentPage) {
                    document.getElementById(tm.globals.currentPage).classList.remove('active');
                    tm.globals.currentPage = page;
                    document.getElementById(tm.globals.currentPage).classList.add('active');                   
            }
    }
    
    if(tm.globals.currentPage > endPage) {
            document.getElementById(tm.globals.startPage).style.display = "none";
            tm.globals.startPage++;
            endPage++;
            document.getElementById(endPage).style.display = "block";               
    } else if(tm.globals.currentPage < tm.globals.startPage) {
            document.getElementById(endPage).style.display = "none";
            tm.globals.startPage--;
            endPage--;
            document.getElementById(tm.globals.startPage).style.display = "block";
    }
    
    // Grey out arrow keys when reach either start or end of pages
    if(tm.globals.currentPage > 1) {
            document.getElementById('larrow').style.background = "white";
    } else {
            document.getElementById('larrow').style.background = "lightgrey";
    }
    
    if(tm.globals.currentPage < number_of_pages) {
            document.getElementById('rarrow').style.background = "white";
    } else {
            document.getElementById('rarrow').style.background = "lightgrey";
    }
};

/**
 * Called when row is updated.
 */
tm.updateHeaderFields = function(serverResponse, data, url) {

	// Update table
	var table = document.getElementById('theTable');
	table.rows[tm.globals.row].cells[7].textContent = serverResponse.getResponseHeader("Etag");
	table.rows[tm.globals.row].cells[8].textContent = serverResponse.getResponseHeader("Last-Modified");
	
	// Update row data
	tm.updateRow(serverResponse, data, null);
	tm.globals.active = null;
   	tm.globals.col = -1;
    	tm.globals.row = 0;
};

/**
 * Called when row on server is "fresher" than
 * row on this client after an attempted edit.
 */
tm.updateRow = function(serverResponse, data, url) { 
	if(serverResponse.status == "200") {
		var obj = JSON.parse(serverResponse.responseText);
		obj = obj.Employee;
		var table = document.getElementById('theTable');
		
		var i = 0;
		for(var key in obj) { 
			if(key == "full_time") {
				if(obj[key] === "1") {
					table.rows[tm.globals.row].cells[i].firstChild.checked = true;
				} else {
					table.rows[tm.globals.row].cells[i].firstChild.checked = false;
				}
			} else { 
				if(key === "salary") { 
					table.rows[tm.globals.row].cells[i].textContent =
						tools.format_nondecimal_currency(obj[key]);
				} else { 
					table.rows[tm.globals.row].cells[i].textContent = obj[key];
				}				
			} 
			i++;
		}
		
		// Highlight updated row
		var element = table.rows[tm.globals.row];
		tools.highlightElem(element);
	    
		tm.globals.active = null;
	        tm.globals.col = -1;
	        tm.globals.row = 0;
	} else {
		alert("Oops! Something went wrong.");
	}
};
tm.updatePages = function(page) {
    // Determine which "page" to display
    var nextPage = 0;
    if(page === "rarrow") {
            nextPage = tm.globals.currentPage + 1;
    } else if(page === "larrow") {
    	if(tm.globals.currentPage != 1) {
            nextPage = tm.globals.currentPage - 1;
    	} else {
    		nextPage = tm.globals.currentPage;
    	}
    } else {
            nextPage = page;
    }
    
    // Commenting out the conditional makes this function
    // reusable (called when row is deleted). However, now
    // clicking the current page number makes a server request.
    // if(nextPage != tm.globals.currentPage) {
    	var request = tm.globals.url + "employees/?page=" + nextPage + "&pagesize=10&sort=" + tm.globals.sortByCol + "&order=" + tm.globals.sortOrder
    	ajax.request("GET", request,  tm.colCallBack, page, null, null);
    // }
};
tm.createJSONString = function(table, row, colName, value) {
	var data = '{ "lastname":"';	
	if(colName == "lastName") {
		data += value + '", ';
	} else {
		data += table.rows[tm.globals.row].cells[1].textContent + '", ';
	}
	
	data += '"firstname":"';
	if(colName === "firstName") {
		data += value + '", ';
	} else {
		data += table.rows[tm.globals.row].cells[2].textContent + '", ';
	}
     
	data += '"department":"';
	if(colName === "department") {
		data += value + '", ';
	} else {
		data += table.rows[tm.globals.row].cells[3].textContent + '", ';
	}
	
	data +=	'"fulltime":"';
	if(colName === "fulltime") {
		if(value) {
			data += 1 + '", ';
		} else {
			data += 0 + '", ';
		}
	} else {
		if(table.rows[tm.globals.row].cells[4].childNodes[0].checked) {
			data +=  '1", ';
		} else {
			data += '0", ';
		}
	}
    
	// Hire date column is not editable
    data += '"hiredate":"' + table.rows[tm.globals.row].cells[5].textContent + '", ';
    
    data += '"salary":"'
    if(colName === "salary") {
    	data += tools.strip_num_formatting(value) + '"  }';
    } else {
    	data += tools.strip_num_formatting(table.rows[tm.globals.row].cells[6].textContent) + '"  }';
    }
    return data;
};

/**
 * New menu item 
 */
tm.newRow = function(event) {
	// Need to check if cached department list is
	// most recent to save bandwidth but I don't do it here
	
	// Make ajax call with etag and lmod
	ajax.request("GET", tm.globals.url + "departments/", tm.newRowFormCallback,
			null, tm.globals.dlEtag, tm.globals.dlLastMod);
	
};
tm.newRowFormCallback = function(xhttp, url) { 
	var list = null;
	// If list on server changed...
	if(xhttp.status == "200") { 
		list = JSON.parse(xhttp.responseText);
		tm.globals.deptList = list;
		tm.globals.dlEtag = xhttp.getResponseHeader('Etag');
		tm.globals.dlLastMod = xhttp.getResponseHeader('Last-Modified');
		
		var dlist = document.getElementById('newdept');
		while(dlist.length > 0) {
			dlist.remove(dlist.length-1);
		}
		var length = list['Departments'].length;
		for (var i = 0; i < length; i++) {
	        var option = document.createElement("option");
	        option.value = list['Departments'][i];
	        option.text = list['Departments'][i];
	        dlist.appendChild(option);
		}	
	} 	
	var pop = document.getElementById('overlay');
	tm.globals.currentDiv = document.getElementById('new');
    	pop.style.display = "block";
    	tm.globals.currentDiv.style.display = "block";
};
tm.newRowSubmit = function(event) { 
	var lname = document.getElementById('newlname').value;
	var fname = document.getElementById('newfname').value;
	var dept = document.getElementById('newdept').value;
	var ftime = document.getElementById('newftime').checked;
	var year = document.getElementById('newyear').value;
	var month = document.getElementById('newmonth').value;
	var day = document.getElementById('newday').value;
	var salary = document.getElementById('newsalary').value;
	
	var hdate = year + "-" + month + "-" + day;
	if(ftime == true) {
		ftime = 1;
	} else {
		ftime = 0;
	}
	
	if(tm.validateRow(lname, fname, salary, year, month, day)) { 
		var data = "lname=" + lname + "&fname=" + fname + "&dept=" + dept;
		data += "&ftime=" + ftime + "&hdate=" + hdate + "&salary=" + salary;
		ajax.request("POST", tm.globals.url + "employees/", tm.newRowCallback, data, null, null); 
	} 	
};
tm.newRowCallback = function(xhttp, data, url) {
	document.getElementById('newRowForm').reset();
	tm.cancel();
	if(xhttp.status == "201") {
		alert('Success! - The record has been created');
		// Need to make another ajax call to get the updated record from
		// the server and update the table
		ajax.request("GET", xhttp.responseText, tm.addNewRowCallback, null, null, null);
	} else {
		alert('Error - The new record could not be created');
	}	
};
tm.addNewRowCallback = function(xhttp, data, url) { 
	var rowData = JSON.parse(xhttp.responseText);
	var rowData = rowData['Employee'];
	var table = document.getElementById('theTable');
	// Add new row to top of table
	var row = table.insertRow(1); // row 0 is the table header
	var i = 0;
	for (var key in rowData) {
	    if (rowData.hasOwnProperty(key)) { 
	        var cell = row.insertCell(i);
	        if(key == "full_time") {
	        	var checkbox = document.createElement('input');
	        	checkbox.type = "checkbox";
	        	if(rowData[key] == 1) {
	        		checkbox.checked = true;
	        	} 
	        	cell.appendChild(checkbox);
	        } else {
	        	cell.innerHTML = rowData[key];
	        }
	        
	        if(key == "etag" || key == "last_modified") {
	        	cell.style.display = "none";
	        }
	        i++;
	    }
	}
	
	// If table has 10 rows, delete last row
	var rowCount = document.getElementById('theTable').rows.length;
	if(rowCount >= 10) {
		table.deleteRow(11);
	}	
};
tm.validateRow = function(lname, fname, salary, year, month, day) {
	var exp = /[!"\#$%&'()*+,\-./:;<=>?@\[\\\]^_`{|}~0-9]/;
	
	if(lname === "") {
		alert('Please enter a last name');
		return false;
	} else if(lname.match(exp)) {
		alert('Last name must contain letters only');
		return false;
	}
	
	if(fname.match(exp)) {
		alert('First name must contain letters only');
		return false;
	} else if(fname === "") {
		alert('Please enter a first name');
		return false;
	} 
	
	if(!tools.validateDate(year, month, day)) {
		alert('Please enter a valid date');
		return false;
	}
	
	if(!tools.isNumber(salary)) {
		alert('Salary must contain only numbers');
		return false;
	} else if(salary === "") {
		alert('Please enter a salary amount');
		return false;
	} 
	
	return true;	
};

tm.deleteRow = function(event) {
	var pop = document.getElementById('overlay');
	tm.globals.currentDiv = document.getElementById('delete');
    pop.style.display = "block";
    tm.globals.currentDiv.style.display = "block";
};
tm.deleteRowSubmit = function(event) {
	var empId = document.getElementById('deleteInput').value;
	if(empId != "" && tools.isNumber(empId)) {
		// Need to get the etag and last modified values
		// to ensure the correct record is being deleted
		var etag = null;
		var lastMod = null;
		ajax.request("DELETE", tm.globals.url + "employees/" + empId, tm.deleteRowCallback, null, etag, lastMod);
	} else {
		alert("EmployeeID must be a numeric value");
	}
};
tm.deleteRowCallback = function(xhttp, data, url) { 
	if(xhttp.status == "204") {
		document.getElementById('deleteInput').value = "";
		tm.cancel();
		
		// Update the table.
		tm.updatePages(tm.globals.currentPage);
	} 	
};

// This feature not implemented
tm.search = function(event) {
	var pop = document.getElementById('overlay');
    tm.globals.currentDiv = document.getElementById('search');
    pop.style.display = "block";
    tm.globals.currentDiv.style.display = "block";
};

tm.help = function(event) {
	var pop = document.getElementById('overlay');
    tm.globals.currentDiv = document.getElementById('help');
    pop.style.display = "block";
    tm.globals.currentDiv.style.display = "block";
};

tm.cancel = function(event) {
	var pop = document.getElementById('overlay');
    pop.style.display = "none";
    tm.globals.currentDiv.style.display = "none";
};













/**
 * Helper functions.
 */
var tools = tools || {};
tools.isNumber = function(num) {
    if(isNaN(parseFloat(num)) && !isFinite(num)) {
            return false;
    }
    return true;
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
tools.format_nondecimal_currency = function(num) { 
    return "$" + num.toLocaleString("en-US");
};
tools.removeChildren = function(parent) {
	while (parent.firstChild) {
	    parent.removeChild(parent.firstChild);
	}
};
tools.highlightElem = function(elem) {
	var ofs = 0;  // initial opacity	
	var bgnd = elem.style.backgroundColor;
	var timer = setInterval(function () {
        if (ofs >= 1){
        	elem.style.backgroundColor = bgnd;
            clearInterval(timer);
            return;
        }
        elem.style.backgroundColor = 'rgba(51,153,255,'+Math.abs(Math.sin(ofs))+')';
        ofs += 0.05;
    }, 100);
};
tools.validateDate = function(year, month, day) {
	if(year < 1000 || year > 3000) {
		return false;
	}
	
	if(month < 1 || month > 12) {
		return false;
	} 
	
	var daysInMonth = [ 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 ];

    // Adjust for leap years
    if(year % 400 == 0 || (year % 100 != 0 && year % 4 == 0))
        daysInMonth[1] = 29;
    
    if(day < 1 || day > daysInMonth[month-1]) {
    	return false;
    }    
    return true;
};
tools.disableCheckboxes = function() {
	var elems = document.getElementsByTagName('input');
    	for(i in elems) {
		if(elems[i].type === "checkbox")
        		elems[i].disabled = true;
        }
};
tools.enableCheckboxes = function() {
	var elems = document.getElementsByTagName('input');
    	for(i in elems) {
		if(elems[i].type === "checkbox")
        		elems[i].disabled = false;
        }
};



//Contact the server
var ajax = ajax || {};
ajax.request = function(method, url, callbackFunc, data, etag, lastMod) { 
	var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function() {
    if (this.readyState == 4) {
    	callbackFunc(this, data, url);
        // NOTE: could use xmlHTTPrequest.responseURL but
        // it's not available on all browsers
        } 
    };
    xmlhttp.open(method, url, true);
         
    if(etag != null) {
       	xmlhttp.setRequestHeader("If-Match", etag);
    }
    if(lastMod != null) {
      	xmlhttp.setRequestHeader("If-Unmodified-Since", lastMod);
    }
        
    if(method == "GET") {
       	xmlhttp.setRequestHeader("Accept", "application/json");
       	xmlhttp.send();
    } else if(method == "PUT" || method == "POST") { 
       	if(method == "POST") {
       		xmlhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
       	}
       	xmlhttp.send(data);
    } else if(method == "DELETE") {
       	xmlhttp.send();
    }
};