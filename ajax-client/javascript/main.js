/**
 * 
 */

/* The Table Manager */
var tm = tm || {};
tm.globals = {
		col : -1,			// Table column
		row : 0,			// Table row
		active : null,		// Is a cell currently being edited
		url : "http://localhost/GEM/rest/",
		cbox : null
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
        	console.log("SORT BY HEADER " + e.target.innerHTML);
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
        
        // Create the element for editing
        var curVal = e.target.textContent;
        if(tm.globals.col == 3) {
        	var url = "http://modintro.com/departments/";
        	tm.createSelectElement(url, curVal);
        } else {
        	if(tm.globals.col != 4)
        		tm.createInputElement(curVal);
        }
        
        tm.globals.active = curVal;
	} 
};

/**
 * 
 */
tm.createInputElement = function(content) {
	// Create the element
	var input = document.createElement("INPUT");
	input.type = "text";
    
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
		select.id = "department";
		var length = items.length;
		for (var i = 0; i < length; i++) {
            var option = document.createElement("option");
            option.value = items[i];
            option.text = items[i];
            if(items[i] == selected) {
            	option.selected = true;
            }
            select.appendChild(option);
		}
		tm.setElement(select);
		select.addEventListener("keydown", tm.editorEventListener);    
	} catch(e) {
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
 * 
 */
tm.editorEventListener = function(event) {
    if (event.keyCode == 13) {  
    	event.stopImmediatePropagation();
        event.preventDefault();       

        // Validate edit 
        var value = this.value;
        var table = document.getElementById('theTable');
        
        if(value == "") {
        	// If input field is empty, replace original content
        	table.rows[tm.globals.row].cells[tm.globals.col].textContent = tm.globals.active;
        	tm.globals.row = -1;
        	tm.globals.col = 0;
        	tm.globals.active = null;
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
    	var table = document.getElementById('theTable');
    	var node = table.rows[tm.globals.row].cells[tm.globals.col];
		node.removeChild(node.firstChild);
		node.textContent = tm.globals.active;
		tm.globals.active = null;
    }
}
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
        table.rows[tm.globals.row].cells[tm.globals.col].textContent = value;
        
        // UPDATE ETAG AND LAST MODIFIED FEILDS
       ajax.request("GET", url, tm.updateHeaderFields, null, null, null);
	} else {
		// TO-DO: Display error message $#$#$#$#$#$#%@%@%@%@%@%@^!^!^!^!^
		if(serverResponse.status == "412") {
			// TO-DO: Change to dialog box
			// alert("Unable to update - More recent copy on server");
			// Update the row
			ajax.request("GET", url, tm.updateRow, null, null, null);
		}
	}
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
		// TO-DO: Handle error ************#$#$#$#$#$#$#$#$#$#$#$
		// Check for 412 status
		if(serverResponse.status == "412") {
			// alert("CBCB: " + serverResponse.status);
			
			// Update the row
			ajax.request("GET", url, tm.updateRow, null, null, null);
		}
		// Need to inform human and update row
	}
	return;
};
tm.updateHeaderFields = function(serverResponse, data, url) {
	// Update table
	var table = document.getElementById('theTable');
	table.rows[tm.globals.row].cells[7].textContent = serverResponse.getResponseHeader("Etag");
	table.rows[tm.globals.row].cells[8].textContent = serverResponse.getResponseHeader("Last-Modified");
	
	tm.globals.active = null;
    tm.globals.col = -1;
    tm.globals.row = 0;
};
tm.updateRow = function(serverResponse, data, url) {
	if(serverResponse.status == "200") {
		var obj = JSON.parse(serverResponse.responseText);
		var table = document.getElementById('theTable');
		var node = table.rows[tm.globals.row].cells[tm.globals.col];
		node.removeChild(node.firstChild);
		
		var i = 0;
		for(var key in obj) { 
			if(key === "full_time") {
				if(obj[key]) {
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
		/*
		// Fade out (needs to be in own function)
		var ofs = 0;  // initial opacity
		var element = table.rows[tm.globals.row];
		var bgnd = element.style.backgroundColor;
		// alert(" * * * * " + bgn);
	    var timer = setInterval(function () {
	        if (ofs >= 1){
	        	element.style.backgroundColor = bgnd;
	            clearInterval(timer);
	            return;
	        }
	        element.style.backgroundColor = 'rgba(0,0,0,'+Math.abs(Math.sin(ofs))+')';
	        ofs += 0.01;
	    }, 100)
	    */
	} else {
		alert("ERROR UR");
	}
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
 * 
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
    var len = num.length;
    var newNum = "";
    for(var i = 0; i < len; i++) {
            if((len - i)%3 == 0 && i != 0)
                    newNum += ",";
            newNum += num.charAt(i);
    }
    return "$" + newNum;
};
tools.removeChildren = function(parent) {
	while (parent.firstChild) {
	    parent.removeChild(parent.firstChild);
	}
};

//Contact the server
var ajax = ajax || {};
ajax.request = function(method, url, callbackFunc, data, etag, lastMod) {
	console.log("AJAX " + url + ", ETAG: " + etag + ", LASTMOD: " + lastMod);
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4) {
                callbackFunc(this, data, url);
                // NOTE: could use xmlHTTPrequest.responseURL but
                // it's not available on all browsers
            } 
        };
        xmlhttp.open(method, url, true);
         
        if(etag) {
        	xmlhttp.setRequestHeader("Etag", etag);
        }
        if(lastMod) {
        	xmlhttp.setRequestHeader("If-Unmodified-Since", lastMod);
        }
        
        if(method == "GET") {
        	xmlhttp.setRequestHeader("Accept", "application/json");
        	xmlhttp.send();
        } else if(method == "PUT" || method == "POST") {
        	xmlhttp.send(data);
        }
};