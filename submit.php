<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

const DB_HOST = 'rdbms.strato.de';
const DB_NAME = 'dbs9305631';
const DB_USER = 'dbu5407203';
const DB_PASS = 'mukkeg-bowdan-3tIbco';

$dsn = "mysql:dbname=" . DB_NAME . ";host=" . DB_HOST;
$dbh = new PDO($dsn, DB_USER, DB_PASS);

function vd($input) {
	echo '<pre>';
	var_dump($input);
	echo '</pre>';
}

function verifyPayload($payload) {
	// Verify the general payload
	if (
		!isset($payload['uuid'])    ||
		!isset($payload['started']) ||
		!isset($payload['ended']) ||
		!isset($payload['results'])
	) {
		return 1;
	}

	// Verify result entries
	foreach ($payload["results"] as $result) {
		if (
			!isset($result['task'])   ||
			!isset($result['answer'])
		) {
			return 2;
		}

		if (
			!isset($result['task']['task']) ||
			!isset($result['task']['hypothesis']) ||
			!isset($result['task']['type']) ||
			!isset($result['task']['name']) ||
			!isset($result['task']['video_link'])
		) {
			return 3;
		}
	}
	return 0;
}

if (!isset($_POST)) {
	http_response_code(400);
	return;
}

$sessionPayload = $_POST;
if (verifyPayload($sessionPayload) !== 0) {
	http_response_code(400);
	return;
}

$uuid = $_POST['uuid'];
$started = $_POST['started'];
$ended = $_POST['ended'];
$results = $_POST['results'];
$ip = $_SERVER['REMOTE_ADDR'];
$user_agent = $_SERVER['HTTP_USER_AGENT'];

$sth = $dbh->prepare("insert into mt_sessions (uuid, ip, user_agent, started, ended) values (?, ?, ?, ?, ?)");
$ret = $sth->execute([ $uuid, $ip, $user_agent, $started, $ended ]);
$sid = $dbh->lastInsertId();

foreach ($results as $result) {
	$answer = $result['answer'];
	$task = $result['task'];

	$taskStr = $task['task'];
	$hypothesis = $task['hypothesis'];
	$type = $task['type'];
	$name = $task['name'];
	$video_link = $task['video_link'];
	$comment = isset($result['comment']) && !empty($result['comment']) ? $result['comment'] : '';

	$sth = $dbh->prepare("insert into mt_results (sid, answer, task, hypothesis, type, name, video_link, comment) values (?, ?, ?, ?, ?, ?, ?, ?)");
	$ret = $sth->execute([ $sid, $answer, $taskStr, $hypothesis, $type, $name, $video_link, $comment]);
}

http_response_code(200);
