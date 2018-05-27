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

