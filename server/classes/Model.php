<?php
require_once('View.php');
require_once('DBConnection.php');

/**
 * Model for RestFUL Web Service in a MVC architecture.
 * 
 * @author Craig Spencer <craigspencer@modintro.com>
 *
 */

class Model {
        
        private $view;
        
        public function __construct($view) {
                $this->view = $view;
		date_default_timezone_set('UTC');
        }
        
        // DELETE ALL
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
        
        // DELETE
        function delete($id) {
                $db = new DBConnection();
                $dbconn = $db->getConnection();
                
                // First check record against headers
                $query = "SELECT employeeID, last_name, first_name, department,
                        full_time, hire_date, salary, etag,
                        DATE_FORMAT(last_modified, \"%a, %d %b %Y %T GMT\")
                        AS last_modified FROM employee WHERE employeeID=:empID";
                $stmt = $dbconn->prepare($query);
                $stmt->bindParam(':empID', $id);
                        
                if($stmt->execute()) {
                        $rowCount = $stmt->rowCount();
                        if($rowCount == 1) {
                                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                $result = $result[0];
                                $stmt->closeCursor();
                                Headers::processConditionalHeaders($rowCount, $result['etag'], $result['last_modified']);
                                        
                                // Delete the resource
                                $stmt = $dbconn->prepare("DELETE FROM employee WHERE employeeID=:empID");
                                $stmt->bindParam(':empID', $id);
                                        
                                if($stmt->execute()) {
                                        header('HTTP/1.1 204 No Content');
                                        exit;
                                } else {
                                        header('HTTP/1.1 500 Internal Server Error');
                                        exit;
                                }
                        } else {
                                // Headers::processConditionalHeaders(null, $rowCount, null);
                                header('HTTP/1.1 204 No Content');
                                exit;
                        }
                } else {
                        header('HTTP/1.1 500 Internal Server Error');
                        exit;
                }
        }
        
        // GET or HEAD
        function getAll($HTTPverb) {
                $query = "SELECT employeeID, last_name, first_name, department,
                        full_time, DATE_FORMAT(hire_date, '%Y-%m-%d') AS hire_date,
                        salary, etag, DATE_FORMAT(last_modified, \"%a, %d %b %Y %T GMT\")
                        AS last_modified FROM employee";
                
                $sortBy = array("employeeID", "last_name", "first_name", "department",
                                "full_time", "salary", "hire_date");
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
                                } else if($order === "asc") {
                                        $query .= " ASC";
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
                        if($stmt->rowCount() == 0 && $this->view->outputFormat() != "text/html") {
                                header('HTTP/1.1 204 No Content');                              
                                exit;
                        }                       
                        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        $this->view->respond($results, "Employees");
                } else {
                        header('HTTP/1.1 500 Internal Server Error');
                        exit;
                }
        }
        
        // GET or HEAD
        function get($id, $HTTPverb) { 
                $query = "SELECT employeeID, last_name, first_name, department,
                        full_time, DATE_FORMAT(hire_date, '%Y-%m-%d') AS hire_date,
                        salary, etag, DATE_FORMAT(last_modified, \"%a, %d %b %Y %T GMT\")
                        AS last_modified FROM employee WHERE employeeID=:empID";
                
                $db = new DBConnection();
                $dbconn = $db->getConnection();
                $stmt = $dbconn->prepare($query);
                $stmt->bindParam(':empID', $id);
                if($stmt->execute()) {
                        $rowCount = $stmt->rowCount();
                        if($rowCount == 1) {
                                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                $result = $result[0];
                                Headers::processConditionalHeaders($rowCount, $result['etag'], $result['last_modified']);
                                
                                $this->view->respond($result, "Employee");
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
        
        // POST
        function post() {
                $db = new DBConnection();
                $dbconn = $db->getConnection();
                
                if(!empty($_POST)) {
                        // $mflag = FALSE;
                        /* if(($userType === "MASTER" || $userType === "ADMIN") && isset($_POST['userid_fk'])) {
                                $userID_FK = $_POST['userid_fk'];
                                $mflag = TRUE;
                        } */


                        if(isset($_POST['lname']) && isset($_POST['fname'])
                                && isset($_POST['dept']) && isset($_POST['ftime'])
                                && isset($_POST['hdate']) && isset($_POST['salary'])) {
                                $lastName = trim($_POST['lname']);
                                $firstName = trim($_POST['fname']);
                                $department = trim($_POST['dept']);
                                $fullTime = trim($_POST['ftime']);
                                $hireDate = trim($_POST['hdate']);
                                $salary = trim($_POST['salary']);

				$this->validatePostData($lastName, $firstName, $department,
                                                $fullTime, $hireDate, $salary);
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
                        }*/


                        $stmt->bindParam(':lastName', $lastName, PDO::PARAM_STR);
                        $stmt->bindParam(':firstName', $firstName, PDO::PARAM_STR);
                        $stmt->bindParam(':department', $department, PDO::PARAM_STR);
                        $stmt->bindParam(':fullTime', $fullTime, PDO::PARAM_BOOL);
                        $stmt->bindParam(':hireDate', $hireDate, PDO::PARAM_STR);
                        $stmt->bindParam(':salary', $salary, PDO::PARAM_INT);
                                                
			try {
				$stmt->execute();
				$i = $dbconn->lastInsertId();
				$location = "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].$i;
                                header('HTTP/1.1 201 Created');
                                header('Content-Location: '.$location);
                                echo $location;
				exit;
                        } catch(Exception $e) {
				header('HTTP/1.1 500 Internal Server Error');
				exit;
                        }
                } else {
                        header('HTTP/1.1 400 Bad Request');
                        exit;
                }
        }
        
        // PUT
        function put($id) {
                if(isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
                        header('HTTP/1.1 412 Precondition Failed');
                        exit;
                }
                
                $putVar = json_decode(file_get_contents("php://input"), true);
		if(isset($putVar) && isset($putVar['lastname']) && isset($putVar['firstname'])
                                && isset($putVar['department']) && isset($putVar['fulltime'])
                                && isset($putVar['hiredate']) && isset($putVar['salary'])) {

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
                                Headers::processConditionalHeaders($stmt->rowCount(), $results['etag'], $results['last_modified']);
                                                        
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
                        $stmt->bindParam(':lastName', ucwords(strtolower($putVar['lastname'])));
                        $stmt->bindParam(':firstName', ucwords(strtolower($putVar['firstname'])));
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

				// This code should probably be a function
				$stmt = $dbconn->prepare("SELECT etag,
					 DATE_FORMAT(last_modified, \"%a, %d %b %Y %T GMT\") as lastMod
					 FROM employee WHERE employeeID=:id");
				$stmt->bindParam(':id', $id);
				$stmt->execute();
				$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

				header('ETag: '.$results[0]['etag']);
				header('Last-Modified: '.$results[0]['lastMod']);
				exit;
                        } else {
                                header('HTTP/1.1 504 Internal Server Error');
                                exit;
                        }
                } else {
                        header('HTTP/1.1 400 Bad Request');
                        exit;
                }
        }
        
        function validateNumericFields($a) {
                if(!is_numeric($a['salary'])) {
                        header('HTTP/1.1 400 Bad Request');
                        exit;
                }
        }
        
        function validatePostData($lastName, $firstName, $department,
                        $fullTime, $hireDate, $salary) {
                
                if($fullTime != 0 && $fullTime != 1) {
                        header('HTTP/1.1 400 Bad Request');
                        exit;
                }
        }       
}

/*
if(isset($putVar) && isset('lastname', $putVar) && isset('firstname', $putVar)
                                && isset('department', $putVar) && isset('fulltime', $putVar)
                                && isset('hiredate', $putVar) && isset('salary', $putVar)) {
*/
?>