<?php
require_once('Model.php');
require_once('Headers.php');

class Controller { 
	
	private $model;
	
	public function __construct($model) {
		$this->model = $model;
	}
	
	public function invoke() {
		// Dissect the url
		$params = explode("/", $_SERVER['REQUEST_URI']);
		
		// Separate URL from query string
		$requestURI = explode("?", $_SERVER['REQUEST_URI']);
		$requestURI = $requestURI[0];
		
		if(preg_match('/^\/employees\/$/', $requestURI)) {
			/* URL: /employees/	*/
			
			switch($_SERVER['REQUEST_METHOD']) {
				case "DELETE":
					$this->model->deleteAll();
				case "GET": 
				case "HEAD": 
					$this->model->getAll($_SERVER['REQUEST_METHOD']);
				case "OPTIONS":
					header("HTTP/1.1 200 OK");
					header("Allow: DELETE, GET, HEAD, POST, PUT");
					exit;
				case "POST":
					$this->model->post();
				default:
					header("HTTP/1.1 405 Method Not Allowed");
					header("Allow:  DELETE, GET, HEAD, OPTIONS, POST, PUT");
					exit;
			}
			
		} elseif(preg_match('/^\/employees\/[0-9]+$/', $requestURI)) {
			/* URL: /employees/{id}	*/
			
			$id = end($params);
			switch($_SERVER['REQUEST_METHOD']) {
				case "DELETE":
					$this->model->delete($id);
				case "GET":
				case "HEAD":
					$this->model->get($id, $_SERVER['REQUEST_METHOD']);
				case "OPTIONS":
					header("HTTP/1.1 200 OK");
					header("Allow: DELETE, GET, HEAD, PUT");
					exit;
				case "PUT":
					$this->model->put($id);
				default:
					header("HTTP/1.1 405 Method Not Allowed");
					header("Allow:  DELETE, GET, HEAD, OPTIONS, PUT");
					exit;
			}
		} else {
			header('HTTP/1.1 404 Not Found');
			exit;
		}
	}
}