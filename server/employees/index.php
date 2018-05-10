<?php
require_once '../../utilities/Database_Connection.php';
require_once '../../utilities/tools.php';

// Make sure this service can supply
// the data format requested by the client
// $outputFormat = processAcceptHeader();

// Dissect the url
$params = explode("/", $_SERVER['REQUEST_URI']);

// Separate URL from query string
$requestURI = explode("?", $_SERVER['REQUEST_URI']);
$requestURI = $requestURI[0];

if(preg_match('/^\/employees\/$/', $requestURI)) {
	/* URL: /employees/	*/
	
	switch($_SERVER['REQUEST_METHOD']) {
		case "DELETE":
			deleteAll();
		case "GET":
		case "HEAD":
			getAll($_SERVER['REQUEST_METHOD']);
		case "OPTIONS":
			header("HTTP/1.1 200 OK");
			header("Allow: DELETE, GET, HEAD, POST");
			exit;
		case "POST":
			post();
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
			delete($id);
		case "GET":
		case "HEAD":
			get($id, $_SERVER['REQUEST_METHOD']);
		case "OPTIONS":
			header("HTTP/1.1 200 OK");
			header("Allow: DELETE, GET, HEAD, PUT");
			exit;
		case "PUT":
			put($id);
		default:
			header("HTTP/1.1 405 Method Not Allowed");
			header("Allow:  DELETE, GET, HEAD, OPTIONS, PUT");
			exit;
	}
} else {
	header('HTTP/1.1 404 Not Found'); 
	exit;
}


function deleteAll() {
	$dbconn = getDBConnection();
	$user = lauthenticateUser($dbconn);
	
	if($user->getType() === "MASTER") {
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
	} else {
		header("HTTP/1.1 403 Forbidden");
		exit;
	}
}

function delete($id) {
	$dbconn = getDBConnection();
	$user = lauthenticateUser($dbconn);
	
	$userType = $user->getType();
	if($userType === "MASTER" || $userType === "ADMIN" || $userType === "USER") {
		// First check record against headers
		$stmt = $dbconn->prepare("SELECT * FROM employee WHERE employeeID=:empID");
		/*
		if(($userType === "MASTER" || $userType === "ADMIN") && isset($_GET['userid'])) {
			$stmt->bindParam(':userID', $_GET['userid']);
		} else {
			$uid = $user->getId();
			$stmt->bindParam(':userID', $uid);
		} */
		$stmt->bindParam(':empID', $employeeId);
		
		if($stmt->execute()) {
			$rowCount = $stmt->rowCount();
			if($rowCount == 1) {
				$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
				$result = $result[0];
				$stmt->closeCursor();
				processConditionalHeaders($result['etag'], $rowCount, $result['last_modified']);
				
				// Delete the resource
				$stmt = $dbconn->prepare("DELETE FROM employee WHERE employeeID=:empID");
				
				/*
				if(($userType === "MASTER" || $userType === "ADMIN") && isset($_GET['userid'])) {
					$stmt->bindParam(':userID', $_GET['userid']);
				} else {
					$uid = $user->getId();
					$stmt->bindParam(':userID', $uid);
				}*/
				$stmt->bindParam(':empID', $employeeId);
				
				if($stmt->execute()) {
					header('HTTP/1.1 204 No Content');
					exit;
				} else {
					header('HTTP/1.1 500 Internal Server Error');
					exit;
				}
			} else {
				processConditionalHeaders(null, $rowCount, null);
				header('HTTP/1.1 204 No Content');
				exit;
			}
		} else {
			header('HTTP/1.1 500 Internal Server Error');
			exit;
		}
	} else {
		header('HTTP/1.1 403 Forbidden');
		exit;
	}
}

