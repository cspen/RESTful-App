/**
 * Table Manager makes an HTML table editable.
 * 
 * Created By: Craig Spencer
 * Date: May 14, 2018
 */
var tm = tm || {};

tm.glbs = {
		flag : false,
		elem : null,
		column : -1,
		row : 0
}

tm.clickedCell = function (e) {	
	var table = document.getElementById("theTable");
		
	// Get the clicked column
	var length = document.getElementById("theTable").rows[0].cells.length;	 
	var width = 0;
	for(var i = 0; i < length; i++) {
		width += table.rows[0].cells[i].offsetWidth;
		if(e.pageX < width) {
			tm.glbs.column = i;
			break;
		}
	}
	
	// Get the clicked row
	length = document.getElementById("theTable").rows.length;
	var height = 0; 
	for(var i = 0; i < length; i++) {
		height += table.rows[i].offsetHeight;
		if((e.pageY - table.offsetTop) < height) {
			tm.glbs.row = i;
			break;
		}
	}
	
	// Update the table
	if(tm.glbs.row != 0) { 		
		if(!tm.glbs.flag) {
			tm.glbs.elem = document.createElement("INPUT");
			
			// Get current content in cell and put in text input
			tm.glbs.elem.value = table.rows[tm.glbs.row].cells[tm.glbs.column].textContent;
			tm.glbs.elem.type = "text";
			tm.glbs.elem.id = "edit";
			tm.glbs.elem.addEventListener("keyup", function(event) {
			    event.preventDefault();
			    if (event.keyCode === 13) {
			    	// Here is where the AJAX call is made
			    	// Also need to validate edits
			    	var value = this.value;
			    	// tm.AJAX(value);alert("cheers");
			    	table.rows[tm.glbs.row].cells[tm.glbs.column].removeChild(this);
			    	// if(column === )
			    	table.rows[tm.glbs.row].cells[tm.glbs.column].textContent = value;
			    	tm.glbs.flag = false;
			    	tm.glbs.column = -1;
			    	tm.glbs.row = 0;
			    }
			});
			table.rows[tm.glbs.row].cells[tm.glbs.column].innerHTML = "";
			table.rows[tm.glbs.row].cells[tm.glbs.column].appendChild(tm.glbs.elem);
			tm.glbs.elem.focus();
			tm.glbs.elem.select();
			tm.glbs.flag = true;
		}		
	}
}

tm.AJAX = function (data) {
	alert("AJAX baby! update: " + data)
}