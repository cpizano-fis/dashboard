<?php

$y = filter_input(INPUT_GET, "y");
$m = filter_input(INPUT_GET, "m");
$output = [];
$status_code = 0;
// The array will populate with each line of output
$command = 'python3 /home/ubuntu/dashboard/cron/db-cron.py ECUAINTEGRAL 0 ' . $y . ' ' . $m;
echo $command . "<br>";
exec($command, $output, $status_code);
$salida = print_r($output, TRUE);
if ($status_code === 0) {
    echo "Command succeeded!";
} else {
	echo $salida;
    echo "<br>Command failed with code: " . $status_code;
}
