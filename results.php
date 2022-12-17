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

function formatEntry($key, $entry) {
	switch ($key) {
		case 'started':
		case 'ended':
			return date('D, d M Y H:i:s', (int) (intval($entry) / 1000));
		default:
			return $entry;
	}
}

function applyFilter($rows, $filter) {
	if ($filter === 'none') {
		return $rows;
	}

	$filteredRows = [];
	switch ($filter) {
		case 'answers':
			$keys = ['sid', 'uuid', 'started', 'ended', 'name', 'answer', 'comment'];
			foreach ($rows as $row) {
				$filteredRow = [];
				foreach ($keys as $key) {
					$filteredRow[$key] = $row[$key];
				}
				$filteredRows[] = $filteredRow;
			}
			break;

		default:
			$filteredRows = $rows;
			break;
	}
	return $filteredRows;
}

function groupRows($rows) {
	$groupedRows = [];
	foreach ($rows as $row) {
		$key = $row["sid"];
		if (!isset($groupedRows[$key])) {
			$groupedRows[$key] = [$row];
		} else {
			$groupedRows[$key][] = $row;
		}
	}
	return $groupedRows;
}

$queryAll = (
	"SELECT * " .
	"FROM mt_sessions AS s1 " .
	"INNER JOIN mt_results AS r ON s1.sid = r.sid"
);

$queryLatestOnly = (
	"SELECT * " .
	"FROM mt_sessions AS s1 " .
	"INNER JOIN mt_results AS r ON s1.sid = r.sid " .
	"WHERE s1.sid IN (" .
		"SELECT MAX(s2.sid) " .
		"FROM mt_sessions AS s2 " .
		"GROUP BY uuid " .
		"ORDER BY sid DESC" .
	")"
);

$mode = isset($_GET['mode']) ? $_GET['mode'] : 'all';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'none';

$query = $mode === 'all' ? $queryAll : $queryLatestOnly;
$sth = $dbh->prepare($query);
$sth->execute();
$rawRows = (array) $sth->fetchAll(PDO::FETCH_CLASS);
$rows = [];
foreach ($rawRows as $row) {
	$rows[] = (array) $row;
}
$rows = applyFilter($rows, $filter);
if (count($rows) === 0) {
	$rowKeys = [];
} else {
	$rowKeys = array_keys((array) $rows[0]);
}
$groupedRows = groupRows($rows);

?>

<!DOCTYPE html>
<html>
	<head>
		<title>MT Evaluation</title>
		<script src="https://code.jquery.com/jquery-3.6.1.min.js" integrity="sha256-o88AwQnZB+VDvE9tvIXrMQaPlFFSUTR+nldQm1LuPXQ=" crossorigin="anonymous"></script>
		<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js" integrity="sha256-lSjKY0/srUM9BE3dPm+c4fBo1dky2v27Gdjm2uoZaL0=" crossorigin="anonymous"></script>
		<script src="https://cdn.tailwindcss.com"></script>
	</head>
	<body>
		<div class="container m-8">
		<?php if (count($rows) === 0): ?>
			No entries in the database yet :/
		<?php else: ?>
		<div class="m-8 ml-0">
			<a href="?mode=all&filter=<?= htmlentities($filter) ?>" class="rounded bg-slate-300 p-2 m-2 ml-0 pl-4 pr-4 hover:bg-slate-500 hover:text-white">All entries</a>
			<a href="?mode=last&filter=<?= htmlentities($filter) ?>" class="rounded bg-slate-300 p-2 m-2 pl-4 pr-4 hover:bg-slate-500 hover:text-white">Last sessions Only</a>
			<a href="?mode=<?= htmlentities($mode) ?>&filter=none" class="rounded bg-slate-300 p-2 m-2 ml-20 pl-4 pr-4 hover:bg-slate-500 hover:text-white">All fields</a>
			<a href="?mode=<?= htmlentities($mode) ?>&filter=answers" class="rounded bg-slate-300 p-2 m-2 pl-4 pr-4 hover:bg-slate-500 hover:text-white">Answers only</a>
		</div>
		<?php foreach ($groupedRows as $groupKey => $rows): ?>
		<h2 class="text-4xl font-bold dark:text-white mt-8 mb-4">SID: <?= $groupKey ?></h2>
		<table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
			<thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
				<tr>
					<th scope="col" class="py-3 px-6">#</th>
					<?php foreach ($rowKeys as $key): ?>
					<th scope="col" class="py-3 px-6"><?= ucfirst($key) ?></th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody id="step3-summary">
			<?php 
				$index = 0;
				foreach ($rows as $row):
			?>
				<tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
					<th scope="row" class="py-4 px-6 font-medium text-gray-900 whitespace-nowrap dark:text-white"><?= $index ?></th>
					<?php foreach ($row as $key => $entry): ?>
					<td class="py-4 px-6"><?= formatEntry($key, $entry); ?></td>
					<?php endforeach; ?>
				</tr>
			<?php 
				$index += 1;
				endforeach;
			?>
			</tbody>
		</table>
		<?php endforeach; ?>
		<?php /*
		<table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
			<thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
				<tr>
					<th scope="col" class="py-3 px-6">#</th>
					<?php foreach ($rowKeys as $key): ?>
					<th scope="col" class="py-3 px-6"><?= ucfirst($key) ?></th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody id="step3-summary">
			<?php 
				$index = 0;
				foreach ($rows as $row):
			?>
				<tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
					<th scope="row" class="py-4 px-6 font-medium text-gray-900 whitespace-nowrap dark:text-white"><?= $index ?></th>
					<?php foreach ($row as $key => $entry): ?>
					<td class="py-4 px-6"><?= formatEntry($key, $entry); ?></td>
					<?php endforeach; ?>
				</tr>
			<?php 
				$index += 1;
				endforeach;
			?>
			</tbody>
		</table>
		*/ ?>
		<?php endif; ?>
		</div>
	</body>
</html>
