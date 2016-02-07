<?php

namespace Fuzz\ImageResizer\Tests;

use Carbon\Carbon;
use Fuzz\ImageResizer\Configurator;
use Fuzz\ImageResizer\File;

class ConfiguratorTest extends ImageResizerTestCase
{
	public function testItThrowsExceptionOnInvalidEnvironment()
	{
		$this->setExpectedException(\LogicException::class);
		Configurator::validateEnvironment();
		$this->assertEquals(500, http_response_code());
	}

	public function testItRequiresAllowedHosts()
	{
		putenv('ALLOWED_HOSTS'); // Clear env var
		putenv('CACHE_EXPIRATION_HOURS=1');
		$this->setExpectedException(\LogicException::class, 'The ALLOWED_HOSTS configuration is missing.');
		Configurator::validateEnvironment();
		$this->assertEquals(500, http_response_code());
	}

	public function testItRequiresCacheExpirationHours()
	{
		putenv('CACHE_EXPIRATION_HOURS'); // Clear env var
		putenv('ALLOWED_HOSTS=example.com,anotherexample.com');
		$this->setExpectedException(\LogicException::class, 'The CACHE_EXPIRATION_HOURS configuration is missing.');
		Configurator::validateEnvironment();
		$this->assertEquals(500, http_response_code());
	}

	public function testItConfiguresConfigurator()
	{
		$request_settings = [
			'height' => 300,
			'width' => 425,
			'crop' => false,
			'source' => 'http://acdn.images.com/subdir/image.png'
		];

		$this->setGlobals($request_settings);

		$configurator = new Configurator;

		foreach ($request_settings as $setting => $value) {
			if ($setting === 'source') {
				continue; // source is checked later
			}
			$this->assertEquals($request_settings[$setting], $configurator->config[$setting]);
		}

		$this->assertEquals($request_settings['source'], $configurator->source);
	}

	public function testItThrowsExceptionOnDomainNotAllowed()
	{
		$request_settings = [
			'height' => 300,
			'width' => 425,
			'crop' => false,
			'source' => $this->getPlaceholditImage(500, 400)
		];

		$this->setGlobals($request_settings);

		$configurator = new Configurator;

		$this->setExpectedException(\InvalidArgumentException::class, 'The requested host is not allowed.');
		$file = $configurator->setupFile(['not.placehold.it']);

		$this->assertEquals(500, http_response_code());
	}

	public function testSetupFileAcceptsString()
	{
		$request_settings = [
			'height' => 300,
			'width' => 425,
			'crop' => false,
			'source' => $this->getPlaceholditImage(500, 400)
		];

		$this->setGlobals($request_settings);

		$configurator = new Configurator;

		$file = $configurator->setupFile($this->placeholdit_domain);

		$this->assertTrue($file instanceof File);
	}

	public function testSetupFileAcceptsArray()
	{
		$request_settings = [
			'height' => 300,
			'width' => 425,
			'crop' => false,
			'source' => $this->getPlaceholditImage(500, 400)
		];

		$this->setGlobals($request_settings);

		$configurator = new Configurator;

		$file = $configurator->setupFile([$this->placeholdit_domain]);

		$this->assertTrue($file instanceof File);
	}

	public function testItSetsUpFile()
	{
		$request_settings = [
			'height' => 300,
			'width' => 425,
			'crop' => false,
			'source' => $this->getPlaceholditImage(500, 400)
		];

		$this->setGlobals($request_settings);

		$configurator = new Configurator;

		$file = $configurator->setupFile([$this->placeholdit_domain]);

		$this->assertTrue($file instanceof File);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testItSetsHeaders()
	{
		$cache_expires_hours = 6;
		$request_settings = [
			'height' => 300,
			'width' => 425,
			'crop' => false,
			'source' => $this->getPlaceholditImage(500, 400)
		];

		$this->setGlobals($request_settings);
		$this->setEnvironmentVariables($this->placeholdit_domain, $cache_expires_hours);

		$configurator = new Configurator;
		$file = $configurator->setupFile([$this->placeholdit_domain]);
		$configurator->setHeaders();

		$this->assertEquals($this->getRfcCompliantDate(Carbon::now(), 0), $this->getHeaderValue('Last-Modified'));

		$cache_age_seconds = $cache_expires_hours * 60 * 60;
		$this->assertEquals("max-age=$cache_age_seconds", $this->getHeaderValue('Cache-Control'));

		$this->assertEquals($file->getMimeType(), $this->getHeaderValue('Content-Type'));

		$this->assertEquals($this->getRfcCompliantDate(Carbon::now(), $cache_expires_hours), $this->getHeaderValue('Expires'));
	}
}
