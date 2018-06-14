<?php

require_once('Encode.php');

class View {
	
	private $outputFormat;
	
	public function __construct($outputFormat) {
		$this->outputFormat = $outputFormat;
	}
	
	public function respond($results) {
		$output = null;
		switch($this->outputFormat) {
			case "application/json":
				// $elist = array($results);
				$output = json_encode($results);
				header('Content-Type: application/json');
				break;
			case "text/html":
				$output = Encode::html_encode($results, "Employees");
				header('Content-Type: text/html');
				break;
			case "application/xml": 
				$output = Encode::xml_encode($results, "Employees");
				header('Content-Type: application/xml');
				break;
			case "text/xml":
				$output = Encode::xml_encode($results, "Employees");
				header('Content-Type: text/xml');
				break;
		}
		
		// Set headers
		header('HTTP/1.1 200 OK');
		header('Content-Length: '.strlen($output));
		
		if($_SERVER['REQUEST_METHOD'] == "GET") {
			echo $output;
		}
		exit;		
	}
}