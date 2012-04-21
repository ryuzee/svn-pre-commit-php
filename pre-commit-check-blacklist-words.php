#!/usr/bin/php

<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

define('SVNLOOK_PATH', '/usr/bin/svnlook');
define('BLACKLIST', dirname(__FILE__)."/./words.txt");
$EXTENSIONS = array('php', 'phps');

if($argc != 3) {
	echo 'ERROR: Invalid commandline argument.'.PHP_EOL.PHP_EOL;
	exit(2);
}

// Get list of files in this transaction.
$command = SVNLOOK_PATH.' changed '.$argv[1] ." -t ".$argv[2];
$handle  = popen($command, 'r');
if ($handle === false) {
	echo 'ERROR: Could not execute "'.$command.'"'.PHP_EOL.PHP_EOL;
	exit(2);
}
$contents = stream_get_contents($handle);
fclose($handle);

// Do not check deleted paths.
$contents = preg_replace('/^D.*/m', null, $contents);

// Load blacklist
$black_words = file(BLACKLIST, FILE_IGNORE_NEW_LINES);
$result = array();

foreach (preg_split("/\v|\n/", $contents, -1, PREG_SPLIT_NO_EMPTY) as $path) {
	$tmp = preg_split("/[\s,]+/", $path);
	$path = $tmp[count($tmp)-1];

	// No need to process folders as each changed file is checked.
	if (substr($path, -1) === '/') {
		continue;
	}

	// Get the contents of each file, as it would be after this transaction.
	$command = SVNLOOK_PATH.' cat '. $argv[1] . " -t ". $argv[2] .' '.escapeshellarg($path);
	$handle  = popen($command, 'r');
	if ($handle === false) {
		echo 'ERROR: Could not execute "'.$command.'"'.PHP_EOL.PHP_EOL;
		exit(2);
	}
	$contents = stream_get_contents($handle);
	fclose($handle);

	$ext = pathinfo($path, PATHINFO_EXTENSION);
	if(in_array($ext, $EXTENSIONS)) {
		// blacklist check!!!
		foreach($black_words as $word) {
			if(strpos($contents, $word) !== false) {
				$result[] = $path . "\t" . " Blacklist word '".$word . "' is included.";
			}
		}
	}
}//end foreach

// print result
if (count($result) > 0) {
	echo "---------------------------" . PHP_EOL;
 	foreach($result as $r) {
		echo $r .PHP_EOL;
	}
	echo "---------------------------" . PHP_EOL;
	exit(2);
}

?>
