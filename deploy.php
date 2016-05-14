<?php
$config = json_decode(file_get_contents(__DIR__ . '/.deployconfig.json'));
if (!isset($config->branch)) { $config->branch = 'master'; }
if (!isset($config->cc)) { $config->cc = null; }

$body = file_get_contents('php://input');

if (!verify_request($body)) {
	header('HTTP/1.1 403 Forbidden');
	die('Request did not originate from GitHub-Hookshot with the correct secret.');
}

$input = json_decode($body);
if (!$input) {
	header('HTTP/1.1 400 Bad Request');
	die('Could not parse request body as JSON.');
}

if ($input->ref !== 'refs/heads/' . $config->branch) {
	header('HTTP/1.1 200 OK');
	die('Push was not the deploy branch (' . $config->branch . '). Canceling deploy.');
}

foreach (array('git pull origin ' . $config->branch, 'git submodule sync', 'git submodule update') as $command) {
	exec($command, $output, $return_var);
	if ($return_var !== 0) {
		send_email($input, $output);
		header('HTTP/1.1 500 Internal Server Error');
		die('Deploy failed. The git pull command did not exit with 0.' . "\n" . print_r($output, true));
	}
}

send_email($input);
header('HTTP/1.1 200 OK');
die('Deployment of ' . $config->branch . ' branch was successful.');

/**
 * Verifies that the user agent and SHA1 signature match the "secret" token.
 * @param string $body The request body
 * @return bool True if the request is valid
 */
function verify_request ($body) {
	global $config;
	return strpos($_SERVER['HTTP_USER_AGENT'], 'GitHub-Hookshot') !== false &&
		hash_equals($_SERVER['HTTP_X_HUB_SIGNATURE'], 'sha1=' . hash_hmac('sha1', $body, $config->token));
}

/**
 * Checks if two hashes are equal in an unoptimized way so as to prevent time-checking attacks.
 * @param string $a The hash
 * @param string $b Another hash to compare to
 * @return bool True if they are equal
 */
function hash_equals ($a, $b) {
	$a_length = strlen($a);
	if ($a_length !== strlen($b)) { return false; }

	$result = 0;
	for ($i = 0; $i < $a_length; $i++) {
		$result |= ord($a[$i]) ^ ord($b[$i]);
	}

	return $result === 0;
}

/**
 * Sends an email broadcasting an event to the user who pushed the branch, the repository's owner, and email addresses
 * specified in the config "cc" property.
 * @param object $input The JSON-decoded request data
 * @param null|string $error If not null, sends an error alert
 */
function send_email ($input, $error = null) {
	global $config;

	if ($error === null) {
		$subject = 'Automatic WordPress deploy complete';
		$message = 'The repository ' . $input->repository->name . ' was successfully redeployed automatically from the ' .
			$config->branch . ' branch by ' . $input->pusher->name . '. Visit the website for ' . $input->repository->name . ' to ' .
			'confirm that everything is working!';
	} else {
		$subject = 'Automatic WordPress deploy FAILED';
		$message = 'The automatic WordPress deploy script failed for the ' . $input->repository->name . ' repository ' .
			'because the git pull command did not exit with a 0 status code. The output was:' . "\n" . print_r($error, true);
	}

	mail(
		implode(', ', array($input->pusher->email, $input->repository->owner->email)),
		$subject,
		$message,
		'From: ' . $input->repository->owner->email . ($config->cc ? "\n" . 'Cc: ' . $config->cc : '')
	);
}
