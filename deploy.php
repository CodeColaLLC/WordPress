<?php
$body = @file_get_contents('php://input');

if (
	strpos($_SERVER['HTTP_USER_AGENT'], 'GitHub-Hookshot') === false ||
	hash_equals($_SERVER['HTTP_X_HUB_SIGNATURE'], verify_request($body))
) {
	header('HTTP/1.1 403 Forbidden');
	die('Request did not originate from GitHub-Hookshot with the correct secret.');
}

try {
	$input = json_parse(file_get_contents('php://input'));
	if ($input->ref !== 'refs/heads/master') {
		die('Push was not to master branch. Canceling deploy.');
	}
} catch (Exception $ex) {
	header('HTTP/1.1 400 Bad Request');
	die('Body of request could not be parsed as JSON or was missing `ref` property.');
}

foreach (array('git pull origin master') as $command) {
	shell_exec($command);
}

function verify_request ($body) {
	return 'sha1=' . hash_hmac('sha1', $body, $_ENV['GIT_TOKEN']);
}

function hash_equals ($a, $b) {
	$a_length = strlen($a);
	if ($a_length !== strlen($b)) { return false; }
	
	$result = 0;
	for ($i = 0; $i < $a_length; $i++) {
		$result |= ord($a[$i]) ^ ord($b[$i]);
	}
	
	return $result === 0;
}

die('Deployment of master branch was successful.');
