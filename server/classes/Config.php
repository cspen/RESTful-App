<?php

interface Config {
	// Default media type offered by this server
	const MEDIA_DEFAULT = "application/json";
	
	// Acceptable request header values
	const MEDIA_TYPES = array('application/json', 'text/xml', 'application/xml', 'text/html');
	const MEDIA_WILD = array('application/json' => 'application/*', 'text/html' => 'text/*');
	
	const LANGUAGE = array('en-US', 'en');
	const CHARSET = "utf-8";
}