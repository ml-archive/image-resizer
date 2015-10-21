<?php

/**
 * Setup dependencies and environment
 */
require __DIR__ . '/vendor/autoload.php';

if (! getenv('APP_ENV')) {
	// Load Dotenv if in a dev environment (no environment vars loaded)
	(new \Dotenv\Dotenv(__DIR__))->load();
}

use Fuzz\ImageResizer\Configurator;
use Fuzz\ImageResizer\Resizer;

/**
 * Set things up for the resizer
 */
Configurator::validateEnvironment();

$configurator = new Configurator;
$configurator->setupFile(getenv('ALLOWED_HOSTS'));
$configurator->setHeaders();

/**
 * Resize the image
 */
$image = new Resizer($configurator->file);

/**
 * Return the magically resized image
 */
echo $image->resizeImage($configurator->config);
