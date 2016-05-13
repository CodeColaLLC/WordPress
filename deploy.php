<?php
$body = @file_get_contents('php://input');
$branch = isset($_GET['branch']) ? $_GET['branch'] : 'master';
$mailroom = 'bxdughbdidv+tech@in.mailroom.hipch.at';

if (
	strpos($_SERVER['HTTP_USER_AGENT'], 'GitHub-Hookshot') === false ||
	hash_equals($_SERVER['HTTP_X_HUB_SIGNATURE'], verify_request($body))
) {
	header('HTTP/1.1 403 Forbidden');
	die('Request did not originate from GitHub-Hookshot with the correct secret.');
}

$input = json_decode(file_get_contents('php://input'));
if (!$input || !property_exists($input, 'ref')) {
	header('HTTP/1.1 400 Bad Request');
	die('Could not parse request body as JSON or is missing `ref` property.');
}

if ($input->ref !== 'refs/heads/' . $branch) {
	header('HTTP/1.1 200 OK');
	die('Push was not the deploy branch (' . $branch . '). Canceling deploy.');
}

foreach (array('git pull origin ' . $branch) as $command) {
	exec($command, $output, $return_var);
	if ($return_var !== 0) {
		header('HTTP/1.1 500 Internal Server Error');
		mail(
			$input->pusher->email . ', ' . $input->repository->owner->email,
			'Automatic WordPress deploy failed',
			'The automatic WordPress deploy script failed for the ' . $input->repository->name . ' repository because the ' .
				'git pull command did not exit with a 0 status code. The output was:' . "\n" . print_r($output, true),
			'From: ' . $input->repository->owner->email . "\n" . 'Cc: ' . $mailroom
		);
		die('Deploy failed. The git pull command did not exit with 0.' . "\n" . print_r($output, true));
	}
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

header('HTTP/1.1 200 OK');
mail(
	$input->pusher->email . ', ' . $input->repository->owner->email,
	'Automatic WordPress deploy complete',
	'The repository ' . $input->repository->name . ' was successfully redeployed automatically from the ' . $branch .
		' branch by ' . $input->pusher->name . '. Visit the website for ' . $input->repository->name . ' to confirm that ' .
		'everything is working!',
	'From: ' . $input->repository->owner->email . "\n" . 'Cc: ' . $mailroom
);
die('Deployment of ' . $branch . ' branch was successful.');
