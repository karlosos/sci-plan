<?php
// load required files
require 'Slim/Slim.php';
require 'RedBean/rb.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

\Slim\Slim::registerAutoloader();

R::setup('mysql:host=localhost;dbname=plan', 'root', '');
R::freeze(true);

$app = new \Slim\Slim();


$app->get('/plan', function () use ($app) {  
  $articles = R::find('plan'); 
  $app->response()->header('Content-Type', 'application/json');
  echo json_encode(R::exportAll($articles));
});

class ResourceNotFoundException extends Exception {}

$app->get('/plan/:id', function ($id) use ($app) {    
  try {
      $plan = R::find('plan', 'klasa = ?', array($id));  
    if ($plan) {
      $app->response()->header('Content-Type', 'application/json');
      echo json_encode(R::exportAll($plan));
    } else {
      throw new ResourceNotFoundException();
    }
  } catch (ResourceNotFoundException $e) {
    $app->response()->status(404);
  } catch (Exception $e) {
    $app->response()->status(400);
    $app->response()->header('X-Status-Reason', $e->getMessage());
  }
});

$app->get('/plan/:id/:day', function ($id, $day) use ($app) {    
  try {
      $plan = R::find('plan', "klasa = $id AND dzien = $day", array($id));
    if ($plan) {
      $app->response()->header('Content-Type', 'application/json');
      echo json_encode(R::exportAll($plan));
    } else {
      throw new ResourceNotFoundException();
    }
  } catch (ResourceNotFoundException $e) {
    $app->response()->status(404);
  } catch (Exception $e) {
    $app->response()->status(400);
    $app->response()->header('X-Status-Reason', $e->getMessage());
  }
});

$app->run();