<?php
/**
 * Simple service for populating an html select
 * element.
 */

// No sense in storeing this in the database
// but should put in a application config file.
$departments = array(
		"Accounting",
		"Administration",
		"Customer Service",
		"Management",
		"Marketing",		
		"Sales",
		"Technology"		
);

$params = explode("/", $_SERVER['REQUEST_URI']);

// Separate URL from query string
$requestURI = explode("?", $_SERVER['REQUEST_URI']);
$requestURI = $requestURI[0];

if(preg_match('/^\/REST_APP\/REST_App\/departments\/$/', $requestURI)) {
	/* URL: /departments/	*/
	
	if($_SERVER['REQUEST_METHOD'] === "GET") {
		echo json_encode($departments);
	}
} else {
	header('HTTP/1.1 404 Not Found');
	exit;
}

?>