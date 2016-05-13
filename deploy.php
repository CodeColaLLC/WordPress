<?php
$body = @file_get_contents('php://input');

if (
	strpos($_SERVER['HTTP_USER_AGENT'], 'GitHub-Hookshot') === false ||
	hash_equals($_SERVER['HTTP_X_HUB_SIGNATURE'], verify_request($body))
) {
	header('HTTP/1.1 403 Forbidden');
	die('Invalid request.');
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

die('Deployment successful.');
