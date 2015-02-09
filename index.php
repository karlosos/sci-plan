<?php
// load required files
require 'Slim/Slim.php';
require 'RedBean/rb.php';

// register Slim auto-loader
\Slim\Slim::registerAutoloader();

// set up database connection
R::setup('mysql:host=localhost;dbname=rest','root','');
R::freeze(true);

// initialize app
$app = new \Slim\Slim();

// handle GET requests for /articles
$app->get('/articles', function () use ($app) {  
  // query database for all articles
  $articles = R::find('articles'); 
  
  // send response header for JSON content type
  $app->response()->header('Content-Type', 'application/json');
  
  // return JSON-encoded response body with query results
  echo json_encode(R::exportAll($articles));
});

class ResourceNotFoundException extends Exception {}

// handle GET requests for /articles/:id
$app->get('/articles/:id', function ($id) use ($app) {    
  try {
    // query database for single article
    $article = R::findOne('articles', 'id=?', array($id));
    
    if ($article) {
      // if found, return JSON response
      $app->response()->header('Content-Type', 'application/json');
      echo json_encode(R::exportAll($article));
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