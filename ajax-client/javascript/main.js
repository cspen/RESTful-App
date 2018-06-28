/**
 * 
 */


/* The Table Manager */
var tm = tm || {};

tm.globals = {
		// table : null,
		col : -1,			// Table column
		row : 0,			// Table row
		active : false,		// Is a cell currently being edited
		elem : null			// The element
}

tm.clickedCell = function(e) { alert("HELP!");
	if(!tm.globals.active) {
		// Get the table
		var table = document.getElementById("theTable");
        
		// Get the clicked table cell
        var elem = e.target;
        tm.globals.row = elem.parentNode.rowIndex;
        tm.globals.col = elem.cellIndex;
        
        // Column header clicked - sort by column
        if(tm.globals.row == 0) {
        	// alert("SORT BY HEADER " + e.target.innerHTML)
        } 
        
        // Uneditable columns and rows
        if(tm.globals.col == 0 || tm.globals.col == 5 || e.target.tagName == "CAPTION")
        	return;
        
        // Handle checkbox click - full time column
        if(e.target.type == "checkbox") {
        	e.stopImmediatePropagation();
        	e.preventDefault();        	
        	// alert('CHECKBOX');
        	return;
        } 
        
        if(tm.globals.col == 3) {
        	alert("Department");
        } else {
        	if(tm.globals.col != 4)
        		tm.createEditorElement(e.target.textContent, table);
        }
        
        console.log("CLICKED: " + tm.globals.row + ", " + tm.globals.col);
        console.log("e.target.textContent: " + e.target.textContent);
        console.log("e.target.type: " + e.target.type);
        console.log("e.target.tagName: " + e.target.tagName);
        console.log();
        tm.globals.active = true;
	} 
};

tm.createEditorElement = function(content, table) {
	// Create the element
	tm.globals.elem = document.createElement("INPUT");
    tm.globals.elem.type = "text";
    
    if(tm.globals.col == 6) {
    	tm.globals.elem.value = tools.strip_num_formatting(content);
    } else {
    	tm.globals.elem.value = content;
    }    
    
    tm.globals.elem.addEventListener("keydown", tools.editorEventListener);
    table.rows[tm.globals.row].cells[tm.globals.col].innerHTML = "";
    table.rows[tm.globals.row].cells[tm.globals.col].appendChild(tm.globals.elem);
    tm.globals.elem.focus();
    tm.globals.elem.select();
    tm.globals.active = false;
	// alert("CREATE: " + content);
}





var tools = tools || {};
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
tools.editorEventListener = function(event) {         
		var table = document.getElementById('theTable');
		
        if (event.keyCode == 13) {  
        	event.stopImmediatePropagation();
            event.preventDefault();
        	
            // Validate edit 
            var value = this.value;

             // Check that name columns do not contain numbers
             if(tm.globals.col == 1 || tm.globals.col == 2) {
                     
             } else if(tm.globals.col == 6) { // Check that salary is numeric
                     if(isNaN(parseFloat(value)) && !isFinite(value)) {
                             // NEED TO CHANGE TO DIALOG BOX
                             alert("Salary must be a numeric value");
                             return;
                     }
             }
                     
             // If valid edit, update server
             // tm.AJAX(value);
             
             // Update the table
             table.rows[tm.globals.row].cells[tm.globals.col].removeChild(this);

             // Convert Salary column to currency format
             if(tm.globals.col === 6) {
                     if(tools.isNumber(value)) {
                             value = tools.format_nondecimal_currency(value);
                     }
             }
             table.rows[tm.globals.row].cells[tm.globals.col].textContent = value;
             tm.globals.active = false;
             tm.globals.col = -1;
             tm.globals.row = 0;
         }
};




