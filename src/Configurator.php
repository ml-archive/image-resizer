<?php

namespace Fuzz\ImageResizer;

use Symfony\Component\HttpFoundation\Request;
use Carbon\Carbon;

class Configurator
{
	/**
	 * Default value for cache expiration (in hours)
	 *
	 * @var int
	 */
	protected $cache_expiration_hours = 2880;

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
	 * Class constructor
	 *
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 */
	public function __construct(Request $request)
	{
		$this->request = $request;

		$this->setImageSource();

		$this->config = [
			'height'              => (int) $this->request->get('height'),
			'width'               => (int) $this->request->get('width'),
			'crop'                => (bool) $this->request->get('crop'),
			'min_quality'         => (int) $this->request->get('min_quality', Image::FULL_COMPRESSION),
			'max_quality'         => (int) $this->request->get('max_quality', Image::NO_COMPRESSION),
			'max_file_size_bytes' => (int) $this->request->get('max_file_size_bytes'),
		];
	}

	/**
	 * Set up file object
	 *
	 * @param string|array $allowed_hosts
	 * @return \Fuzz\ImageResizer\File
	 */
	public function setupFile($allowed_hosts)
	{
		if (is_string($allowed_hosts)) {
			$this->checkDomain(explode(',', $allowed_hosts));
		} else {
			$this->checkDomain($allowed_hosts);
		}

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
		$expire_in = getenv('CACHE_EXPIRATION_HOURS') ?: $this->cache_expiration_hours;
		// Set time for cache expires. Default to 120 days (2880 hours)
		$expires         = $this->getRfcCompliantDate(Carbon::now(), $expire_in);
		$last_modified   = $this->getRfcCompliantDate(Carbon::now(), 0);
		$max_age_seconds = $expire_in * 60 * 60;

		header("Last-Modified: $last_modified");
		header("Cache-Control: max-age=$max_age_seconds");
		header("Expires: $expires");

		// File specific headers
		header('Content-Type: ' . $this->file->getMimeType());
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
		];

		foreach ($required_variables as $required) {
			if (! getenv($required)) {
				throw new \LogicException("The $required configuration is missing.");
			}
		}
	}

	/**
	 * Determine an appropriate source for the file
	 *
	 * @return void
	 */
	protected function setImageSource()
	{
		if ($this->request->get('source')) {
			$this->source = $this->request->get('source');
		} else {
			// Default
			$this->source = $this->request->getPathInfo();
		}
	}

	/**
	 * Make sure we're only resizing images from allowed hosts
	 *
	 * @param array $allowed_hosts
	 */
	protected function checkDomain(array $allowed_hosts)
	{
		$source_host = parse_url($this->source)['host'];

		if (! in_array($source_host, $allowed_hosts)) {
			throw new \InvalidArgumentException('The requested host is not allowed.');
		}
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
	private function getRfcCompliantDate(Carbon $carbon, $offset = 0)
	{
		return gmdate('D, d M Y H:i:s', $carbon->addHours($offset)->getTimestamp()) . ' GMT';
	}
}
