<?php
/**
 * Process the HTTP request headers.
 *
 * @author Craig Spencer <craigspencer@modintro.com>
 */

// Acceptable media types, languages, etc. are stored in
// appSettings.php
require '../utilities/appSettings.php';

// AcceptMediaType class definition
require '../classes/AcceptMediaType.php';

/**
 * Probably handled by server, but...
 * According to HTTP/1.1 section 14.23
 * https://tools.ietf.org/html/rfc2616#section-14.23
 */
function processHostHeader() {	
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
function processExpectHeader() {
	if(isset($_SERVER['HTTP_EXPECT'])) {		
		header('HTTP/1.1 417 Expectation Failed');
		exit;
	}
}

/**
 * Ensure this server can provide the character set
 * requested by the client. The Accept-Charset header
 * is explained at:
 * https://tools.ietf.org/html/rfc2616#section-14.2
 */
function processCharsetHeader() {
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
 * Determine if this server can respond using the media
 * format requested by the client. The Accept header is
 * exlained at:
 * https://tools.ietf.org/html/rfc2616#section-14.1
 *
 * @return String
 */
function processAcceptHeader() {
	// Check media type client expects
	if(!empty($_SERVER['HTTP_ACCEPT'])) {
		global $MEDIA_TYPES, $MEDIA_WILD;
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
			
			if(in_array($t, $MEDIA_TYPES)) {
				$bestMatch = $t;
				break;
			} elseif($key = array_search($t, $MEDIA_WILD)) {
				$bestMatch = $key;
				break;
			} elseif($t === "*/*") {
				$bestMatch = MEDIA_DEFAULT;
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
		return MEDIA_DEFAULT;
	}
}

/**
 * Check if language request by client is available on this
 * server. The Accept-Language header is explained at:
 * https://tools.ietf.org/html/rfc2616#section-14.4
 */
function processLanguageHeader() {
	// This method currently does nothing but
	// can be modified to use mulitple languages
	// Check language
	if(!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
		global $LANGUAGE;
		$langs = explode(",", $_SERVER['HTTP_ACCEPT_LANGUAGE']);
		
		$f = FALSE;
		foreach($langs as $lang) {
			$l = explode(";", $lang);
			// echo $l[0].'<br>';
			if(in_array(trim($l[0]), $LANGUAGE)) {
				$f = TRUE;
				break;
			}
		}
	}
}

/**
 * Return all etags found in the If-Match header. The purpose
 * of this header is explained at:
 * https://tools.ietf.org/html/rfc2616#section-14.24
 *
 * @return String array
 */
function processIfMatchHeader() {
	// Process E-tags
	if(isset($_SERVER['HTTP_IF_MATCH'])) {
		$etags = array_map('trim', explode(',', $_SERVER['HTTP_IF_MATCH']));
		return $etags;
	}
	return NULL;
}

/**
 * Return all etags found in the If-None-Match header. The purpose
 * of this header is explained at:
 * https://tools.ietf.org/html/rfc2616#section-14.26
 *
 * @return String array
 */
function processIfNoneMatchHeader() {
	if(isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
		$etags = array_map('trim', explode(',', $_SERVER['HTTP_IF_NONE_MATCH']));
		return $etags;
	}
	return NULL;
}

/**
 * Convert the date time in the If-Modified-Since header
 * to a Unix timestamp. The purpose of this headers is 
 * explained at:
 * https://tools.ietf.org/html/rfc2616#section-14.25 
 *
 * @return int
 */
function processIfModifiedSinceHeader() {
	if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {			
		return strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
	}
}

/**
 * Convert the date time in the If-Unmodified-Since header
 * to a Unix timestamp. The purpose of this headers is 
 * explained at:
 * https://tools.ietf.org/html/rfc2616#section-14.28
 *
 * @return int
 */
function processIfUnmodifiedSinceHeader() {
	if(isset($_SERVER['HTTP_IF_UNMODIFIED_SINCE'])) {
		return strtotime($_SERVER['HTTP_IF_UNMODIFIED_SINCE']);
	}
}

/**
 * A convenience function for processing the If-Modified-Since, If-Unmodified-Since,
 * If-Match, and If-None-Match headers.
 *
 * @param String $etag
 * @param int $rowCount
 * @param String $lastModified	date of 
 */
function processConditionalHeaders($etag, $rowCount, $lastModified) {
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
 * @param String $tag1	first etag to compare
 * @param String $tag2	second etag to compare
 * @return boolean
 */
function compareEtags($tag1, $tag2) {
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
?>