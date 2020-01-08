# RESTful-App
RESTful web service application with HTML client

# NOTE: This application exposed a bug in the Chrome browser as of 1/7/2020.
The Salary column does not work properaly in Chrome's standard view but
works perfectly in Chrome's development view. I reported the bug to Google.
The application works perfectly in FireFox.

## Location
http://modintro.com/employees/

## Screenshot
![Screenshot](https://github.com/cspen/RESTful-App/blob/master/screenshot.png)


## What the Application Does
### Server
The server provides employee records from a database and allows CRUD operations
on those records via Web Services as specified in RFC 2616 HTTP.

### HTML Client
Any client who makes a request to the server with HTML indicated in the "Accept"
header (web browser, for example) will receive an HTML page that provides the data in an editable
HTML table. The table allows for sorting by column and CRUD operations.

Tested in the following browsers
- Mozilla Firefox 63.0.3
- Microsoft Edge 42.17134.1.0
- Google Chrome 71.0.3578.80

## Related Project - Java Swing Client
A Java Swing application that connects to the server and provides
the same functionality as the HTML client is available at
[Java Client](http://modintro.com/java/client/)

## Application Design and Construction
### The Server
The server follows RFC 2616 HTTP Protocol to provide RESTful Web Services.
It is written in object oriented PHP with a MySQL database and has
an MVC architecture.

### HTML Client
The HTML client application uses a single page application (SAP)
architecture, Responsive Web Design, and AJAX. It is coded using
javascript, HTML5, and CSS3.


## Notes and Additional Thoughts
The client application uses a single page application (SAP)
architecture connecting to a RESTful Web Service Server, Responsive Web Design, and AJAX. I am
also hoping to extract some general functionality to create a reusable
framework for editable html tables.

The Server:
The server coded in PHP with a MySQL database. The server is a
RESTful Web Service (HTTP Protocol) according to RFC 2616.
https://tools.ietf.org/html/rfc2616

The server architecture is Model-View-Controller (MVC) pattern with
Object Oriented Design (OOD).


The HTML/CSS/Javascript Client:
The single page application (SPA) is coded in HTML5, CSS3, and Javascript.
The javascript is in a module design but I think a object/prototype design
would be better.

There is also a Java Swing client but that code is in another repository:
https://github.com/cspen/Java-Client
 


Launching the single page application (SPA):
The client will initially request an HTML document via the HTTP Accept request
header. This HTML document will contain the SPA which consists of HTML, CSS and
Javascript. Once the client loads the SPA all further server requests will be
made via AJAX and will request a JSON response from the server via the HTTP
Accept request header.

A feature of this application is a set of functions
for providing different data formats for web server responses.
That is, to format database result sets into json, xml, html,
and soon to be added, csv.

Thoughts
-Single AJAX method-
I read it was considered good practice (or DRY) to have a single
method for AJAX and route all AJAX requests through that method.
I'm finding it difficult to set headers with this framework. To set
headers you must pass both the header name and header data to the
AJAX method. It would be easier to not use a single AJAX method, 
setting the headers and other fields for each unique request within
the method for handling the event.

* Design
Currently I'm designing the system to utilize three functions for each
request. The first function acts as an event handler/controller and
calls the second function, the second function makes the AJAX request,
and the third function handles the server response, displays any error
messages, and updates the DOM. The third method is the callback
method passed to the AJAX (second) function. Here is the current
design:

Human clicks something -> EventHandler -> AJAX -> AJAXcallback

By not using a single AJAX method, not only would it be easier to
set request headers and other information unique to each AJAX call
but it will also reduce the function chain from three to two.

- not featured
I've decided to not implement a search feature because the current
architecture is too weak. I didn't have a search feature in mind 
initially. To implement search, I would need a search feature on the
server. Also, the client javascript application would need some sort
of screen manager to allow toggling between the data table and the
search results.