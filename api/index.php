<?php

/**
 *  Responsible for all CRUD (Create, Read, Update, Delete) operations
 *  handled by REST services
 *  For more information on how routs and REST services are achieved using
 *  the phpSlim framework read documentation http://docs.slimframework.com/
 */



/**
 * Step 1: Require the Slim Framework
 *
 * If you are not using Composer, you need to require the
 * Slim Framework and register its PSR-0 autoloader.
 *
 * If you are using Composer, you can skip this step.
 */

date_default_timezone_set('America/Port_of_Spain');

require 'Slim/Slim.php';
include 'fileHandler.php';
include 'dbHandler.php';

\Slim\Slim::registerAutoloader();

/**
 * Step 2: Instantiate a Slim application
 *
 * This example instantiates a Slim application using
 * its default settings. However, you will usually configure
 * your Slim application now by passing an associative array
 * of setting names and values into the application constructor.
 */
$app = new \Slim\Slim();

/**
 * Step 3: Define the Slim application routes
 *
 * Here we define several Slim application routes that respond
 * to appropriate HTTP request methods. In this example, the second
 * argument for `Slim::get`, `Slim::post`, `Slim::put`, `Slim::patch`, and `Slim::delete`
 * is an anonymous function.
 */

// GET route
$app->get('/get/file/list','getFileList');


/**
 * Step 4: Run the Slim application
 *
 * This method should be called last. This executes the Slim application
 * and returns the HTTP response to the HTTP client.
 */
$app->run();



/**
*                 POST Services
*    Each Post Service calles an instance of the SLIM class
*    Decodes the request made to the service
*    Creates an instance of the Database Handler class
*    Passes the decoded request to the neccessary function to process the request
*/

function getFileList(){
       
    $file = FileHandler::getInstance();
    $file->readFiles();
    echo json_encode($file->findFile());    
}