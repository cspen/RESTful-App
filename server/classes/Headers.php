<?php
require_once('AcceptMediaType.php');
require_once('Config.php');

class Headers {
	
	public static function processHeaders() {
		processHostHeader();
		processExpectHeader();
		processCharsetHeader();
		processLanguageHeader();
	}
	
	/**
	 * Probably handled by server, but...
	 * According to HTTP/1.1 section 14.23
	 * https://tools.ietf.org/html/rfc2616#section-14.23
	 */
	public static function processHostHeader() {
		if(!isset($_SERVER['HTTP_HOST'])) {
			header('HTTP/1.1 400 Bad Request');
			exit;
		}
	}
	
	/**
	 * This server does not support the Expect header.
	 * According to HTTP/1.1 section 14.20
	 * https://tools.ietf.org/html/rfc2616#section-14.20
	 */
	public static function processExpectHeader() {
		if(isset($_SERVER['HTTP_EXPECT'])) {
			header('HTTP/1.1 417 Expectation Failed');
			exit;
		}
	}
	
	/**
	 *
	 * https://tools.ietf.org/html/rfc2616#section-14.2
	 */
	public static function processCharsetHeader() {
		// Check character encoding
		if(!empty($_SERVER['HTTP_ACCEPT_CHARSET'])) {
			global $CHARSET;
			$sets = explode(",", $_SERVER['HTTP_ACCEPT_CHARSET']);
			
			// This service only uses utf-8
			$f = FALSE;
			foreach($sets as $set) {
				$s = explode(";", $set);echo $s[0].' ';
				if(trim($s[0]) === $CHARSET || trim($s[0] === "*")) {
					$f = TRUE;
					break;
				}
			}
			
			if(!$f) {
				header('HTTP/1.1 406 Not Acceptable');
				exit;
			}
		}
	}
	
	/**
	 *
	 * https://tools.ietf.org/html/rfc2616#section-14.1
	 */
	public static function processAcceptHeader() {
		// Check media type client expects
		if(!empty($_SERVER['HTTP_ACCEPT'])) {
			$types = explode(",", $_SERVER['HTTP_ACCEPT']);
			$media = array();
			
			// First sort Accept header values by quality factor
			foreach($types as $type) {
				$m = new AcceptMediaType($type);
				$media[] = $m;
			}
			usort($media, "AcceptMediaType::compare");
			
			// Find acceptable media type or die
			$bestMatch = "";
			for($i = count($media)-1; $i >= 0; $i--) {
				$m = $media[$i];
				$t = $m->getMimeType();
				
				if(in_array($t, Config::MEDIA_TYPES)) {
					$bestMatch = $t;
					break;
				} elseif($key = array_search($t, Config::MEDIA_WILD)) {
					$bestMatch = $key;
					break;
				} elseif($t === "*/*") {
					$bestMatch = Config::MEDIA_DEFAULT;
					break;
				}
			}
			
			if($bestMatch === "") {
				header('HTTP/1.1 406 Not Acceptable');
				exit;
			}
			return $bestMatch;
		} else {
			// No header sent - assume any media is acceptable
			return Config::MEDIA_DEFAULT;
		}
	}
	
	/**
	 *
	 *
	 * https://tools.ietf.org/html/rfc2616#section-14.4
	 */
	public static function processLanguageHeader() {
		// This method currently does nothing but
		// can be modified to use mulitple languages
		// Check language
		if(!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
			$langs = explode(",", $_SERVER['HTTP_ACCEPT_LANGUAGE']);
			
			$f = FALSE;
			foreach($langs as $lang) {
				$l = explode(";", $lang);
				// echo $l[0].'<br>';
				if(in_array(trim($l[0]), Config::LANGUAGE)) {
					$f = TRUE;
					break;
				}
			}
		}
	}
	
	/**
	 * https://tools.ietf.org/html/rfc2616#section-14.24
	 * @return array
	 */
	public static function processIfMatchHeader() {
		// Process E-tags
		if(isset($_SERVER['HTTP_IF_MATCH'])) {
			$etags = array_map('trim', explode(',', $_SERVER['HTTP_IF_MATCH']));
			return $etags;
		}
		return NULL;
	}
	
