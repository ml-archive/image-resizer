<?php

/**
 * Setup dependencies and environment
 */
require __DIR__ . '/vendor/autoload.php';

use Fuzz\ImageResizer\App;
use Fuzz\ImageResizer\ExceptionHandler;
use Symfony\Component\HttpFoundation\Request;

try {
	echo App::run(Request::createFromGlobals());
} catch (Exception $e) {
	echo json_encode(ExceptionHandler::handleException($e));
}
