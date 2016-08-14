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
	private $image_handler;

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
			'width'  => 'is_numeric',
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
		$this->image_handler = new Imagick;

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
	 * @param  array $options
	 *               Configuration array of the following form:
	 *               [
	 *               'width'       => <w>,  // OPTIONAL required width
	 *               'height'      => <h>, // OPTIONAL required height
	 *               'ratio'       => <r>, // OPTIONAL required aspect ratio <w>:<h>
	 *               'tolerance'   => <t>, // OPTIONAL acceptable tolerance from <r>,
	 *               'compression' => <c>, // OPTIONAL compression type, integer or string
	 *               ]
	 *
	 * @param bool   $garbage_collect
	 * @return string
	 */
	public function alterImage($options, $garbage_collect = true)
	{
		$this->validateOptions($options);
		// Was an image format passed?
		if (isset($options['format'])) {
			// If so, set our image format to what was passed
			$image_format = $options['format'];
		} else {
			// Otherwise, set our image format to the same as the current
			$image_format = $this->file->getExtension();
		}

		$crop        = isset($options['crop']) ? $options['crop'] : false;
		$compression = isset($options['compression']) ? $options['compression'] : Imagick::COMPRESSION_UNDEFINED;

		$this->readFromBlob($this->file->getRaw());
		$this->setImageFormat($image_format);
		$this->setCompressionQuality($compression);
		$this->resizeImage($options['height'], $options['width'], $crop);

		// Get our raw image data from the resized image
		$resized_image_data = $this->image_handler->getImageBlob();

		if ($garbage_collect) {
			// Free our image resizer of our image resource, to enhance performance and make it easier to resize the NEXT image
			// ( Garbage collect )
			$this->image_handler->clear();
		}

		return $resized_image_data;
	}

	/**
	 * Resize the image
	 *
	 * @param int|string $height
	 * @param int|string $width
	 * @param bool       $crop
	 */
	public function resizeImage($height, $width, $crop = false)
	{
		if ($this->crop || $crop) {
			$this->image_handler->cropThumbnailImage($width, $height);

			return;
		}

		$this->image_handler->thumbnailImage($width, $height, true); // Best fit
	}

	/**
	 * Read an image from a blob
	 *
	 * @param string $blob
	 * @return void
	 */
	public function readFromBlob($blob)
	{
		$this->image_handler->readImageBlob($blob);
	}

	/**
	 * Set the image format
	 *
	 * @param string $format
	 * @return void
	 */
	public function setImageFormat($format)
	{
		$this->image_handler->setImageFormat($format);
	}

	/**
	 * Set the compression quality
	 *
	 * @param string|int $quality
	 * @return void
	 */
	public function setCompressionQuality($quality)
	{
		if (is_numeric($quality)) {
			$this->image_handler->setImageCompressionQuality($quality);

			return;
		}

		$quality          = strtoupper($quality);
		$compression_type = "COMPRESSION_$quality";
		$constant         = "Imagick::$compression_type";

		if (! defined($constant) || is_null(constant("Imagick::$compression_type"))) {
			throw new InvalidArgumentException('Invalid compression quality specified.');
		}

		$this->image_handler->setImageCompressionQuality(constant("Imagick::$compression_type"));
	}

	/**
	 * Retrieve the Imagick instance
	 *
	 * @return \Imagick
	 */
	public function getImageHandler()
	{
		return $this->image_handler;
	}
}
