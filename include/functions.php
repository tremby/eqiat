<?php

// array_filter recursively
function array_filter_recursive($input, $callback) {
	$output = array();
	foreach ($input as $key => $value) {
		if (is_array($value))
			$output[$key] = array_filter_recursive($value, $callback);
		else
			$output[$key] = call_user_func($callback, $value);
	}
	return $output;
}

// if magic quotes get/post/cookie is on, undo it by stripping slashes from each
function unmagic() {
	if (get_magic_quotes_gpc()) {
		$_GET = array_filter_recursive($_GET, "stripslashes");
		$_POST = array_filter_recursive($_POST, "stripslashes");
		$_COOKIE = array_filter_recursive($_COOKIE, "stripslashes");
	}
}

//exit with various HTTP statuses, most useful for Ajax-------------------------
function servererror($message = "server error") {
	header("Content-Type: text/plain", true, 500);
	if (is_array($message))
		foreach ($message as $m)
			echo "- " . $m . "\n";
	else
		echo $message . "\n";
	exit;
}
function badrequest($message = "bad request", $mimetype = "text/plain") {
	header("Content-Type: $mimetype", true, 400);
	if (is_array($message))
		foreach ($message as $m)
			echo "- " . $m . "\n";
	else
		echo $message . "\n";
	exit;
}
function ok($message = null, $mimetype = "text/plain") {
	if (is_null($message))
		header("Content-Type: text/plain", true, 204);
	else {
		header("Content-Type: $mimetype", true, 200);
		echo $message;
	}
	exit;
}
function notfound($message = "404: not found", $mimetype = "text/plain") {
	header("Content-Type: $mimetype", true, 404);
	echo $message;
	exit;
}

// make a string safe for XML
function xmlspecialchars($text) {
	return str_replace('&#039;', '&apos;', htmlspecialchars($text, ENT_QUOTES));
}

// add one SimpleXML tree to another
function simplexml_append(SimpleXMLElement $parent, SimpleXMLElement $new_child) {
	$node1 = dom_import_simplexml($parent);
	$dom_sxe = dom_import_simplexml($new_child);
	$node2 = $node1->ownerDocument->importNode($dom_sxe, true);
	$node1->appendChild($node2);
}

// show an array of messages as HTML
function showmessages($messages, $title = "Message", $class = null) {
	if (!empty($messages)) { ?>
		<div<?php if (!is_null($class)) { ?> class="<?php echo $class; ?>"<?php } ?>>
			<h3><?php echo htmlspecialchars($title); ?></h3>
			<ul>
				<?php foreach ($messages as $message) { ?>
					<li><?php echo htmlspecialchars($message); ?></li>
				<?php } ?>
			</ul>
		</div>
	<?php }
}

// validate a string of QTI XML or SimpleXML element
// $errors, $warnings and $messages should be arrays
function validateQTI($xml, &$errors, &$warnings, &$messages) {
	if ($xml instanceof SimpleXMLElement)
		$xml = $xml->asXML();

	$pipes = null;
	$validate = proc_open("./run.sh", array(array("pipe", "r"), array("pipe", "w"), array("pipe", "w")), $pipes, SITEROOT_LOCAL . "validate");
	if (!is_resource($validate)) {
		$errors[] = "Failed to start validator";
		return false;
	}

	// give QTI on stdin and close the pipe
	fwrite($pipes[0], $xml);
	fclose($pipes[0]);

	// get contents of stdout and stderr
	$stdout = trim(stream_get_contents($pipes[1]));
	fclose($pipes[1]);
	$stderr = trim(stream_get_contents($pipes[2]));
	fclose($pipes[2]);

	$exitcode = proc_close($validate);

	if (!empty($stderr))
		$errors = array_merge($errors, explode("\n", $stderr));
	if (!empty($stdout)) {
		$stdout = explode("\n", $stdout);
		foreach ($stdout as $message) {
			$parts = explode("\t", $message);
			switch ($parts[0]) {
				case "Error":
					$errors[] = "Validator error: {$parts[1]} ({$parts[2]})";
					break;
				case "Warning":
					$warnings[] = "Validator warning: {$parts[1]} ({$parts[2]})";
					break;
				default:
					$messages[] = "Validator message: {$parts[1]} ({$parts[2]})";
			}
		}
	}

	if (empty($errors) && $exitcode != 0)
		$errors[] = "Validator exited with code $exitcode";

	return $exitcode == 0;
}

// redirect to another URL
function redirect($destination = null, $anal = true, $permanent = false, $textonly = false) {
	session_write_close();
	if ($permanent)
		header("HTTP/1.1 301 Moved Permamently");

	if (is_null($destination))
		$destination = $_SERVER["REQUEST_URI"];

	// HTTP spec says location has to be absolute. If we started with a slash, 
	// assume it started with the siteroot and so we can prepend the site's 
	// domain.
	if ($destination[0] == "/")
		$destination = "http://" . $_SERVER["HTTP_HOST"] . $destination;

	header("Location: " . $destination);
	if ($anal)
		die("Tried and failed to redirect you. No worries – just follow this link: $destination\n");
}

?>
