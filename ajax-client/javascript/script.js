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
};

// Make AJAX an independent module
var ajax = ajax || {};

ajax.func = function(data) {
	 alert("AJAX baby! update: " + data)
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
			case "Refresh":
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
		}		
	};
}());

// Page manager for managing multiple page results
var pm = pm || {};

pm.glbs = {
	currentPage: 1,  /* Default */
	startPage: 1
}

pm.updatePages = function(page) { // alert("endPage = " + endPage);
	// Need to check with the server first
	// in case of server or network error
	
	// Update the data table 

	// Update page navigation
	if(page === "rarrow") {
		// Check if next page exists
		var next = document.getElementById(pm.glbs.currentPage+1);
		if(next != null) { console.log("IN IF");
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
		console.log("CP = " + pm.glbs.currentPage)
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
