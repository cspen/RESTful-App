<?php

require_once('Encode.php');

class View {
	
	private $outputFormat;
	
	public function __construct($outputFormat) {
		$this->outputFormat = $outputFormat;
	}
	
	public function respond($results, $type) {
		$output = null;
		switch($this->outputFormat) {
			case "application/json":
				$elist = array($type => $results);
				$output = json_encode($elist);
				header('Content-Type: application/json');
				break;
			case "text/html":
				$output = Encode::html_encode($results, $type);
				header('Content-Type: text/html');
				break;
			case "application/xml": 
				$output = Encode::xml_encode($results, $type);
				header('Content-Type: application/xml');
				break;
			case "text/xml":
				$output = Encode::xml_encode($results, $type);
				header('Content-Type: text/xml');
				break;
		}
		
		// Set headers
		header('HTTP/1.1 200 OK');
		header('Content-Length: '.strlen($output));
		if(isset($results['etag'])) {
			header('Etag: '.$results['etag']);
		}
		if(isset($results['last_modified'])) {
			header('Last-Modified: '.$results['last_modified']);
		}
		
		if($_SERVER['REQUEST_METHOD'] == "GET") {
			echo $output;
		}
		exit;		
	}
	
	public function outputFormat() {
		return $this->outputFormat;
	}
}