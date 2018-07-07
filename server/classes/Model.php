<?php
require_once('View.php');
require_once('DBConnection.php');

class Model {
	
	private $view;
	
	public function __construct($view) {
		$this->view = $view;
	}
	
	function deleteAll() {
		$db = new DBConnection();
		$dbconn = $db->getConnection();
		
		$query = "DELETE FROM employee";
		$fromFlag = $toFlag = FALSE;
		if(isset($_GET['from'])) {
			if(!is_numeric($_GET['from'])) {
				header('HTTP/1.1 400 Bad Request');
				exit;
			}
			$query .= " WHERE employeeID >= :fromID";
			$fromFlag = TRUE;
		}
		if(isset($_GET['to'])) {
			if(!is_numeric($_GET['to'])) {
				header('HTTP/1.1 400 Bad Request');
				exit;
			}
			$toFlag = TRUE;
			if($fromFlag) {
				$query .= " AND employeeID <= :toID";
			} else {
				$query .= " WHERE employeeID <= :toID";
			}
		}
			
		$stmt = $dbconn->prepare($query);
		if($fromFlag) {
			$stmt->bindParam(':fromID', $_GET['from']);
		}
		if($toFlag) {
			$stmt->bindParam(':toID', $_GET['to']);
		}
			
		if($stmt->execute()) {
			header('HTTP/1.1 204 No Content');
			exit;
		} else {
			header('HTTP/1.1 500 Internal Server Error');
			exit;
		}
	}
	
	function delete($id) {
		$db = new DBConnection();
		$dbconn = $db->getConnection();
		
		// First check record against headers
		$stmt = $dbconn->prepare("SELECT * FROM employee WHERE employeeID=:empID");
		$stmt->bindParam(':empID', $employeeId);
			
		if($stmt->execute()) {
			$rowCount = $stmt->rowCount();
			if($rowCount == 1) {
				$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
				$result = $result[0];
				$stmt->closeCursor();
				Headers::processConditionalHeaders($result['etag'], $rowCount, $result['last_modified']);
					
				// Delete the resource
				$stmt = $dbconn->prepare("DELETE FROM employee WHERE employeeID=:empID");
				$stmt->bindParam(':empID', $employeeId);
					
				if($stmt->execute()) {
					header('HTTP/1.1 204 No Content');
					exit;
				} else {
					header('HTTP/1.1 500 Internal Server Error');
					exit;
				}
			} else {
				Headers::processConditionalHeaders(null, $rowCount, null);
				header('HTTP/1.1 204 No Content');
				exit;
			}
		} else {
			header('HTTP/1.1 500 Internal Server Error');
			exit;
		}
	}
	
	function getAll($HTTPverb) {
		$query = "SELECT employeeID, last_name, first_name, department,
			full_time, DATE_FORMAT(hire_date, '%Y-%m-%d') AS hire_date,
			salary, etag, last_modified FROM employee";
		
		$sortBy = array("date", "headline");
		if(isset($_GET['sort'])) {
			if(in_array($_GET['sort'], $sortBy)) {
				$query .= " ORDER BY ".$_GET['sort'];
			} elseif(isset($_GET['sort']) && $_GET['sort'] == "userid") {
				$query .= " ORDER BY userID_FK";
			} else {
				header('HTTP/1.1 400 Bad Request');
				exit;
			}
			
			// Sort order - asc default
			if(isset($_GET['order'])) {
				$order = $_GET['order'];
				if($order === "desc") {
					$query .= " DESC";
				} else {
					header('HTTP/1.1 400 Bad Request');
					exit;
				}
			}
		}
		
		// Process url parameters
		if(isset($_GET['page']) && isset($_GET['pagesize'])) {
			if($_GET['page'] > 0 && $_GET['pagesize'] > 0) {
				$page = ($_GET['page'] - 1) * $_GET['pagesize'];
				$query .= " limit ".$page.", ".$_GET['pagesize'];
			} else {
				header('HTTP/1.1 400 Bad Request');
				exit;
			}
		}
		
		$db = new DBConnection(); 
		$dbconn = $db->getConnection();
		$stmt = $dbconn->prepare($query);
		if($stmt->execute()) { 
			if($stmt->rowCount() == 0) {
				header('HTTP/1.1 204 No Content');
				exit;
			}			
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$this->view->respond($results);
		} else {
			header('HTTP/1.1 500 Internal Server Error');
			exit;
		}
	}
	
	function get($id, $HTTPverb) {
		$query = "SELECT employeeID, last_name, first_name, department,
			full_time, DATE_FORMAT(hire_date, '%Y-%m-%d') AS hire_date,
			salary, etag, last_modified FROM employee WHERE employeeID=:empID";
		
		$db = new DBConnection();
		$dbconn = $db->getConnection();
		$stmt = $dbconn->prepare($query);
		$stmt->bindParam(':empID', $id);
		if($stmt->execute()) {
			$rowCount = $stmt->rowCount();
			if($rowCount == 1) {
				$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
				$result = $result[0];
				Headers::processConditionalHeaders($result['etag'], $rowCount, $result['last_modified']);
				
				// ****** NEED TO MOVE DATA FORMATING TO A FUNCTION
				$output = json_encode($result);
				
				// ***** NEED TO UPDATE THIS HEADER BASED ON $outputFormat
				header('Content-Type: application/json');
				header('Content-Length: '.strlen($output));
				header('Etag: '.$result['etag']);
				header('Last-Modified: '.$result['last_modified']);
				header('HTTP/1.1 200 OK');
				
				if($HTTPverb === "GET") {
					echo $output;
				}
				exit;
			} else {
				Headers::processConditionalHeaders(null, $rowCount, null);
				header('HTTP/1.1 404 Not Found');
				exit;
			}
		} else {
			header('HTTP/1.1 500 Internal Server Error');
			exit;
		}
	}
	
