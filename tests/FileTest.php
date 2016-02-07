<?php

namespace Fuzz\ImageResizer\Tests;

use Fuzz\ImageResizer\File;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

class FileTest extends ImageResizerTestCase
{
	public function testItCanCreateFileFromBlob()
	{
		$blob = file_get_contents($this->getPlaceholditImage(300, 300));

		$file = File::createFromBlob($blob);

		$this->assertTrue($file instanceof File);
		$this->assertTrue($file->hasLocalFile());
	}

	public function testItSetsTempFilenameWhenCreatingFromBlob()
	{
		$blob = file_get_contents($this->getPlaceholditImage(300, 300));

		$file = File::createFromBlob($blob);

		$this->assertNotNull($file->getLocalFilename());
		$this->assertTrue($file->hasLocalFile());
	}

	public function testItReturnsFalseIfLocalFileNotFound()
	{
		$file = File::createFromFile('/this/does/not/exist/');

		$this->assertFalse($file);
	}

	public function testItCanCreateFromLocalFile()
	{
		$file = File::createFromFile(__FILE__);

		$this->assertTrue($file instanceof File);
	}

	public function testConstructorCanSetMimeType()
	{
		// default type is image/png
		$blob      = file_get_contents($this->getPlaceholditImage(300, 300));
		$mime_type = 'AFakeMimeType';

		$file = File::createFromBlob($blob, $mime_type);

		$this->assertEquals($mime_type, $file->getMimeType());
	}

	public function testItCanGuessMimeTypeFromExtension()
	{
		// default type is image/png
		$blob = file_get_contents($this->getPlaceholditImage(300, 300));

		$file = File::createFromBlob($blob);

		$this->assertEquals('image/png', $file->getMimeType());
	}

	public function testContructorCanSetExtension()
	{
		// default type is image/png
		$blob      = file_get_contents($this->getPlaceholditImage(300, 300));
		$mime_type = 'AFakeMimeType';
		$extension = 'gif';

		$file = File::createFromBlob($blob, $mime_type, $extension);

		$this->assertEquals($extension, $file->getExtension());
	}

	public function testItCanCleanUpOwnGarbage()
	{
		// default type is image/png
		$blob = file_get_contents($this->getPlaceholditImage(300, 300));

		$file = File::createFromBlob($blob);

		$temp_file = $file->getLocalFilename();

		$file->unlink();

		// Cleared temp filename
		$this->setExpectedException(FileNotFoundException::class);
		$this->assertNull($file->getLocalFilename());

		// Cleared actual file
		$this->assertFalse(file_get_contents($temp_file));
	}

	public function testItCanReturnRawImageData()
	{
		$blob = file_get_contents($this->getPlaceholditImage(300, 300));
		$file = File::createFromBlob($blob);

		$this->assertTrue(is_string($file->getRaw()));
	}
}
