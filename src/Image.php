<?php

namespace Fuzz\ImageResizer;

use Imagick;
use InvalidArgumentException;

/**
 * Class Resizer
 *
 * @package Fuzz\ImageResizer
 */
class Image
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
	 * Class constructor.
	 *
	 * @param \Fuzz\ImageResizer\File $file
	 *        Instance of the image to resize
	 * @param bool                    $crop
	 *        Flag to determine if we're cropping to resize or not
	 */
	public function __construct(File $file, $crop = false)
	{
		$this->validateFile($file);

		$this->image_handler = new Imagick;
		$this->file = $file;
		$this->crop = $crop;

		$this->readFromBlob($this->file->getRaw());
	}

	/**
	 * Resize the image based on passed size information.
	 *
	 * @param  array $options
	 * @return $this
	 */
	public function alterImage($options)
	{
		// @todo rewrite into image decorator where each f(x) modifies the image
		$options = $this->validateOptions($options);

		$this->setImageFormat($options['format']);
		$this->setCompressionQuality($options['compression']);
		$this->resizeImage($options['height'], $options['width'], $options['crop']);

		return $this;
	}

	/**
	 * Convert to blob
	 *
	 * @return string
	 */
	public function toBlob()
	{
		return $this->image_handler->getImageBlob();
	}

	/**
	 * Clear the image resource
	 *
	 * @return $this
	 */
	public function clear()
	{
		// Free our image resizer of our image resource, to enhance performance and make it easier to resize the NEXT image
		// ( Garbage collect )
		$this->image_handler->clear();

		return $this;
	}

	/**
	 * Resize the image
	 *
	 * @param int $height
	 * @param int $width
	 * @param bool       $crop
	 * @return $this|void
	 */
	public function resizeImage($height, $width, $crop = false)
	{
		if ($this->crop || $crop) {
			$this->image_handler->cropThumbnailImage($width, $height);

			return $this;
		}

		$this->image_handler->thumbnailImage($width, $height, true); // Best fit

		return $this;
	}

	/**
	 * Read an image from a blob
	 *
	 * @param string $blob
	 * @return $this
	 */
	public function readFromBlob($blob)
	{
		$this->image_handler->readImageBlob($blob);

		return $this;
	}

	/**
	 * Set the image format
	 *
	 * @param string $format
	 * @return $this
	 */
	public function setImageFormat($format)
	{
		$this->image_handler->setImageFormat($format);

		return $this;
	}

	/**
	 * Set the compression quality
	 *
	 * @param string|int $quality
	 * @return $this
	 */
	public function setCompressionQuality($quality)
	{
		if (is_numeric($quality)) {
			$this->image_handler->setImageCompressionQuality($quality);

			return $this;
		}

		$quality          = strtoupper($quality);
		$compression_type = "COMPRESSION_$quality";
		$constant         = "Imagick::$compression_type";

		if (! defined($constant) || is_null(constant("Imagick::$compression_type"))) {
			throw new InvalidArgumentException('Invalid compression quality specified.');
		}

		$this->image_handler->setImageCompressionQuality(constant("Imagick::$compression_type"));

		return $this;
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

	/**
	 * Ensure that the request contains valid options
	 *
	 * @param array $options
	 * @return array
	 */
	private function validateOptions(array $options)
	{
		$required_options = [
			'width'  => 'is_numeric',
			'height' => 'is_numeric',
		];

		foreach ($required_options as $required => $validator) {
			if (! array_key_exists($required, $options) || is_null($options[$required]) || ! $validator($options[$required])) {
				http_response_code(400);
				throw new InvalidArgumentException("The $required parameter is missing or invalid.");
			}
		}

		$options['format']      = array_key_exists('format', $options) ? $options['format'] : $this->file->getExtension();
		$options['crop']        = array_key_exists('crop', $options) ? $options['crop'] : false;
		$options['compression'] = array_key_exists('compression', $options) ? $options['compression'] :
			100; // Default to no compression

		return $options;
	}

	/**
	 * Validate that the file passed to the constructor is an image.
	 *
	 * @param \Fuzz\ImageResizer\File $file
	 */
	private function validateFile(File $file)
	{
		if (! $file->isImage()) {
			http_response_code(400);
			throw new InvalidArgumentException('The file is not an image. Abort!');
		}
	}
}