function getAll($HTTPverb) { 
	$query = "SELECT * FROM employee";
	
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
	
	$dbconn = getDBConnection();
	$stmt = $dbconn->prepare($query);
	if($stmt->execute()) {
		if($stmt->rowCount() == 0) {
			header('HTTP/1.1 204 No Content');
			exit;
		}

		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$employeeList = array();
		foreach($results as $row) {
			$employee = array();
			$employee[] = $row['employeeID'];
			$employee[] = $row['first_name'];
			$employee[] = $row['last_name'];
			$employee[] = $row['department'];
			$employee[] = $row['full_time'];
			$employee[] = $row['hire_date'];
			$employee[] = $row['salary'];
			$employee[] = $row['etag'];
			$employee[] = $row['last_modified'];
			$employeeList[] = $employee;			
		}		
		$employeeList = Array( "Employees" => $employeeList);
		$output = json_encode($employeeList);
		
		// Set headers
		header('HTTP/1.1 200 OK');
		header('Content-Type: application/json');
		header('Content-Length: '.strlen($output));
		
		if($HTTPverb === "GET") {
			echo $output;
		}
		exit;
	} else {
		header('HTTP/1.1 500 Internal Server Error');
		exit;
	}
}

function get($id, $HTTPverb) {
	$query = "SELECT employeeID, DATE_FORMAT(last_modified, \"%a, %d %b %Y %T GMT\") AS last_modified, last_name, first_name, full_time, hire_date, salary, etag
			FROM employee WHERE employeeID=:empID";
	
	$dbconn = getDBConnection();
	$stmt = $dbconn->prepare($query);
	$stmt->bindParam(':empID', $id);
	if($stmt->execute()) { 
		$rowCount = $stmt->rowCount();
		if($rowCount == 1) {
			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$result = $result[0]; 
			processConditionalHeaders($result['etag'], $rowCount, $result['last_modified']);
			
			// NEED TO MOVE DATA FORMATING TO A FUNCTION
			$output = json_encode($result);			
			
			header('HTTP/1.1 200 OK');
			// NEED TO UPDATE THIS HEADER BASED ON $outputFormat
			header('Content-Type: application/json');

			// CONTENT LENGTH WILL VARY DEPENDING ON OUTPUT FORMAT
			header('Content-Length: '.strlen($output));
			header('Etag: '.$result['etag']);
			header('Last-Modified: '.$result['last_modified']);			
			
			if($HTTPverb === "GET") {
				echo $output;
			}
			exit;
		} else {
			processConditionalHeaders(null, $rowCount, null);
			header('HTTP/1.1 404 Not Found');
			exit;
		}
	} else {
		header('HTTP/1.1 500 Internal Server Error');
		exit;
	}
}

function post() {
	$dbconn = getDBConnection();
	$user = lauthenticateUser($dbconn);
	
	$userType = $user->getType();
	if($userType === "MASTER" || $userType === "ADMIN" || $userType === "USER") {
		
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
				$department = trim($_POST['department']);
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
			/*
			if($mflag) {
				$stmt->bindParam(':userID_FK', $userID_FK);
			} else {
				$uid = $user->getId();
				$stmt->bindParam(':userID_FK', $uid);
			}
			*/
			$stmt->bindParam(':lastName', $lastName);
			$stmt->bindParam(':firstName', $firstName);
			$stmt->bindParam(':department', $department);
			$stmt->bindParam(':fullTime', $fullTime);
			$stmt->bindParam(':hireDate', $hireDate);
			$stmt->bindParam(':salary', $salary);
			
			if($stmt->execute()) {
				$i = $dbconn->lastInsertId();
				$location = $_SERVER['REQUEST_URI'].$i;
				header('HTTP/1.1 201 Created');
				header('Content-Location: '.$location);
				echo $location;
			} else {
				header('HTTP/1.1 500 Internal Server Error');
				exit;
			}
		}
	} else {
		header('HTTP/1.1 403 Forbidden');
		exit;
	}
}

