<?php

namespace Fuzz\ImageResizer\Tests;

use Fuzz\ImageResizer\App;
use Fuzz\ImageResizer\File;
use Imagick;
use Mockery;
use Symfony\Component\HttpFoundation\Request;

class AppTest extends ImageResizerTestCase
{
	public function getImageFile($height, $width)
	{
		return File::createFromBlob(file_get_contents($this->getPlaceholditImage($height, $width)));
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testItAltersFile()
	{
		putenv('ALLOWED_HOSTS=placehold.it,placeholdit.imgix.net');
		putenv('APP_ENV=testing');

		$request = Mockery::mock(Request::class);

		$request->shouldReceive('get')->with('height')->andReturn(150);
		$request->shouldReceive('get')->with('width')->andReturn(300);
		$request->shouldReceive('get')->with('crop')->andReturn(false);
		$request->shouldReceive('get')->with('source')->andReturn($this->getPlaceholditImage(300, 500));

		$image_blob = App::run($request);

		$this->assertTrue(is_string($image_blob));

		$imagick = new Imagick;
		$imagick->readImageBlob($image_blob);

		$this->assertEquals(150, $imagick->getImageHeight());
		$this->assertEquals(250, $imagick->getImageWidth()); // we expect 250 because of Best Fit
	}
}