	function post() {
		$db = new DBConnection();
		$dbconn = $db->getConnection();
		
		if(!empty($_POST)) {
			$mflag = FALSE;
			if(($userType === "MASTER" || $userType === "ADMIN") && isset($_POST['userid_fk'])) {
				$userID_FK = $_POST['userid_fk'];
				$mflag = TRUE;
			}
			if(isset($_POST['lastname']) && isset($_POST['firstname'])
				&& isset($_POST['department']) && isset($_POST['fulltime'])
				&& isset($_POST['hiredate']) && isset($_POST['salary'])) {
				$lastName = trim($_POST['lastname']);
				$firstName = trim($_POST['firstname']);
				$deparment = trim($_POST['department']);
				$fullTime = trim($_POST['fulltime']);
				$hireDate = trim($_POST['hiredate']);
				$salary = trim($_POST['salary']);
			} else {
				header('HTTP/1.1 400 Bad Request');
				exit;
			}
						
			$stmt = $dbconn->prepare("INSERT INTO employee
				(last_name, first_name, department, full_time, hire_date, salary)
				VALUES(:lastName, :firstName, :department, :fullTime, :hireDate, :salary)");
						
			if($mflag) {
				$stmt->bindParam(':userID_FK', $userID_FK);
			} else {
				$uid = $user->getId();
				$stmt->bindParam(':userID_FK', $uid);
			}
			$stmt->bindParam(':lastName', $lastName);
			$stmt->bindParam(':firstName', $firstName);
			$stmt->bindParam(':department', $department);
			$stmt->bindParam(':fullTime', $fullTime);
			$stmt->bindParam(':hireDate', $hireDate);
			$stmt->bindParam(':salary', $salary);
						
			if($stmt->execute()) {
				$i = $dbconn->lastInsertId();
				$location = $_SERVER['REQUEST_URI'].$i;
				header('Content-Location: '.$location);
				echo $location;
			} else {
				header('HTTP/1.1 500 Internal Server Error');
				exit;
			}
		}
	}
	
	function put($id) {
		if(isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
			header('HTTP/1.1 412 Precondition Failed');
			exit;
		}
		
		$putVar = json_decode(file_get_contents("php://input"), true);
		if(isset($putVar) && array_key_exists('lastname', $putVar) && array_key_exists('firstname', $putVar)
				&& array_key_exists('department', $putVar) && array_key_exists('fulltime', $putVar)
				&& array_key_exists('hiredate', $putVar) && array_key_exists('salary', $putVar)) {
					
			$this->validateNumericFields($putVar);
					
			$db = new DBConnection();
			$dbconn = $db->getConnection();
			
			$stmt = $dbconn->prepare("SELECT * FROM employee WHERE employeeID = :empID");
			$stmt->bindParam(':empID', $id);
			$stmt->execute();
			
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$results = $results[0];
			$rowCount = $stmt->rowCount();
			$stmt->closeCursor();
						
			$flag = false;
			if($rowCount == 1) { // Update (replace) existing resource
				Headers::processConditionalHeaders($results['etag'], $stmt->rowCount(), $results['last_modified']);
							
				$stmt = $dbconn->prepare("UPDATE employee SET last_name=:lastName, first_name=:firstName,
					department=:department, full_time=:fullTime, hire_date=:hireDate, salary=:salary
					WHERE employeeID=:empID");
				$flag = true;							
			} else { // Create a new resource
				Headers::processConditionalHeaders(null, 0, null);
						
				$stmt = $dbconn->prepare("INSERT INTO employee
					(employeeID, last_name, first_name, department, full_time, hire_date, salary)
					VALUES(:empID, :lastName, :firstName, :department, :fullTime, :hireDate, :salary)");
			}
			$stmt->bindParam(':empID', $id);
			$stmt->bindParam(':lastName', $putVar['lastname']);
			$stmt->bindParam(':firstName', $putVar['firstname']);
			$stmt->bindParam(':department', $putVar['department']);
			$stmt->bindParam(':fullTime', $putVar['fulltime']);
			$stmt->bindParam(':hireDate', $putVar['hiredate']);
			$stmt->bindParam(':salary', $putVar['salary']);
					
			if($stmt->execute()) {
				if($flag) {
					header('HTTP/1.1 204 No Content');
				} else {
					header('HTTP/1.1 201 Created');
				}
				exit;
			} else {
				header('HTTP/1.1 504 Internal Server Error');
				exit;
			}
		} else {
			header('HTTP/1.1 400 Bad Request');
			echo 'FUCKED UP';
			exit;
		}
	}
	
	function validateNumericFields($a) {
		if(!is_numeric($a['salary'])) {
			header('HTTP/1.1 400 Bad Request');
			exit;
		}
	}
	
}
?>