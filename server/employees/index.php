<?php
/* /announcements/index.php */

require_once '../../utilities/tools.php';
require_once '../../utilities/appSettings.php';
require_once '../../classes/User.php';

// Check if Content-Type  and character set requested by
// client are available
processHeaders();

// Dissect the url and grab http verb
$params = explode("/", $_SERVER['REQUEST_URI']);
$HTTPVerb = $_SERVER['REQUEST_METHOD'];

// Separate URL from query string
$requestURI = explode("?", $_SERVER['REQUEST_URI']);
$requestURI = $requestURI[0];

if(preg_match('/^\/announcements\/$/', $requestURI)) {
	/* URL:	/announcements/ */

	switch($_SERVER['REQUEST_METHOD']) {
		case "DELETE":
			deleteAnnouncements();
		case "GET":
		case "HEAD":
			getAnnouncements($HTTPVerb);
		case "OPTIONS":
			header("HTTP/1.1 200 OK");
			header("Allow: DELETE, GET, HEAD, POST, PUT");
			exit;
		case "POST":
			postAnnouncement();
		case "PUT":
			putAnnouncements();
		default:
			header("HTTP/1.1 405 Method Not Allowed");
			header("Allow:  DELETE, GET, HEAD, OPTIONS, POST, PUT");
			exit;
	}

} elseif(preg_match('/^\/announcements\/[0-9]+$/', $requestURI)) {
	/* URL:	/announcements/{announcementID}	*/
	
	$announcementId = end($params);

	switch($_SERVER['REQUEST_METHOD']) {
		case "DELETE":
			deleteAnnouncement($announcementId);
		case "GET":
		case "HEAD":
			getAnnouncement($HTTPVerb, $announcementId);
		case "OPTIONS":
			header("HTTP/1.1 200 OK");
			header("Allow: DELETE, GET, HEAD, PUT");
			exit;
		case "PUT":
			putAnnouncement($announcementId);
		default:
			header("HTTP/1.1 405 Method Not Allowed");
			header("Allow:  DELETE, GET, HEAD, OPTIONS, PUT");
			exit;
	}

} else {
	header('HTTP/1.1 404 Not Found');
	exit;
}

