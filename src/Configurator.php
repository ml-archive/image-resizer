<?php

namespace Fuzz\ImageResizer;

use Symfony\Component\HttpFoundation\Request;
use Carbon\Carbon;

class Configurator
{
	/**
	 * Request container
	 *
	 * @var \Symfony\Component\HttpFoundation\Request
	 */
	protected $request;

	/**
	 * Image source
	 *
	 * @var string
	 */
	public $source = '';

	/**
	 * Configuration container
	 *
	 * @var array
	 */
	public $config = [];

	/**
	 * File container
	 *
	 * @var \Fuzz\ImageResizer\File
	 */
	public $file;

	/**
	 * Return an RFC {large number here} compliant date from a Carbon date object
	 *
	 * Example: Wed, 21 Oct 2015 13:48:28 GMT
	 *
	 * @param \Carbon\Carbon $carbon
	 * @param int            $offset
	 * @return string
	 */
	private function getRfcCompliantDate(Carbon $carbon, $offset = 0)
	{
		return gmdate('D, d M Y H:i:s', $carbon->addHours($offset)->getTimestamp()) . ' GMT';
	}

	/**
	 * Determine an appropriate source for the file
	 *
	 * @return void
	 */
	protected function setImageSource()
	{
		// Default
		$path = $this->request->getPathInfo();

		if ($this->request->get('source')) {
			$this->source = $this->request->get('source');
		} else {
			$this->source = $path;
		}
	}

	/**
	 * Make sure we're only resizing images from allowed hosts
	 *
	 * @param array $allowed_hosts
	 */
	protected function checkDomain(array $allowed_hosts)
	{
		$source_host = parse_url($this->source)['host']; // google.com
		$def = in_array($source_host, $allowed_hosts);

		if (! in_array($source_host, $allowed_hosts)) {
			http_response_code(401);
			throw new \InvalidArgumentException('The requested host is not allowed.');
		}
	}

	/**
	 * Class constructor
	 */
	public function __construct()
	{
		$this->request = Request::createFromGlobals();

		$this->setImageSource();

		$this->config = [
			'height' => $this->request->get('height'),
			'width'  => $this->request->get('width'),
			'crop'   => (bool) $this->request->get('crop'),
		];
	}

	/**
	 * Set up file object
	 *
	 * @return \Fuzz\ImageResizer\File
	 */
	public function setupFile($allowed_hosts)
	{
		$this->checkDomain(explode(',', $allowed_hosts));

		// Setup file
		return $this->file = File::createFromBlob(file_get_contents($this->source));
	}

	/**
	 * Apply appropriate headers to the request
	 *
	 * @return void
	 */
	public function setHeaders()
	{
		$expire_in = getenv('CACHE_EXPIRATION_HOURS') ?: 2880; //120 days by default
		// Set time for cache expires. Default to 120 days (2880 hours)
		$expires         = $this->getRfcCompliantDate(Carbon::now(), $expire_in);
		$last_modified   = $this->getRfcCompliantDate(Carbon::now(), 0);
		$max_age_seconds = $expire_in * 60 * 60;

		header("Last-Modified: $last_modified");
		header("Cache-Control: max-age=$max_age_seconds, must-revalidate");
		header("Expires: $expires");

		// File specific headers
		header('Content-Type: ' . $this->file->getMimeType());
		header('Content-Length: ' . $this->file->getSize());
	}

	/**
	 * Require some environment configurations to be present
	 *
	 * @return void
	 * @throws \LogicException
	 */
	public static function validateEnvironment()
	{
		$required_variables = [
			'ALLOWED_HOSTS',
			'CACHE_EXPIRATION_HOURS',
		];

		foreach ($required_variables as $required) {
			if (! getenv($required)) {
				http_response_code(500);
				throw new \LogicException("The $required configuration is missing.");
			}
		}
	}
}