function put($id) {
	if(isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
		header('HTTP/1.1 412 Precondition Failed');
		exit;
	}
	
	$putVar = json_decode(file_get_contents("php://input"), true);
	if(isset($putVar) && array_key_exists('LastName', $putVar) && array_key_exists('FirstName', $putVar)
			&& array_key_exists('Department', $putVar) && array_key_exists('FullTime', $putVar)
			&& array_key_exists('HireDate', $putVar) && array_key_exists('Salary', $putVar)) {header('X-zzzzwtf: '.$putVar['FullTime']);

				
				if(!is_numeric($putVar['Salary'])) {
					header('HTTP/1.1 400 Bad Request');
					exit;
				}				
				$dbconn = getDBConnection();
				$user = lauthenticateUser($dbconn);
				
				$userType = $user->getType();
				if($userType === "MASTER" || $userType === "ADMIN" || $userType === "USER") {
					
					$stmt = $dbconn->prepare("SELECT * FROM employee WHERE employeeID = :empID");
					$stmt->bindParam(':empID', $id);
					$stmt->execute();
					$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
					$results = $results[0];
					$rowCount = $stmt->rowCount();
					$stmt->closeCursor();
					
					$existed = false;
					if($rowCount == 1) { // Update (replace) existing resource
						processConditionalHeaders($results['etag'], $stmt->rowCount(), $results['last_modified']);
						
						$stmt = $dbconn->prepare("UPDATE employee SET last_name=:lastName, first_name=:firstName,
								department=:department, full_time=:fullTime, hire_date=:hireDate, salary=:salary
								WHERE employeeID=:empID");
						$existed = true;
					} else { // Create a new resource
						processConditionalHeaders(null, 0, null);
						
						$stmt = $dbconn->prepare("INSERT INTO employee
								(employeeID, last_name, first_name, department, full_time, hire_date, salary)
								VALUES(:empID, :lastName, :firstName, :department, :fullTime, :hireDate, :salary)");
												
					}
					$stmt->bindParam(':empID', $id);	
					$stmt->bindParam(':lastName',  $putVar['LastName']);
					$stmt->bindParam(':firstName', $putVar['FirstName']);
					$stmt->bindParam(':department', $putVar['Department']);
					$stmt->bindParam(':fullTime', $putVar['FullTime']);header('X-wtf: '.$putVar['FullTime']);
					$stmt->bindParam(':hireDate', $putVar['HireDate']);
					$stmt->bindParam(':salary', $putVar['Salary']);
					
					if($stmt->execute()) {
						if($existed) {
							header('HTTP/1.1 204 No Content');
						} else {
							header('HTTP/1.1 201 Created');
						}
						exit;
					} else {
						header('HTTP/1.1 504 Internal Server Error');
						exit;
					}
				}
			} else {
				header('HTTP/1.1 400 Bad Request');
				exit;
			}
}

function getDBConnection() { 
	try {
		$db = new Database_Connection();
		return $db->getConnection(); 
	} catch(PDOException $e) {
		echo $e->getMessage();
		header('HTTP/1.1 500 Internal Server Error');
		exit;
	}
}

function lauthenticateUser($dbconn) {
	$segments = @explode(':', base64_decode(substr($_SERVER['REDIRECT_HTTP_AUTHORIZATION'], 6)));
	
	if(count($segments) == 2) {
		list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = $segments;
	}
	
	if (!isset($_SERVER['PHP_AUTH_USER']) || ($_SERVER['PHP_AUTH_USER']) == "")  {
		header('WWW-Authenticate: Basic realm="Modintro"');
		header('HTTP/1.0 401 Unauthorized'); 
		// echo 'Text to send if user hits Cancel button<br>';
		exit;
	} else {
		$stmt = $dbconn->prepare("SELECT adminID, email, password, type FROM admin WHERE email=:email");
		$stmt->bindParam(':email', $_SERVER['PHP_AUTH_USER']);
		$stmt->execute();
		
		if($stmt->rowCount() == 1) {
			$result = $stmt->fetch();
			$stmt->closeCursor();
			
			if(password_verify($_SERVER['PHP_AUTH_PW'], $result['password'])) {
				$user =  new User($result['name'], $_SERVER['PHP_AUTH_USER'], $result['type']);
				return $user;
			} else {
				header('HTTP/1.0 401 Unauthorized');
				exit;
			}
			
		} else { // No record found
			header('HTTP/1.0 401 Unauthorized');
			// header('WWW-Authenticate: Basic realm="Modintro"');
			// echo 'Text to send if user hits Cancel button<br>';
			// echo '{ Error:"Not Found", ErrorCode: 333 }';
			exit;
		}
	}
}

?>