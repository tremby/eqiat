<?php

// error reporting
error_reporting(E_ALL | E_STRICT);

// constants
require_once "include/constants.php";

// class autoloader
function __autoload($classname) {
	$path = "classes/$classname.class.php";
	if (dirname($path) == "classes" && file_exists($path))
		require_once $path;
	else {
		$path = "classes/itemtypes/$classname.class.php";
		if (dirname($path) == "classes/itemtypes" && file_exists($path))
			require_once $path;
	}
}

// set up include path
ini_set("include_path", ".:" . SITEROOT_LOCAL . "include");

// common functions
require_once "include/functions.php";

// character encoding
mb_internal_encoding("UTF-8");

// default timezone
date_default_timezone_set("Europe/London");

// undo any magic quotes
unmagic();

// start sessions
session_start();

// serve the page
$page = isset($_GET["page"]) ? $_GET["page"] : "home";
switch ($page) {
	default:
		if (dirname("content/$page.php") == "content" && file_exists("content/$page.php"))
			include "content/$page.php";
		else
			notfound();
}

?>
