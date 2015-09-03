<?php namespace Fuzz\ImageResizer;

/**
 * @file
 *
 * Magical image resizer.
 */
use Illuminate\Support\Facades\Config;
use Imagick;
use InvalidArgumentException;

class Resizer
{
	/**
	 * The actual resizer
	 * @var Imagick
	 */
	private $image_resizer;

	private $crop;
	private $config;
	private $geometry;

	public  $file;
	public  $sizes;

	/**
	 * Class constructor.
	 *
	 * @param Fuzz\File\File $file
	 *        Instance of the image to resize
	 * @param Array $config
	 *        Configuration array of the following form:
	 *        array(
	 *           'sizes' => array(
	 *               '<slug>' => array(
	 *                   'width'  => <w>,
	 *                   'height' => <h>,
	 *               ),
	 *               ...
	 *           ), // REQUIRED sizing array
	 *           'width'     => <w>,  // OPTIONAL required width
	 *           'height'    => <h>, // OPTIONAL required height
	 *           'ratio'     => <r>, // OPTIONAL required aspect ratio <w>:<h>
	 *           'tolerance' => <t>, // OPTIONAL acceptable tolerance from <r>
	 *         )
	 * @param bool $crop
	 *        Flag to determine if we're cropping to resize or not
	 *
	 * @return void
	 */
	public function __construct(File $file, $crop = false)
	{
		$this->image_resizer = new Imagick;

		$this->file = $file;
		$this->validateFile();

//		$this->config = $config;
//		$this->validateConfig();

//		$this->sizes = $this->config['sizes'];

//		$this->validateSizes();
		$this->crop = $crop;

//		$this->rewind();
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
			throw new InvalidArgumentException('The file is not an image. Abort!');
		}
	}

	/**
	 * Validate that the config passed has appropriate resize specifications.
	 *
	 * @return void
	 *
	 * @throws InvalidArgumentException If the config does not match the spec
	 */
//	private function validateConfig()
//	{
//		if (! isset($this->config['sizes'])) {
//			throw new InvalidArgumentException('The configuration does not specify sizes');
//		}
//
//		foreach ($this->config['sizes'] as $size) {
//			if (! isset($size['width']) || ! isset($size['height'])
//				|| ! is_int($size['width']) || ! is_int($size['height'])
//			) {
//				throw new InvalidArgumentException(
//					'The configuration does not specify valid integer pixel width and height for all sizes'
//				);
//			}
//		}
//	}

	/**
	 * Resize the image based on passed size information.
	 *
	 * @param  array $size_info
	 *         Contains height, width, and optional format params
	 *
	 * @return string
	 */
	public function resizeImage($size_info) {
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
		if ($this->crop || (isset($size_info['crop']) && ($size_info['crop'] === true)) || (isset($this->config['crop']) && ($this->config['crop'] === true))) {
			$this->image_resizer->cropThumbnailImage($size_info['width'], $size_info['height']);
		} else {
			$this->image_resizer->thumbnailImage($size_info['width'], $size_info['height'], true /* Bestfit */);
		}

		// Get our raw image data from the resized image
		$resized_image_data = $this->image_resizer->getImageBlob();

		// Free our image resizer of our image resource, to enhance performance and make it easier to resize the NEXT image
		// ( Garbage collect )
		$this->image_resizer->clear();

		return $resized_image_data;
	}
}