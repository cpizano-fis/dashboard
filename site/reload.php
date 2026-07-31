<?php

$y = filter_input(INPUT_GET, "y");
$m = filter_input(INPUT_GET, "m");
$output = [];
$status_code = 0;
// The array will populate with each line of output
exec('python3 /home/ubuntu/dashboard/cron/db-cron.py ECUAINTEGRAL 0 ' . $y . ' ' . $m, $output, $status_code);
if ($status_code === 0) {
    echo "Command succeeded!";
} else {
    echo "Command failed with code: " . $status_code;
}