/* URL: /announcements/ */
function deleteAnnouncements() {
	$dbconn = getDatabaseConnection();
	$user = authenticateUser($dbconn);
	
	if($user->getType() === "MASTER") {
		$query = "ALTER TABLE announcement AUTO_INCREMENT = 1; DELETE FROM announcement";
		$fromFlag = $toFlag = FALSE;
		if(isset($_GET['from'])) {
			if(!is_numeric($_GET['from'])) {
				header('HTTP/1.1 400 Bad Request');
				exit;
			}
			$query .= " WHERE announcementID >= :fromID";
			$fromFlag = TRUE;
		}
		if(isset($_GET['to'])) {
			if(!is_numeric($_GET['to'])) {
				header('HTTP/1.1 400 Bad Request');
				exit;
			}
			$toFlag = TRUE;
			if($fromFlag) {
				$query .= " AND announcementID <= :toID";
			} else {
				$query .= " WHERE announcementID <= :toID";
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

function getAnnouncements($verb) {
	$query = "SELECT announcementID, userID_FK, DATE_FORMAT(last_modified, \"%a, %d %b %Y %T GMT\") AS last_modified, date, headline, body, previous, allow_comments, deleted, etag
 			FROM announcement WHERE deleted=FALSE";
	
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
		$aList = array();
		foreach($results as $row) {
			$announcement = array();
			$announcement[] = $row['announcementID'];
			$announcement[] = $row['userID_FK'];
			$announcement[] = $row['date'];
			$announcement[] = $row['headline'];
			$announcement[] = $row['body'];
			$announcement[] = $row['previous'];
			$announcement[] = $row['allow_comments'];
			$announcement[] = $row['deleted'];
			$announcement[] = $row['etag'];
			$announcement[] = $row['last_modified'];
			$aList[] = $announcement;
		}
		$aList = Array( "Announcements" => $aList);
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

function postAnnouncement() {
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
			if(isset($_POST['headline']) && isset($_POST['body'])) {
				$headline = trim($_POST['headline']);
				$body = $_POST['body'];
				if(isset($_POST['comments'])) {
					$allowComments = $_POST['comments'];
				} else {
					$allowComments = null;
				}
				if(isset($_POST['previous'])) {
					$previous = $_POST['previous'];
				} else {
					$previous = null;
				}
			} else {
				header('HTTP/1.1 400 Bad Request');
				exit;
			}

			$stmt = $dbconn->prepare("INSERT INTO announcement
				(userID_FK, date, headline, body, previous, allow_comments)
				VALUES(:userID_FK, NOW(), :headline, :body, :previous, :allowComments)");	
				
			if($mflag) {
				$stmt->bindParam(':userID_FK', $userID_FK);
			} else {
				$uid = $user->getId();
				$stmt->bindParam(':userID_FK', $uid);
			}
			$stmt->bindParam(':headline', $headline);
			$stmt->bindParam(':body', $body);
			$stmt->bindParam(':previous', $previous);
			$stmt->bindParam(':allowComments', $allowComments);
			
			if($stmt->execute()) {
				$i = $dbconn->lastInsertId();
				$location = "http://".$_SERVER[HTTP_HOST].$_SERVER['REQUEST_URI'].$i;
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

function putAnnouncements() {
	if($input = json_decode(file_get_contents("php://input"), true)) {
		$dbconn = getDatabaseConnection();
		$user = authenticateUser($dbconn);
		
		$userType = $user->getType();
		if($userType === "MASTER" || $userType === "ADMIN") {
			
			if(isset($input['Announcements'])) {
				$announcements = $input['Announcements'];
			} else {
				header('HTTP/1.1 400 Bad Request');
				exit;
			}
			
			$sql = 'ALTER TABLE announcement AUTO_INCREMENT = 1; INSERT INTO announcement (userID_FK, date, headline, body,
					 previous, allow_comments, deleted) VALUES ';
			$count = count($announcements);
			for($i = 0; $i < $count; $i++) {
				if(isset($announcements[$i]['Date']) && isset($announcements[$i]['Headline'])
						&& isset($announcements[$i]['Body']) && isset($announcements[$i]['Previous'])
						&& isset($announcements[$i]['AllowComments']) && isset($announcements[$i]['Deleted'])) {
							
							validateNumericFields($announcements[$i]);
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
						$stmt = $dbconn->prepare("DELETE FROM announcement WHERE userID_FK=:userID");
						$stmt->bindParam(':userID', $guid);
					} else {
						$stmt = $dbconn->prepare("DELETE FROM announcement");
					}					
				}			
				
				if($stmt->execute()) {
					$stmt->closeCursor();
				
					$stmt = $dbconn->prepare($sql);
					$count = count($announcements);
					$pos = 0;
					foreach($announcements as $a) {
						if($userId && $userType === "MASTER" || $userType === "ADMIN") {
							$stmt->bindParam(++$pos, $guid);
						} else {
							$stmt->bindParam(++$pos, $a['UserID']);
						}
						$stmt->bindParam(++$pos, $a['Date']);
						$stmt->bindParam(++$pos, $a['Headline']);
						$stmt->bindParam(++$pos, $a['Body']);
						$stmt->bindParam(++$pos, $a['Previous']);
						$stmt->bindParam(++$pos, $a['AllowComments']);
						$stmt->bindParam(++$pos, $a['Deleted']);
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
/* END URL: /announcements/ */


/* URL:	/announcements/{announcementID}	*/
function deleteAnnouncement($announcementId) { 
	$dbconn = getDatabaseConnection();
	$user = authenticateUser($dbconn);
	
	$userType = $user->getType();
	if($userType === "MASTER" || $userType === "ADMIN" || $userType === "USER") {
		// First check record against headers
		$stmt = $dbconn->prepare("SELECT userID_FK, DATE_FORMAT(last_modified, \"%a, %d %b %Y %T GMT\") AS last_modified, date, headline, body, previous, allow_comments, deleted, etag
						FROM announcement WHERE userID_FK=:userID AND announcementID=:announcementID");
		
		if(($userType === "MASTER" || $userType === "ADMIN") && isset($_GET['userid'])) {
			$stmt->bindParam(':userID', $_GET['userid']);
		} else {
			$uid = $user->getId();
			$stmt->bindParam(':userID', $uid);
		}
		$stmt->bindParam(':announcementID', $announcementId);
		
		if($stmt->execute()) {
			$rowCount = $stmt->rowCount();
			if($rowCount == 1) { 
				$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
				$result = $result[0];
				$stmt->closeCursor();
				processConditionalHeaders($result['etag'], $rowCount, $result['last_modified']);
				
				// Delete the resource
				$stmt = $dbconn->prepare("DELETE FROM announcement WHERE userID_FK=:userID AND announcementID=:announcementID");
				
				if(($userType === "MASTER" || $userType === "ADMIN") && isset($_GET['userid'])) {
					$stmt->bindParam(':userID', $_GET['userid']);
				} else {
					$uid = $user->getId();
					$stmt->bindParam(':userID', $uid);
				}
				$stmt->bindParam(':announcementID', $announcementId);
				
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

function getAnnouncement($verb, $announcementId) {	
	$query = "SELECT announcementID, userID_FK, DATE_FORMAT(last_modified, \"%a, %d %b %Y %T GMT\") AS last_modified, date, headline, body, previous, allow_comments, deleted, etag
			FROM announcement WHERE announcementID=:announcementID";
	
	$dbconn = getDatabaseConnection();	
	$stmt = $dbconn->prepare($query);
	$stmt->bindParam(':announcementID', $announcementId);
	if($stmt->execute()) {
		$rowCount = $stmt->rowCount();
		if($rowCount == 1) {
			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$result = $result[0];
			processConditionalHeaders($result['etag'], $rowCount, $result['last_modified']);
			
			$output = json_encode($result);
			
			header('HTTP/1.1 200 OK');
			header('Content-Type: application/json');
			header('Content-Length: '.strlen($output));
			header('Etag: '.$result['etag']);
			header('Last-Modified: '.$result['last_modified']); 
			
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

function putAnnouncement($announcementId) {	
	$putVar = json_decode(file_get_contents("php://input"), true);
	if(isset($putVar) && array_key_exists('Headline', $putVar) && array_key_exists('Body', $putVar)
			&& array_key_exists('Previous', $putVar) && array_key_exists('AllowComments', $putVar)
			&& array_key_exists('Deleted', $putVar)) {
				
		validateNumericFields($putVar);
		
		$dbconn = getDatabaseConnection();
		$user = authenticateUser($dbconn);
		
		$userType = $user->getType();
		if($userType === "MASTER" || $userType === "ADMIN" || $userType === "USER") {
			
			$stmt = $dbconn->prepare("SELECT * FROM announcement WHERE announcementID = :announcementID");
			$stmt->bindParam(':announcementID', $announcementId);
			$stmt->execute();
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$results = $results[0];
			$rowCount = $stmt->rowCount();
			$stmt->closeCursor();			
			
			$existed = false;
			if($rowCount == 1) { // Update (replace) existing resource				
				processConditionalHeaders($results['etag'], $stmt->rowCount(), $results['last_modified']);
				
				$stmt = $dbconn->prepare("UPDATE announcement SET date=NOW(), headline=:headline, body=:body,
								previous=:previous, allow_comments=:allowComments, userID_FK=:userID, deleted=:deleted
								WHERE announcementID=:announcementID");	
				$existed = true;				
			} else { // Create a new resource
				processConditionalHeaders(null, 0, null);
				
				$stmt = $dbconn->prepare("INSERT INTO announcement
								(announcementID, userID_FK, date, headline, body, previous, deleted, allow_comments)
								VALUES(:announcementID, :userID, NOW(), :headline, :body, :previous, :deleted, :allowComments)");
			}
			
			$uid = $user->getId();
			$stmt->bindParam(':announcementID', $announcementId);
			$stmt->bindParam(':userID', $uid);
			$stmt->bindParam(':headline', $putVar['Headline']);
			$stmt->bindParam(':body', $putVar['Body']);
			$stmt->bindParam(':allowComments', $putVar['AllowComments']);
			$stmt->bindParam(':previous', $putVar['Previous']);
			$stmt->bindParam(':deleted', $putVar['Deleted']);
			
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

function validateNumericFields($a) {
	if(!is_numeric($a['Previous']) || !is_numeric($a['AllowComments'])
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
/* END URL:	/announcements/{announcementID}	*/
?>