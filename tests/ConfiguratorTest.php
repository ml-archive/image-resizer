<?php

namespace Fuzz\ImageResizer\Tests;

use Carbon\Carbon;
use Fuzz\ImageResizer\Configurator;
use Fuzz\ImageResizer\File;
use Fuzz\ImageResizer\Image;
use Mockery;
use Symfony\Component\HttpFoundation\Request;

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

	public function testItConfiguresConfigurator()
	{
		$request_settings = [
			'height' => 300,
			'width' => 425,
			'crop' => false,
			'source' => 'http://acdn.images.com/subdir/image.png'
		];

		$request = Mockery::mock(Request::class);

		$request->shouldReceive('get')->with('height')->andReturn($request_settings['height']);
		$request->shouldReceive('get')->with('width')->andReturn($request_settings['width']);
		$request->shouldReceive('get')->with('crop')->andReturn($request_settings['crop']);
		$request->shouldReceive('get')->with('source')->andReturn($request_settings['source']);
		$request->shouldReceive('get')->with('min_quality', Image::FULL_COMPRESSION)->andReturn(10);
		$request->shouldReceive('get')->with('max_quality', image::NO_COMPRESSION)->andReturn(100);
		$request->shouldReceive('get')->with('max_file_size_bytes')->andReturn(2200);
		$request->shouldReceive('get')->with('min_quality', Image::FULL_COMPRESSION)->andReturn(10);
		$request->shouldReceive('get')->with('max_quality', image::NO_COMPRESSION)->andReturn(100);
		$request->shouldReceive('get')->with('max_file_size_bytes')->andReturn(2200);

		$configurator = new Configurator($request);

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

		$request = Mockery::mock(Request::class);

		$request->shouldReceive('get')->with('height')->andReturn($request_settings['height']);
		$request->shouldReceive('get')->with('width')->andReturn($request_settings['width']);
		$request->shouldReceive('get')->with('crop')->andReturn($request_settings['crop']);
		$request->shouldReceive('get')->with('source')->andReturn($request_settings['source']);
		$request->shouldReceive('get')->with('min_quality', Image::FULL_COMPRESSION)->andReturn(10);
		$request->shouldReceive('get')->with('max_quality', image::NO_COMPRESSION)->andReturn(100);
		$request->shouldReceive('get')->with('max_file_size_bytes')->andReturn(2200);

		$configurator = new Configurator($request);

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

		$request = Mockery::mock(Request::class);

		$request->shouldReceive('get')->with('height')->andReturn($request_settings['height']);
		$request->shouldReceive('get')->with('width')->andReturn($request_settings['width']);
		$request->shouldReceive('get')->with('crop')->andReturn($request_settings['crop']);
		$request->shouldReceive('get')->with('source')->andReturn($request_settings['source']);
		$request->shouldReceive('get')->with('min_quality', Image::FULL_COMPRESSION)->andReturn(10);
		$request->shouldReceive('get')->with('max_quality', image::NO_COMPRESSION)->andReturn(100);
		$request->shouldReceive('get')->with('max_file_size_bytes')->andReturn(2200);

		$configurator = new Configurator($request);

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

		$request = Mockery::mock(Request::class);

		$request->shouldReceive('get')->with('height')->andReturn($request_settings['height']);
		$request->shouldReceive('get')->with('width')->andReturn($request_settings['width']);
		$request->shouldReceive('get')->with('crop')->andReturn($request_settings['crop']);
		$request->shouldReceive('get')->with('source')->andReturn($request_settings['source']);
		$request->shouldReceive('get')->with('min_quality', Image::FULL_COMPRESSION)->andReturn(10);
		$request->shouldReceive('get')->with('max_quality', image::NO_COMPRESSION)->andReturn(100);
		$request->shouldReceive('get')->with('max_file_size_bytes')->andReturn(2200);

		$configurator = new Configurator($request);

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

		$request = Mockery::mock(Request::class);

		$request->shouldReceive('get')->with('height')->andReturn($request_settings['height']);
		$request->shouldReceive('get')->with('width')->andReturn($request_settings['width']);
		$request->shouldReceive('get')->with('crop')->andReturn($request_settings['crop']);
		$request->shouldReceive('get')->with('source')->andReturn($request_settings['source']);
		$request->shouldReceive('get')->with('min_quality', Image::FULL_COMPRESSION)->andReturn(10);
		$request->shouldReceive('get')->with('max_quality', image::NO_COMPRESSION)->andReturn(100);
		$request->shouldReceive('get')->with('max_file_size_bytes')->andReturn(2200);

		$configurator = new Configurator($request);

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

		$this->setEnvironmentVariables($this->placeholdit_domain, $cache_expires_hours);

		$request = Mockery::mock(Request::class);

		$request->shouldReceive('get')->with('height')->andReturn($request_settings['height']);
		$request->shouldReceive('get')->with('width')->andReturn($request_settings['width']);
		$request->shouldReceive('get')->with('crop')->andReturn($request_settings['crop']);
		$request->shouldReceive('get')->with('source')->andReturn($request_settings['source']);
		$request->shouldReceive('get')->with('min_quality', Image::FULL_COMPRESSION)->andReturn(10);
		$request->shouldReceive('get')->with('max_quality', image::NO_COMPRESSION)->andReturn(100);
		$request->shouldReceive('get')->with('max_file_size_bytes')->andReturn(2200);

		$configurator = new Configurator($request);

		$file = $configurator->setupFile([$this->placeholdit_domain]);
		$configurator->setHeaders();

		$this->assertEquals($this->getRfcCompliantDate(Carbon::now(), 0), $this->getHeaderValue('Last-Modified'));

		$cache_age_seconds = $cache_expires_hours * 60 * 60;
		$this->assertEquals("max-age=$cache_age_seconds", $this->getHeaderValue('Cache-Control'));

		$this->assertEquals($file->getMimeType(), $this->getHeaderValue('Content-Type'));

		$this->assertEquals($this->getRfcCompliantDate(Carbon::now(), $cache_expires_hours), $this->getHeaderValue('Expires'));
	}
}
