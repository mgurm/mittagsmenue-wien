<?php

if (!file_exists($_SERVER['DOCUMENT_ROOT'] . '../includes/config.php'))
	die('config file not existing');

$commands_to_check = array('pdftohtml', 'antiword', 'convert');
foreach ($commands_to_check as $command) {
	if (!command_exist($command))
		die("command '$command' not existing");
}

?>