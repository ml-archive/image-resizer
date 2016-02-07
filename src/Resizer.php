<?php

namespace Fuzz\ImageResizer;

use Imagick;
use InvalidArgumentException;

/**
 * Class Resizer
 *
 * @package Fuzz\ImageResizer
 */
class Resizer
{
	/**
	 * The actual resizer
	 *
	 * @var Imagick
	 */
	private $image_resizer;

	/**
	 * Should we crop the file?
	 *
	 * @var bool
	 */
	public $crop;

	/**
	 * File object container
	 *
	 * @var \Fuzz\ImageResizer\File
	 */
	public $file;

	/**
	 * Ensure that the request contains valid options
	 *
	 * @param array $size_options
	 */
	private function validateOptions(array $size_options)
	{
		$required_options = [
			'width' => 'is_numeric',
			'height' => 'is_numeric',
		];


		foreach ($required_options as $required => $validator) {
			if (! isset($size_options[$required]) || is_null($size_options[$required]) || ! $validator($size_options[$required])) {
				http_response_code(400);
				throw new InvalidArgumentException("The $required parameter is missing or invalid.");
			}
		}
	}

	/**
	 * Class constructor.
	 *
	 * @param \Fuzz\ImageResizer\File $file
	 *        Instance of the image to resize
	 * @param bool                    $crop
	 *        Flag to determine if we're cropping to resize or not
	 */
	public function __construct(File $file, $crop = false)
	{
		$this->image_resizer = new Imagick;

		$this->file = $file;
		$this->validateFile();
		$this->crop = $crop;
	}

	/**
	 * Validate that the file passed to the constructor is an image.
	 *
	 * @return void
	 *
	 * @throws  InvalidArgumentException If file is not a valid image
	 */
	private function validateFile()
	{
		if (! $this->file->isImage()) {
			http_response_code(400);
			throw new InvalidArgumentException('The file is not an image. Abort!');
		}
	}

	/**
	 * Resize the image based on passed size information.
	 *
	 * @param  array $size_info
	 *           Configuration array of the following form:
	 *           [
	 *           'width'     => <w>,  // OPTIONAL required width
	 *           'height'    => <h>, // OPTIONAL required height
	 *           'ratio'     => <r>, // OPTIONAL required aspect ratio <w>:<h>
	 *           'tolerance' => <t>, // OPTIONAL acceptable tolerance from <r>
	 *           ]
	 *
	 * @param bool   $garbage_collect
	 * @return string
	 */
	public function resizeImage($size_info, $garbage_collect = true)
	{
		$this->validateOptions($size_info);
		// Was an image format passed?
		if (isset($size_info['format'])) {
			// If so, set our image format to what was passed
			$image_format = $size_info['format'];
		} else {
			// Otherwise, set our image format to the same as the current
			$image_format = $this->file->getExtension();
		}

		// Pass our binary image data to our resizer
		$this->image_resizer->readImageBlob($this->file->getRaw());

		// Set our image format
		$this->image_resizer->setImageFormat($image_format);

		// Resize our image based on our size info and cropping preferences
		if ($this->crop || (isset($size_info['crop']) && ($size_info['crop'] === true))) {
			$this->image_resizer->cropThumbnailImage($size_info['width'], $size_info['height']);
		} else {
			$this->image_resizer->thumbnailImage($size_info['width'], $size_info['height'], true /* Bestfit */);
		}

		// Get our raw image data from the resized image
		$resized_image_data = $this->image_resizer->getImageBlob();

		if ($garbage_collect) {
			// Free our image resizer of our image resource, to enhance performance and make it easier to resize the NEXT image
			// ( Garbage collect )
			$this->image_resizer->clear();
		}

		return $resized_image_data;
	}

	/**
	 * Retrieve the Imagick instance
	 *
	 * @return \Imagick
	 */
	public function getImageResizer()
	{
		return $this->image_resizer;
	}
}
