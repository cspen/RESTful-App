# RESTful-App
Multi-client RESTful web service application

This is a test application before building a much larger
and more robust application to be hosted at modintro.com.

The point of this application is to develop a set of functions
for providing different data formats for web server responses.
That is, to format database result sets into json, xml, html,
csv, and so on.

Another reason for this application is to develop a way of kickstarting
a single page application (SPA). The client will initially request an HTML
document via the HTTP Accept request header. This HTML document will contain
the SPA which consists of HTML, CSS and Javascript. Once the client loads the
SPA all further server requests will be made via AJAX and will request a JSON
response from the server via the HTTP Accept request header.

The Java client will request XML from the server. The data contained in the
XML file will then be parsed into a two-dimensional array so it can be used
in a TableModel for a JTable.

Adding java client last.

Thoughts
	-Single AJAX method-
	I read it was considered good practice (or DRY) to have a single
	method for AJAX and route all AJAX requests through that method.
	I'm finding it difficult to set headers with this framework. To set
	headers you must pass both the header name and header data to the
	AJAX method. It would be easier to not use a single AJAX method, 
	setting the headers and other fields for each unique request within
	the method for handling the event.

	Currently I'm designing the system to utilize three methods for each
	request. The first method acts as an event handler and calls the AJAX
	method, the second method makes the AJAX request, and the third method
	updates the DOM. The third method is the callback method passed to the
	AJAX method. Here is the current design:

	Human clicks something -> EventHandler -> AJAX -> AJAXcallback

	By not using a single AJAX method not only would it be easier to
	set request headers and other information unique to each AJAX call
	but it will also reduce the method chain from three to two.


UPDATE 2018-08-31
	I've decided to not implement a search feature because the current
	architecture is too weak. I didn't have a search feature in mind 
	initially. To implement search, I would need a search feature on the
	server. Also, the client javascript application would need some sort
	of screen manager to allow toggling between the data table and the
	search results.
