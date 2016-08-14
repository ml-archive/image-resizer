<?php

namespace Fuzz\ImageResizer\Tests;

use Fuzz\ImageResizer\File;
use Fuzz\ImageResizer\Resizer;

class ResizerTest extends ImageResizerTestCase
{
	public function getImageFile($height, $width)
	{
		return File::createFromBlob(file_get_contents($this->getPlaceholditImage($height, $width)));
	}

	public function testItThrowsExceptionOnInvalidImage()
	{
		// Load in a .php file
		$file = File::createFromBlob(file_get_contents(__FILE__));

		$this->setExpectedException(\InvalidArgumentException::class, 'The file is not an image. Abort!');
		$resizer = new Resizer($file);
	}

	public function testItLoadsFile()
	{
		$resizer = new Resizer($this->getImageFile(300, 500));

		$this->assertTrue($resizer->file instanceof File);
	}

	public function testItCanSetCrop()
	{
		$resizer = new Resizer($this->getImageFile(300, 500), false);

		$this->assertFalse($resizer->crop);

		$resizer = new Resizer($this->getImageFile(300, 500), true);

		$this->assertTrue($resizer->crop);
	}

	public function testItThrowsExceptionOnNonNumericWidth()
	{
		$resizer = new Resizer($this->getImageFile(300, 500), false);

		$options = [
			'width'  => 'NotNumeric',
			'height' => 400,
		];

		$this->setExpectedException(\InvalidArgumentException::class, 'The width parameter is missing or invalid.');
		$resizer->alterImage($options);
	}

	public function testItThrowsExceptionOnMissingWidth()
	{
		$resizer = new Resizer($this->getImageFile(300, 500), false);

		$options = [
			'height' => 400,
		];

		$this->setExpectedException(\InvalidArgumentException::class, 'The width parameter is missing or invalid.');
		$resizer->alterImage($options);
	}

	public function testItThrowsExceptionOnNonNumericHeight()
	{
		$resizer = new Resizer($this->getImageFile(300, 500), false);

		$options = [
			'height' => 'NotNumeric',
			'width'  => 400,
		];

		$this->setExpectedException(\InvalidArgumentException::class, 'The height parameter is missing or invalid.');
		$resizer->alterImage($options);
	}

	public function testItThrowsExceptionOnMissingHeight()
	{
		$resizer = new Resizer($this->getImageFile(300, 500), false);

		$options = [
			'width' => 400,
		];

		$this->setExpectedException(\InvalidArgumentException::class, 'The height parameter is missing or invalid.');
		$resizer->alterImage($options);
	}

	public function testResizerReceivesImageBlob()
	{
		$file    = $this->getImageFile(300, 500);
		$resizer = new Resizer($file, false);

		$options = [
			'height' => '200',
			'width'  => '400',
		];

		$resizer->alterImage($options, false);

		// strcmp won't work because by this point the image blob in the imagick instance has been resized and is now different
		//$this->assertTrue(strcmp($file->getRaw(), $resizer->getImageHandler()->getImageBlob()) === 0);

		$this->assertNotNull($resizer->getImageHandler()->getImageBlob());
	}

	public function testItSetsExplicitFormat()
	{
		$file    = $this->getImageFile(300, 500);
		$resizer = new Resizer($file, false);

		$options = [
			'height' => '200',
			'width'  => '400',
			'format' => 'gif',
			// Default is png
		];

		$resizer->alterImage($options, false);

		$this->assertEquals($options['format'], $resizer->getImageHandler()->getImageFormat());
	}

	public function testItSetsImplicitFormat()
	{
		$file    = $this->getImageFile(300, 500);
		$resizer = new Resizer($file, false);

		$options = [
			'height' => '200',
			'width'  => '400',
		];

		$resizer->alterImage($options, false);

		$this->assertEquals($file->getExtension(), $resizer->getImageHandler()->getImageFormat());
	}

	public function testItCanCropOnAlterImage()
	{
		$file    = $this->getImageFile(300, 500);
		$resizer = new Resizer($file, true);

		$options = [
			'height' => '200',
			'width'  => '400',
		];

		$resizer->alterImage($options, false);

		// Crop option should return an image with our exact size options
		$this->assertEquals($options['height'], $resizer->getImageHandler()->getImageHeight());
		$this->assertEquals($options['width'], $resizer->getImageHandler()->getImageWidth());
	}

	public function testItCanAlterImageWithoutCrop()
	{
		$file    = $this->getImageFile(300, 500);
		$resizer = new Resizer($file, false);

		$options = [
			'height' => '200',
			'width'  => '400',
			'crop'   => false,
		];

		$resizer->alterImage($options, false);

		$this->assertEquals($options['height'], $resizer->getImageHandler()->getImageHeight());
		// Because we always use best fit, final image width will be 333 pixels
		$this->assertEquals(333, $resizer->getImageHandler()->getImageWidth());
	}

	public function testItReturnsRawImageData()
	{
		$file    = $this->getImageFile(300, 500);
		$resizer = new Resizer($file, false);

		$options = [
			'height' => '200',
			'width'  => '400',
			'crop'   => false,
		];

		$image = $resizer->alterImage($options, false);

		$this->assertEquals($image, $resizer->getImageHandler()->getImageBlob());
	}

	public function testItThrowsExceptionOnBadCompressionType()
	{
		$file    = $this->getImageFile(300, 500);
		$resizer = new Resizer($file, false);

		$options = [
			'height'      => '200',
			'width'       => '400',
			'compression' => 'h264',
			// not a valid compression type
		];

		$this->setExpectedException(\InvalidArgumentException::class, 'Invalid compression quality specified.');
		$image = $resizer->alterImage($options, false);
	}

	public function testItSetsCompressionQuality()
	{
		$file    = $this->getImageFile(300, 500);
		$resizer = new Resizer($file, false);

		$options = [
			'height'      => '200',
			'width'       => '400',
			'compression' => 'jpeg', // not a valid compression type
		];

		$image = $resizer->alterImage($options, false);

		$this->assertEquals(\Imagick::COMPRESSION_JPEG, $resizer->getImageHandler()->getImageCompressionQuality());
	}

	public function testItGarbageCollectsImagickInstance()
	{
		$file    = $this->getImageFile(300, 500);
		$resizer = new Resizer($file, false);

		$options = [
			'height' => '200',
			'width'  => '400',
			'crop'   => false,
		];

		$image = $resizer->alterImage($options);

		// Instance is now clear and we shouldn't be able to get an image blob
		$this->setExpectedException(\ImagickException::class, 'Can not process empty Imagick object');
		$resizer->getImageHandler()->getImageBlob();
	}
}
