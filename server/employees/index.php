<?php
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
			header("Allow: DELETE, GET, HEAD, POST, PUT");
			exit;
		case "POST":
			post();
		case "PUT":
			putAll();
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
	$dbconn = getDatabaseConnection();
	$user = authenticateUser($dbconn);
	
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
	$dbconn = getDatabaseConnection();
	$user = authenticateUser($dbconn);
	
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
	$query = "SELECT * FROM customer";
	
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
	
	$dbconn = getDatabaseConnection();
	$stmt = $dbconn->prepare($query);
	if($stmt->execute()) {
		if($stmt->rowCount() == 0) {
			header('HTTP/1.1 204 No Content');
			exit;
		}
		
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$customerList = array();
		foreach($results as $row) {
			$customer = array();
			$customer[] = $row['customerID'];
			$customer[] = $row['first_name'];
			$customer[] = $row['last_name'];
			$customer[] = $row['department'];
			$customer[] = $row['full_time'];
			$customer[] = $row['hire_date'];
			$customer[] = $row['salary'];
			$customer[] = $row['etag'];
			$customer[] = $row['last_modified'];
			$customerList[] = $customer;
		}
		$customerList = Array( "Customers" => $aList);
		$output = json_encode($aList);
		
		// Set headers
		header('HTTP/1.1 200 OK');
		header('Content-Type: application/json');
		header('Content-Length: '.strlen($output));
		
		if($verb === "GET") {
			echo $output;
		}
		exit;
	} else {
		header('HTTP/1.1 500 Internal Server Error');
		exit;
	}
}

