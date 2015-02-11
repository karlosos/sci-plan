<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'Backend.php';

$backend = new Backend();

print_r($backend->getData());

