<?php

namespace Fuzz\ImageResizer\Tests;

use Carbon\Carbon;
use PHPUnit_Framework_TestCase;

class ImageResizerTestCase extends PHPUnit_Framework_TestCase
{
	public $placeholdit_domain = 'placeholdit.imgix.net';

	public $headers_cache = [];

	/**
	 * Set up request globals
	 *
	 * @param array $settings
	 */
	public function setGlobals(array $settings)
	{
		foreach ($settings as $key => $value) {
			$_GET[$key] = $value;
		}
	}

	/**
	 * Set up environment configurations
	 *
	 * @param string $allowed_hosts
	 * @param int    $cache_expiration_hours
	 */
	public function setEnvironmentVariables($allowed_hosts = 'example.com', $cache_expiration_hours = 1)
	{
		putenv("ALLOWED_HOSTS=$allowed_hosts");
		putenv("CACHE_EXPIRATION_HOURS=$cache_expiration_hours");
	}

	/**
	 * Return a source URL for a placehold.it image
	 *
	 * @param int    $height
	 * @param int    $width
	 * @param string $format
	 * @return string
	 */
	public function getPlaceholditImage($height, $width, $format = 'png')
	{
		$height = (string) $height;
		$width  = (string) $width;

		return "https://placeholdit.imgix.net/~text?txtsize=33&txt=350%C3%97150&w=$width&h=$height&fm=$format";
	}

	/**
	 * Read a value for a header that's ready to be sent
	 *
	 * @param string $key
	 * @return null
	 */
	public function getHeaderValue($key)
	{
		if (! empty($this->headers_cache)) {
			return isset($this->headers_cache[$key]) ? $this->headers_cache[$key] : null;
		}

		// Requires XDebug installed
		foreach (xdebug_get_headers() as $header) {
			$split                          = explode(': ', $header); // ['key', 'value']
			$this->headers_cache[$split[0]] = $split[1];
		}

		return $this->headers_cache[$key];
	}

	/**
	 * Return an RFC {large number here} compliant date from a Carbon date object
	 *
	 * Example: Wed, 21 Oct 2015 13:48:28 GMT
	 *
	 * @param \Carbon\Carbon $carbon
	 * @param int            $offset
	 * @return string
	 */
	public function getRfcCompliantDate(Carbon $carbon, $offset = 0)
	{
		return gmdate('D, d M Y H:i:s', $carbon->addHours($offset)->getTimestamp()) . ' GMT';
	}
}