function get($id, $HTTPverb) {
	$query = "SELECT * FROM employee WHERE employeeID=:empID";
	
	$dbconn = getDatabaseConnection();
	$stmt = $dbconn->prepare($query);
	$stmt->bindParam(':empID', $employeeId);
	if($stmt->execute()) {
		$rowCount = $stmt->rowCount();
		if($rowCount == 1) {
			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$result = $result[0];
			processConditionalHeaders($result['etag'], $rowCount, $result['last_modified']);
			
			// ****** NEED TO MOVE DATA FORMATING TO A FUNCTION
			$output = json_encode($result);			
			
			// ***** NEED TO UPDATE THIS HEADER BASED ON $outputFormat
			header('Content-Type: application/json');
			header('Content-Length: '.strlen($output));
			header('Etag: '.$result['etag']);
			header('Last-Modified: '.$result['last_modified']);
			header('HTTP/1.1 200 OK');
			
			if($verb === "GET") {
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
	$dbconn = getDatabaseConnection();
	$user = authenticateUser($dbconn);
	
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
	} else {
		header('HTTP/1.1 403 Forbidden');
		exit;
	}
}

function putAll() {
	if($input = json_decode(file_get_contents("php://input"), true)) {
		$dbconn = getDatabaseConnection();
		$user = authenticateUser($dbconn);
		
		$userType = $user->getType();
		if($userType === "MASTER" || $userType === "ADMIN") {
			
			if(isset($input['Employees'])) {
				$announcements = $input['Employees'];
			} else {
				header('HTTP/1.1 400 Bad Request');
				exit;
			}
			
			$sql = 'INSERT INTO employee (employeeID, last_name, first_name, department,
					 full_time, hire_date, salary) VALUES ';
			$count = count($employees);
			for($i = 0; $i < $count; $i++) {
				if(isset($employees[$i]['employeeid']) && isset($employees[$i]['lastname'])
						&& isset($employees[$i]['firstname']) && isset($employees[$i]['department'])
						&& isset($employees[$i]['fulltime']) && isset($employees[$i]['hiredate'])
						&& isset($employees[$i]['salary'])) {
							
							validateNumericFields($employees[$i]);
							$sql .= '(?, ?, ?, ?, ?, ?, ?)';
							if($i < ($count - 1)) {
								$sql .= ', ';
							}
						} else {
							header('HTTP/1.1 400 Bad Request');
							exit;
						}
			}
			
			try {
				if(isset($_GET['userid'])) {
					$guid = $_GET['userid'];
				} else {
					$guid = false;
				}
				
				// Should use transaction but can't with MyISAM
				if($userType === "MASTER" || $userType === "ADMIN") {
					if($guid) {
						$stmt = $dbconn->prepare("DELETE FROM employee WHERE userID_FK=:userID");
						$stmt->bindParam(':userID', $guid);
					} else {
						$stmt = $dbconn->prepare("DELETE FROM employee");
					}
				}
				
				if($stmt->execute()) {
					$stmt->closeCursor();
					
					$stmt = $dbconn->prepare($sql);
					$count = count($employees);
					$pos = 0;
					foreach($employees as $emp) {
						if($userId && $userType === "MASTER" || $userType === "ADMIN") {
							$stmt->bindParam(++$pos, $guid);
						} else {
							$stmt->bindParam(++$pos, $emp['UserID']);
						}
						$stmt->bindParam(++$pos, $emp['employeeID']);
						$stmt->bindParam(++$pos, $emp['LastName']);
						$stmt->bindParam(++$pos, $emp['FirstName']);
						$stmt->bindParam(++$pos, $emp['Department']);
						$stmt->bindParam(++$pos, $emp['FullTime']);
						$stmt->bindParam(++$pos, $emp['HireDate']);
						$stmt->bindParam(++$pos, $emp['Salary']);
					}
				} else {
					header('500 Internal Server Error');
					exit;
				}
				
				if($stmt->execute()) {
					header('HTTP/1.1 204 No Content');
				} else {
					header('HTTP/1.1 500 Internal Server Error');
				}
				exit;
			} catch(PDOException $e) {
				echo $e->getMessage();
			}
		} else {
			header('HTTP/1.1 403 Forbidden');
			exit;
		}
	} else {
		header('HTTP/1.1 400 Bad Request');
		exit;
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
				
				validateNumericFields($putVar);
				
				$dbconn = getDatabaseConnection();
				$user = authenticateUser($dbconn);
				
				$userType = $user->getType();
				if($userType === "MASTER" || $userType === "ADMIN" || $userType === "USER") {
					
					$stmt = $dbconn->prepare("SELECT * FROM employee WHERE employeeID = :empID");
					$stmt->bindParam(':empID', $employeeId);
					$stmt->execute();
					$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
					$results = $results[0];
					$rowCount = $stmt->rowCount();
					$stmt->closeCursor();
					
					if($rowCount == 1) { // Update (replace) existing resource
						processConditionalHeaders($results['etag'], $stmt->rowCount(), $results['last_modified']);
						
						$stmt = $dbconn->prepare("UPDATE employee SET last_name=:lastName, first_name=:firstName,
								department=:department, full_time=:fullTime, hire_date=:hireDate, salary=:salary,
								WHERE employeeID=:empID");
					} else { // Create a new resource
						processConditionalHeaders(null, 0, null);
						
						$stmt = $dbconn->prepare("INSERT INTO employee
								(employeeID, last_name, first_name, department, full_time, hire_date, salary)
								VALUES(:empID, :lastName, :firstName, :department, :fullTime, :hireDate, :salary)");
						$stmt->bindParam(':employeeID', $announcementId);
					}
										
					$stmt->bindParam(':lastName', $uid);
					$stmt->bindParam(':firstName', $putVar['Headline']);
					$stmt->bindParam(':department', $putVar['Body']);
					$stmt->bindParam(':fullTime', $putVar['AllowComments']);
					$stmt->bindParam(':hireDate', $putVar['Previous']);
					$stmt->bindParam(':salary', $putVar['salary']);
					
					if($stmt->execute()) {
						header('HTTP/1.1 204 No Content');
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

function validateNumericFields($a) {
	if(!is_numeric($a['salary']) || !is_numeric($a['AllowComments'])
			|| !is_numeric($a['Deleted'])) {
				header('HTTP/1.1 400 Bad Request');
				exit;
			}
			if($a['Previous'] > 1 || $a['Previous'] < 0 || $a['AllowComments'] > 1
					|| $a['AllowComments'] < 0 || $a['Deleted'] > 1 || $a['Deleted'] < 0) {
						header('HTTP/1.1 400 Bad Request');
						exit;
					}
}
?>