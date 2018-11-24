<?php

class AcceptMediaType {
	
	private $mime;
	private $qFactor;
	
	function __construct($type) {
		$pos = strpos($type, "q=");
		
		if($pos === false) {
			$this->mime = trim($type);
			$this->qFactor = 1;
		} else {
			$this->mime = trim(substr($type, 0, $pos-1));
			$this->qFactor = substr($type, $pos+2);
		}
	}
	
	/**
	 * Compares two AcceptMediaType objects according to the
	 * HTTP 1.1 specification found at:
	 * https://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
	 *
	 * @param AcceptMediaType $a
	 * @param AcceptMediaType $b
	 * @return -1 if $a < $b, 0 if $a == $b, 1 if $a > $b
	 */
	public static function compare($a, $b) {
		$aq = $a->getQualityFactor();
		$bq = $b->getQualityFactor();
		
		if($aq < $bq) {
			return -1;
		} elseif($aq == $bq) {
			$am = substr_count($a->getMimeType(), "*");
			$bm = substr_count($b->getMimeType(), "*");
			
			if($am == $bm) {
				return 0;
			} elseif($am < $bm) {
				return 1;
			} else {
				return -1;
			}
		} else {
			return 1;
		}
	}
	
	public function getMimeType() {
		return $this->mime;
	}
	
	public function getQualityFactor() {
		return $this->qFactor;
	}
}
?>