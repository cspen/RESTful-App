# RESTful-App
RESTful web service server with HTML client


## Location
https://modintro.com/employees/


## Screenshot
![Screenshot](https://github.com/cspen/RESTful-App/blob/master/screenshot.png)


## Description of the Application
### Server (Application tier)
The server allows access to employee records stored in a database by providing
CRUD(Create, Read, Update, Delete) operations on those records via Web Services.

### HTML Client (Presentation tier)
Any client who makes a request to the server with HTML indicated in the "Accept"
header (web browser, for example) will receive as a response an HTML page that provides
the data in an editable HTML table. The table allows for sorting by column and CRUD operations.


## Launching the Single Page Application (SPA):
A web browser requests an HTML document from https://modintro.com/employees with the HTTP Accept request
header indicating an HTML document is expected by the browser. The HTML document returned by the server is the SPA.


## Application Design and Construction
### The Server
The server follows the RFC 2616 HTTP Protocol (https://tools.ietf.org/html/rfc2616) to provide
RESTful Web Services to clients. It is written in object oriented PHP with an MVC architecture
and uses a MySQL database for the persistence layer (Data tier). 

### HTML Client
The HTML client application uses a single page application (SAP)
architecture, Responsive Web Design, as well as AJAX and is coded in
javascript, HTML5, and CSS3.

The application uses JSON for data transport.


## Notes and Additional Thoughts

Tested in the following browsers
- Mozilla Firefox (63.0.3 and above)
- Microsoft Edge (42.17134.1.0 and above)
- Google Chrome (71.0.3578.80 and above)
However, I can't guarantee the application works correctly in
every version of every browser.

One feature of this application is the ability
for providing different data formats for web server responses,
that is, to format database result sets into json, xml, and html
based on the client preference. The
data type of the server response depends on the value in the Accept
header in the client's request as according to RFC 2616.

* Design
The client is designed such that it utilizes three functions for each
request. The first function acts as an event handler/controller and
calls the second function, which makes the AJAX request,
and the third function handles the server response, displays any error
messages, and updates the view. The third method, the callback
method, is passed to the AJAX (second) function. Here is the current
design:

Human clicks something -> [EventHandler -> AJAX -> AJAXcallback]

-No Search Feature
I didn't have a search feature in mind 
initially. To implement search, I would need a search service on the
server. A search form would need to appear somewhere, such as in a diolog
pop-over, and the results of the search would need to be displayed in the
table. If the search produces no results a message would need to be displayed
to inform the user.

## Related Project - Java Swing Client
A Java Swing application that connects to the same server used
in this project and provides the same functionality as the HTML client in this
project but is a stand-alone desktop application built with Java Swing. The
project can be found at
[Java Client](http://modintro.com/java/client/)

The Java Swing client code repository:
https://github.com/cspen/Java-Client