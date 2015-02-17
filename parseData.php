<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'Backend.php';

$backend = new SheduleManager();

print_r($backend->updateAllData());

