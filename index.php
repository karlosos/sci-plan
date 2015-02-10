<?php
// load required files
require 'Slim/Slim.php';
require 'RedBean/rb.php';

// register Slim auto-loader
\Slim\Slim::registerAutoloader();

// set up database connection
R::setup('mysql:host=localhost;dbname=plan','root','');
R::freeze(true);

// initialize app
$app = new \Slim\Slim();

// handle GET requests for /articles
$app->get('/plan', function () use ($app) {  
  // query database for all articles
  $articles = R::find('plan'); 
  
  // send response header for JSON content type
  $app->response()->header('Content-Type', 'application/json');
  
  // return JSON-encoded response body with query results
  echo json_encode(R::exportAll($articles));
});

class ResourceNotFoundException extends Exception {}

// handle GET requests for /articles/:id
$app->get('/plan/:id', function ($id) use ($app) {    
  try {
    // query database for single article
    //$article = R::findOne('articles', 'id=?', array($id));
      $plan = R::find('plan', 'klasa = ?', array($id));
    
    if ($plan) {
      // if found, return JSON response
      $app->response()->header('Content-Type', 'application/json');
      echo json_encode(R::exportAll($plan));
    } else {
      // else throw exception
      throw new ResourceNotFoundException();
    }
  } catch (ResourceNotFoundException $e) {
    // return 404 server error
    $app->response()->status(404);
  } catch (Exception $e) {
    $app->response()->status(400);
    $app->response()->header('X-Status-Reason', $e->getMessage());
  }
});

// handle GET requests for /articles/:id
$app->get('/plan/:id/:day', function ($id, $day) use ($app) {    
  try {
    // query database for single article
    //$article = R::findOne('articles', 'id=?', array($id));
      $plan = R::find('plan', "klasa = $id AND dzien = $day", array($id));
    
    if ($plan) {
      // if found, return JSON response
      $app->response()->header('Content-Type', 'application/json');
      echo json_encode(R::exportAll($plan));
    } else {
      // else throw exception
      throw new ResourceNotFoundException();
    }
  } catch (ResourceNotFoundException $e) {
    // return 404 server error
    $app->response()->status(404);
  } catch (Exception $e) {
    $app->response()->status(400);
    $app->response()->header('X-Status-Reason', $e->getMessage());
  }
});


// run
$app->run();