<?php

require 'Slim/Slim.php';
\Slim\Slim::registerAutoloader();
$application = new \Slim\Slim();

$application->get(
        '/hello/:firstname/:lastname',
        function ($first, $last) {
            echo "Hello $first $last";
        });
        
$application->run();