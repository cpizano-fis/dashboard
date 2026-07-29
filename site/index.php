<?php
$dir = "/var/www/html/xlsx/";
$server_ip = trim(file_get_contents('https://checkip.amazonaws.com'));
echo "Internal Server IP: " . $server_ip . "<br/>";
$xlsx_files = array_diff(scandir($dir, SCANDIR_SORT_DESCENDING), array('..', '.'));
foreach  ($xlsx_files as $value) {
    echo "<a href='http://" . $server_ip . "/xlsx/" . $value . "'>" . $value ."</a><br/>";
}
