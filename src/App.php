<?php

namespace Fuzz\ImageResizer;

use Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;

class App
{
	/**
	 * Run the application
	 *
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @return string
	 */
	public static function run(Request $request)
	{
		self::loadEnv();

		// Set things up for the resizer
		Configurator::validateEnvironment();

		$configurator = new Configurator($request);
		$configurator->setupFile(getenv('ALLOWED_HOSTS'));
		$configurator->setHeaders();

		$image = new Image($configurator->file);

		return $image->alterImage($configurator->config)->toBlob();
	}

	/**
	 * Setup dependencies and environment
	 *
	 * @return void
	 */
	protected static function loadEnv()
	{
		if (! getenv('APP_ENV')) {
			// Load Dotenv if in a dev environment (no environment vars loaded)
			(new Dotenv(__DIR__ . '/../'))->load();
		}
	}
}
