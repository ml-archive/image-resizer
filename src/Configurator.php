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
	 * Determine an appropriate source for the file
	 *
	 * @return void
	 */
	protected function setImageSource()
	{
		// Default
		$path = $this->request->getPathInfo();

		if ($this->request->get('src')) {
			$this->source = $this->request->get('src');
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
			'width' => $this->request->get('width'),
			'crop' => (bool) $this->request->get('crop'),
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
		// @todo whitelisted domains
		return $this->file = File::createFromBlob(file_get_contents($this->source));
	}

	/**
	 * Apply appropriate headers to the request
	 *
	 * @return void
	 */
	public function setHeaders()
	{
		// Set proper headers afterwards.
		header('Content-Type: ' . $this->file->getMimeType());
		header('Content-Length: ' . $this->file->getSize()); // This wasn't returning the proper length... @todo needs to be fixed.

		// Set time for cache expires @todo this should be a config setting. PS: This code is ugly :(.

		$gmdate_expires = gmdate('D, d M Y H:i:s', strtotime ('now +120  days')) . ' GMT';
		$gmdate_modified = gmdate('D, d M Y H:i:s') . ' GMT';
		header('Last-Modified: ' . $gmdate_modified);
		header('Cache-Control: max-age=10368000, must-revalidate'); //120 days
		header('Expires: ' . $gmdate_expires);
	}
}
