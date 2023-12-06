<?php
$method = strip_tags($_POST['method']);
$host = strip_tags($_POST['host']);
$uid = strip_tags($_POST['uid']);
$pwd = strip_tags($_POST['pwd']);

$output = $_lang['status_connecting'];
try {
    $dbh = new PDO($method . ':host=' . $host . ';', $uid, $pwd);
    $output .= '<span id="server_pass" style="color:#80c000;"> ' . $_lang['status_passed_server'] . '</span>';
} catch (PDOException $e) {
    $output .= '<span id="server_fail" style="color:#FF0000;"> ' . $_lang['status_failed'] . ' ' . $e->getMessage() . '</span>';
}
echo $output;
