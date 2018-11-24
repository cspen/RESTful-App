<?php
/**
 * Connect to a MySQL database with PDO using credentials stored in a config.ini file.
 */

class DBConnection {
	
	private $connection;
	
	function __construct() {
		if(!isset($this->connection)) {
			// $config = parse_ini_file('config.ini');
			try {
				$this->connection = new PDO("mysql:host=localhost;dbname=restapp", 'root', '');
			} catch(PDOException $e) {
				echo $e->getMessage();
			}
		}
		
		if($this->connection === false) {
			return PDO::errorinfo();
		}
		$this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
	
	// For queries without parameters
	function db_query($query) {
		$stmt = $this->connection->query($query);
		return $stmt;
	}
	
	// For queries with parameters
	function getConnection() {
		return $this->connection;
	}
}
?>