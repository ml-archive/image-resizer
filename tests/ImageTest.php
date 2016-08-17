<?php

namespace Fuzz\ImageResizer\Tests;

use Fuzz\ImageResizer\File;
use Fuzz\ImageResizer\Image;
use Imagick;
use Mockery;

class ImageTest extends ImageResizerTestCase
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
		$image = new Image($file);
	}

	public function testItLoadsFile()
	{
		$image = new Image($this->getImageFile(300, 500));

		$this->assertTrue($image->file instanceof File);
	}

	public function testItCanSetCrop()
	{
		$image = new Image($this->getImageFile(300, 500), false);

		$this->assertFalse($image->crop);

		$image = new Image($this->getImageFile(300, 500), true);

		$this->assertTrue($image->crop);
	}

	public function testItThrowsExceptionOnNonNumericWidth()
	{
		$image = new Image($this->getImageFile(300, 500), false);

		$options = [
			'width'  => 'NotNumeric',
			'height' => 400,
		];

		$this->setExpectedException(\InvalidArgumentException::class, 'The width parameter is missing or invalid.');
		$image->alterImage($options);
	}

	public function testItThrowsExceptionOnMissingWidth()
	{
		$image = new Image($this->getImageFile(300, 500), false);

		$options = [
			'height' => 400,
		];

		$this->setExpectedException(\InvalidArgumentException::class, 'The width parameter is missing or invalid.');
		$image->alterImage($options);
	}

	public function testItThrowsExceptionOnNonNumericHeight()
	{
		$image = new Image($this->getImageFile(300, 500), false);

		$options = [
			'height' => 'NotNumeric',
			'width'  => 400,
		];

		$this->setExpectedException(\InvalidArgumentException::class, 'The height parameter is missing or invalid.');
		$image->alterImage($options);
	}

	public function testItThrowsExceptionOnMissingHeight()
	{
		$image = new Image($this->getImageFile(300, 500), false);

		$options = [
			'width' => 400,
		];

		$this->setExpectedException(\InvalidArgumentException::class, 'The height parameter is missing or invalid.');
		$image->alterImage($options);
	}

	public function testImageReceivesImageBlob()
	{
		$file  = $this->getImageFile(300, 500);
		$image = new Image($file, false);

		$options = [
			'height' => '200',
			'width'  => '400',
		];

		$image->alterImage($options);

		$this->assertNotNull($image->toBlob());
	}

	public function testItSetsExplicitFormat()
	{
		$file  = $this->getImageFile(300, 500);
		$image = new Image($file, false);

		$options = [
			'height' => '200',
			'width'  => '400',
			'format' => 'gif',
			// Default is png
		];

		$image->alterImage($options);

		$this->assertEquals($options['format'], $image->getImageHandler()->getImageFormat());
	}

	public function testItSetsImplicitFormat()
	{
		$file  = $this->getImageFile(300, 500);
		$image = new Image($file, false);

		$options = [
			'height' => '200',
			'width'  => '400',
		];

		$image->alterImage($options);

		$this->assertEquals($file->getExtension(), $image->getImageHandler()->getImageFormat());
	}

	public function testItCanCropOnAlterImage()
	{
		$file  = $this->getImageFile(300, 500);
		$image = new Image($file, true);

		$options = [
			'height' => '200',
			'width'  => '400',
		];

		$image->alterImage($options);

		// Crop option should return an image with our exact size options
		$this->assertEquals($options['height'], $image->getImageHandler()->getImageHeight());
		$this->assertEquals($options['width'], $image->getImageHandler()->getImageWidth());
	}

	public function testItCanAlterImageWithoutCrop()
	{
		$file  = $this->getImageFile(300, 500);
		$image = new Image($file, false);

		$options = [
			'height' => '200',
			'width'  => '400',
			'crop'   => false,
		];

		$image->alterImage($options, false);

		$this->assertEquals($options['height'], $image->getImageHandler()->getImageHeight());
		// Because we always use best fit, final image width will be 333 pixels
		$this->assertEquals(333, $image->getImageHandler()->getImageWidth());
	}

	public function testItReturnsBlob()
	{
		$file  = $this->getImageFile(300, 500);
		$image = new Image($file);

		// returns a binary string
		$this->assertTrue(is_string($image->toBlob()));
	}

	public function testItSetsCompressionQuality()
	{
		$file  = File::createFromBlob(file_get_contents(__DIR__ . '/images/stock-photo.jpg'));  // ~814kb
		$image = new Image($file, false);

		$options = [
			'height'              => '200',
			'width'               => '400',
			'min_quality'         => 2,
			'max_quality'         => 100,
			'max_file_size_bytes' => 800000, // 800kb
		];

		$image->alterImage($options);

		$this->assertEquals(96, $image->getImageHandler()->getImageCompressionQuality());
	}

	public function testItSetsDefaultsToMaxQuality()
	{
		$file  = $this->getImageFile(300, 500);
		$image = new Image($file, false);

		$options = [
			'height' => '200',
			'width'  => '400',
		];

		$image->alterImage($options);

		$this->assertEquals(Image::NO_COMPRESSION, $image->getImageHandler()->getImageCompressionQuality());
	}

	public function testItGarbageCollectsImagickInstance()
	{
		$file  = $this->getImageFile(300, 500);
		$image = new Image($file, false);

		$options = [
			'height' => '200',
			'width'  => '400',
			'crop'   => false,
		];

		$image->alterImage($options)->clear();

		// Instance is now clear and we shouldn't be able to get an image blob
		$this->setExpectedException(\ImagickException::class, 'Can not process empty Imagick object');
		$image->getImageHandler()->getImageBlob();
	}

	public function testItResizesImageAndReturnsStatic()
	{
		$file  = $this->getImageFile(300, 500);
		$image = new Image($file, false);

		$return = $image->resizeImage(200, 400);

		$this->assertEquals(200, $image->getImageHandler()->getImageHeight());
		// Because we always use best fit, final image width will be 333 pixels
		$this->assertEquals(333, $image->getImageHandler()->getImageWidth());
		$this->assertEquals($image, $return);
	}

	public function testItResizesImageWithCropAndReturnsStatic()
	{
		$file  = $this->getImageFile(300, 500);
		$image = new Image($file, false);

		$return = $image->resizeImage(200, 400, true);

		$this->assertEquals(200, $image->getImageHandler()->getImageHeight());
		// Because we always use best fit, final image width will be 333 pixels
		$this->assertEquals(400, $image->getImageHandler()->getImageWidth());
		$this->assertEquals($image, $return);
	}

	public function testItCanReadFromBlob()
	{
		$file  = $this->getImageFile(300, 500);
		$image = new Image($file, false);
		$file2 = $this->getImageFile(200, 300);

		$return = $image->readFromBlob($file2->getRaw());

		$this->assertTrue(is_string($image->toBlob()));

		$this->assertEquals($image, $return);
	}

	public function testItCanSetImageFormatAndReturnStatic()
	{
		$file  = $this->getImageFile(300, 500);
		$image = new Image($file, false);

		$return = $image->setImageFormat('png');

		$this->assertEquals('png', $image->getImageHandler()->getImageFormat());
		$this->assertEquals($image, $return);
	}

	public function testItSetsImageHandler()
	{
		$file  = $this->getImageFile(300, 500);
		$image = new Image($file, false);

		$new_handler = Mockery::mock(Imagick::class);

		$image->setImageHandler($new_handler);

		$this->assertEquals($new_handler, $new_handler);

		$new_handler->shouldReceive('testMethod')->andReturn('a test!');

		$this->assertEquals('a test!', $image->getImageHandler()->testMethod());
	}

	public function testItUsesMaxQualityIfMaximumFileSizeNotSet()
	{
		$file  = $this->getImageFile(300, 500);
		$image = new Image($file, false);

		$return = $image->setQualityLevel(2, 100);

		$this->assertEquals(100, $image->getImageHandler()->getImageCompressionQuality());
		$this->assertEquals($image, $return);
	}

	public function testItUsesMaximumQualityIfGoodEnough()
	{
		$file        = $this->getImageFile(300, 500);
		$image       = new Image($file, false);
		$new_handler = Mockery::mock(Imagick::class);
		$image->setImageHandler($new_handler);

		$new_handler->shouldReceive('getimageblob')->andReturn(openssl_random_pseudo_bytes(400));
		$new_handler->shouldReceive('getimageblob')->andReturn(openssl_random_pseudo_bytes(400));
		$new_handler->shouldReceive('setimagecompressionquality')->with(100);

		$return = $image->setQualityLevel(5, 100, 1500);

		$this->assertEquals($image, $return);
	}

	public function testItUsesMaxCompressionAndNoLower()
	{
		$file        = $this->getImageFile(300, 500);
		$image       = new Image($file, false);
		$new_handler = Mockery::mock(Imagick::class);
		$image->setImageHandler($new_handler);

		$new_handler->shouldReceive('getimageblob')->once()->andReturn(openssl_random_pseudo_bytes(1600));

		$new_handler->shouldReceive('setimagecompressionquality')->once()->with(5);
		$new_handler->shouldReceive('getimageblob')->once()->andReturn(openssl_random_pseudo_bytes(1590));

		$new_handler->shouldReceive('setimagecompressionquality')->once()->with(6);
		$new_handler->shouldReceive('getimageblob')->once()->andReturn(openssl_random_pseudo_bytes(1580));

		$new_handler->shouldReceive('setimagecompressionquality')->once()->with(7);
		$new_handler->shouldReceive('getimageblob')->once()->andReturn(openssl_random_pseudo_bytes(1550));

		$new_handler->shouldReceive('setimagecompressionquality')->once()->with(8);
		$new_handler->shouldReceive('getimageblob')->once()->andReturn(openssl_random_pseudo_bytes(1540));

		$new_handler->shouldNotReceive('setimagecompressionquality')->with(9);
		$new_handler->shouldNotReceive('getimageblob');

		$return = $image->setQualityLevel(5, 8, 1500);

		$this->assertEquals($image, $return);
	}

	public function testItUsesMaxQualityIfFileSizeHasNetIncreaseInRequestedCompressionRange()
	{
		$file        = $this->getImageFile(300, 500);
		$image       = new Image($file, false);
		$new_handler = Mockery::mock(Imagick::class);
		$image->setImageHandler($new_handler);

		$new_handler->shouldReceive('getimageblob')->once()->andReturn(openssl_random_pseudo_bytes(1600));

		$new_handler->shouldReceive('setimagecompressionquality')->once()->with(5);
		$new_handler->shouldReceive('getimageblob')->once()->andReturn(openssl_random_pseudo_bytes(1800));

		$new_handler->shouldReceive('setimagecompressionquality')->once()->with(6);
		$new_handler->shouldReceive('getimageblob')->once()->andReturn(openssl_random_pseudo_bytes(1700));

		$new_handler->shouldReceive('setimagecompressionquality')->once()->with(7);
		$new_handler->shouldReceive('getimageblob')->once()->andReturn(openssl_random_pseudo_bytes(1650));

		$new_handler->shouldReceive('setimagecompressionquality')->once()->with(8);
		$new_handler->shouldReceive('getimageblob')->once()->andReturn(openssl_random_pseudo_bytes(1625));

		// Should finally set the min compression (max quality) because we don't gain anything by compressing further
		$new_handler->shouldReceive('setimagecompressionquality')->once()->with(8);

		$return = $image->setQualityLevel(5, 8, 1500);

		$this->assertEquals($image, $return);
	}

	public function testItUsesMaxQualityIfFileSizeHasNoNetDecreaseInRequestedCompressionRange()
	{
		$file        = $this->getImageFile(300, 500);
		$image       = new Image($file, false);
		$new_handler = Mockery::mock(Imagick::class);
		$image->setImageHandler($new_handler);

		$new_handler->shouldReceive('getimageblob')->once()->andReturn(openssl_random_pseudo_bytes(1600));

		$new_handler->shouldReceive('setimagecompressionquality')->once()->with(5);
		$new_handler->shouldReceive('getimageblob')->once()->andReturn(openssl_random_pseudo_bytes(1800));

		$new_handler->shouldReceive('setimagecompressionquality')->once()->with(6);
		$new_handler->shouldReceive('getimageblob')->once()->andReturn(openssl_random_pseudo_bytes(1700));

		$new_handler->shouldReceive('setimagecompressionquality')->once()->with(7);
		$new_handler->shouldReceive('getimageblob')->once()->andReturn(openssl_random_pseudo_bytes(1650));

		$new_handler->shouldReceive('setimagecompressionquality')->once()->with(8);
		$new_handler->shouldReceive('getimageblob')->once()->andReturn(openssl_random_pseudo_bytes(1625));

		// Should finally set the min compression (max quality) because we don't gain anything by compressing further
		$new_handler->shouldReceive('setimagecompressionquality')->once()->with(8);

		$return = $image->setQualityLevel(5, 8, 1500);

		$this->assertEquals($image, $return);
	}
}
