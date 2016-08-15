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
	 * No compression
	 *
	 * @const int
	 */
	const NO_COMPRESSION = 100;

	/**
	 * Full compression
	 *
	 * @const int
	 */
	const FULL_COMPRESSION = 1;

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
		$this->file          = $file;
		$this->crop          = $crop;

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
		$options = $this->validateOptions($options);

		$this->setImageFormat($options['format']);
		$this->setQualityLevel($options['min_quality'], $options['max_quality'], $options['max_file_size_bytes']);
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
	 * @param int  $height
	 * @param int  $width
	 * @param bool $crop
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
	 * Set the handler instance
	 *
	 * @param \Imagick $handler
	 * @return $this
	 */
	public function setImageHandler(Imagick $handler)
	{
		$this->image_handler = $handler;

		return $this;
	}

	/**
	 * Set the compression quality
	 *
	 * @param int  $min
	 * @param int  $max
	 * @param bool $maximum_file_size
	 * @return $this
	 */
	public function setQualityLevel($min = self::FULL_COMPRESSION, $max = self::NO_COMPRESSION, $maximum_file_size = false)
	{
		if (! $maximum_file_size) {
			$this->image_handler->setImageCompressionQuality($max);

			return $this;
		}

		$quality   = $max;
		$starting_size = strlen($this->toBlob());
		$size          = $starting_size;

		do {
			$this->image_handler->setImageCompressionQuality($quality);
			$size = strlen($this->toBlob());
			$quality--;
		} while ($quality <= $max && $quality >= $min && $size > $maximum_file_size);

		// If we've gotten this far and have increased the file size, just use the minimum compression (max quality)
		// because compression is not useful in this situation.
		if ($size >= $starting_size) {
			$this->image_handler->setImageCompressionQuality($max);
		}

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

		$options['format']              = $this->getOptionsKey($options, 'format', $this->file->getExtension());
		$options['crop']                = $this->getOptionsKey($options, 'crop', false);
		$options['min_quality']         = $this->getOptionsKey($options, 'min_quality', self::FULL_COMPRESSION);
		$options['max_quality']         = $this->getOptionsKey($options, 'max_quality', self::NO_COMPRESSION);
		$options['max_file_size_bytes'] = $this->getOptionsKey($options, 'max_file_size_bytes', false);

		return $options;
	}

	/**
	 * Get a key from the array or default
	 *
	 * @param array  $options
	 * @param string $key
	 * @param mixed  $default
	 * @return mixed
	 */
	private function getOptionsKey(array $options, $key, $default)
	{
		return array_key_exists($key, $options) ? $options[$key] : $default;
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