	/**
	 * https://tools.ietf.org/html/rfc2616#section-14.26
	 */
	public static function processIfNoneMatchHeader() {
		if(isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
			$etags = array_map('trim', explode(',', $_SERVER['HTTP_IF_NONE_MATCH']));
			return $etags;
		}
		return NULL;
	}
	
	/**
	 *
	 * https://tools.ietf.org/html/rfc2616#section-14.25
	 */
	public static function processIfModifiedSinceHeader() {
		if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
			return strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
		}
	}
	
	/**
	 * https://tools.ietf.org/html/rfc2616#section-14.28
	 */
	public static function processIfUnmodifiedSinceHeader() {
		if(isset($_SERVER['HTTP_IF_UNMODIFIED_SINCE'])) {
			return strtotime($_SERVER['HTTP_IF_UNMODIFIED_SINCE']);
		}
	}
	
	public static function processConditionalHeaders($etag, $rowCount, $lastModified) {
		$ifModSin = processIfModifiedSinceHeader();
		$ifUnmodSin = processIfUnmodifiedSinceHeader();
		$ifMatch = processIfMatchHeader();
		$ifNoneMatch = processIfNoneMatchHeader();
		
		if($ifMatch && !$ifNoneMatch && !$ifModSin) {
			if((in_array('*', $ifMatch) && ($rowCount == 0))) {
				header('HTTP/1.1 412 Precondition Failed');
				exit;
			}  elseif(!in_array($etag, $ifMatch)) {
				header('HTTP/1.1 412 Precondition Failed');
				exit;
			}
		} elseif($ifNoneMatch && !$ifMatch && !$ifUnmodSin) {
			if(in_array($etag, $ifNoneMatch) || in_array("*", $ifNoneMatch)) {
				if($ifModSin > strtotime($lastModified)) {
					header('HTTP/1.1 304 Not Modified');
					header('Etag: '.$etag);
					header('Last-Modified: '.$lastModified);
					exit;
				}
			}
		} elseif($ifModSin && !$ifMatch && !$ifUnmodSin) {
			if($ifModSin > strtotime($lastModified)) {
				header('HTTP/1.1 304 Not Modified');
				header('Etag: '.$etag);
				header('Last-Modified: '.$lastModified);
				exit;
			}
		} elseif($ifUnmodSin && !$ifNoneMatch && !$ifModSin) {
			if($ifUnmodSin < strtotime($lastModified)) {
				header('HTTP/1.1 412 Precondition Failed');
				header('Etag: '.$etag);
				header('Last-Modified: '.$lastModified);
				exit;
			}
		}
	}
	
	/**
	 * Compare two etags using strong comparison
	 * according to:
	 * https://tools.ietf.org/html/rfc2616#section-13.3.3
	 *
	 * @param first etag 		$tag1
	 * @param second etag 		$tag2
	 * @return boolean *
	 */
	public static function compareEtags($tag1, $tag2) {
		if($tag1 === "*" || $tag2 === "*") {
			return TRUE;
		}
		$arg1 = str_split($tag1);
		$arg2 = str_split($tag2);
		
		$size1 = count($arg1);
		if($size1 != count($arg1)) {
			return FALSE;
		}
		
		for($i = 0; $i < $size1; $i++) {
			if($arg1[$i] !== $arg2[$i]) {
				return FALSE;
			}
		}
		return TRUE;
	}
	
	
	
	public static function getLastModified($dbconn, $tableName) {
		$stmt = $dbconn->prepare('SELECT DATE_FORMAT(last_modified, "%a, %d %b %Y %T GMT") AS lm FROM table_metadata WHERE table_name=:table');
		$stmt->bindParam(":table", $tableName);
		if($stmt->execute()) {
			$result = $stmt->fetch();
			return $result['lm'];
		} else {
			return null;
		}
	}
	
	
	
	public static function validateDateTime($datetime) {
		if(preg_match('/^\d{4}-\d{1,2}-\d{1,2}\s\d{1,2}:\d{1,2}:\d{1,2}$/', $datetime)) {
			$dt = explode(" ", $datetime);
			$date = explode("-", $dt[0]);
			
			if(checkdate($date[1], $date[2], $date[0])) {
				$time = explode(":", $dt[1]);
				if($time[0] > 23 || $time[1] > 59 || $time[2] > 59) {
					return FALSE;
				}
				return TRUE;
			} else {
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}
}
?>