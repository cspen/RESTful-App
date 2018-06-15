<?php
/**
 * Connect to a MySQL database with PDO.
 * This class must be stored above public access.
 */

class DBConnection {
	
	// The DB connection
	private $connection;

	// DB credentials
	private 	$username = 'dbo736128965';
	private		$password = 'T3st1ng!';
	private		$dbname = 'db736128965';
	private		$servername = 'db736128965.db.1and1.com';

	
	function __construct() {
		if(!isset($this->connection)) {
			try {
				$this->connection = new PDO("mysql:host=$this->servername;dbname=$this->dbname", $this->username, $this->password);
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