<?php namespace Fuzz\ImageResizer;

use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use finfo;

/**
 * @file
 * Fuzz file object.
 */
class File
{
	/**
	 * The container for the SplFileInfo.
	 *
	 * @var \Symfony\Component\HttpFoundation\File\File
	 */
	private $info;

	/**
	 * The container for the SplFileObject
	 *
	 * @var \SplFileObject
	 */
	private $object;

	/**
	 * The raw binary content of the file.
	 *
	 * @var string
	 */
	private $raw;

	/**
	 * The hashed filename of the file.
	 *
	 * @var string
	 */
	private $filename;

	/**
	 * The derived extension of the file.
	 *
	 * @var string
	 */
	private $extension;

	/**
	 * The dervied (or explicitly set) MIME type of the file.
	 *
	 * @var string
	 */
	protected $mime_type;

	/**
	 * The name of the local file used for operations.
	 *
	 * @var string
	 */
	protected $temp_filename;

	/**
	 * Class constructor.
	 *
	 * @param \Symfony\Component\HttpFoundation\File\File $info
	 * @param string                                      $mime_type
	 * @param string                                      $extension
	 */
	public function __construct(SymfonyFile $info, $mime_type = null, $extension = null)
	{
		// Symfony will help us guess things about this file
		$this->info = $info;

		// Sometimes we want to specify the MIME type ourselves
		$this->mime_type = $mime_type;

		// Sometimes we want to specify the extension ourselves
		$this->extension = $extension;

		// Get the SplFileObject from the SplFileInfo
		$this->object = $this->info->openFile();

		// Kill any temporary files created in the lifetime of this object
		register_shutdown_function([
			$this,
			'unlink'
		]);
	}

	/**
	 * Create a file from a blob.
	 *
	 * @param string        $blob       Raw binary data to create a file from
	 * @param null|string   $mime_type  An optional user-declared MIME type
	 *
	 * @return static Instance of the File object created from the blob
	 */
	public static function createFromBlob($blob, $mime_type = null)
	{
		$temp_filename = tempnam(sys_get_temp_dir(), 'fuzz-file-');

		file_put_contents($temp_filename, $blob);

		$symfony_file = new SymfonyFile($temp_filename);

		$file = new static((new SymfonyFile($symfony_file, false)), $mime_type);

		// Store the temp filename so we can unlink it later
		$file->setTempFilename($temp_filename);

		return $file;
	}

	/**
	 * Set the temp filename of the file.
	 *
	 * @param string $temp_filename
	 * @return void
	 */
	public function setTempFilename($temp_filename)
	{
		$this->temp_filename = $temp_filename;
	}

	/**
	 * Determine if a temp file exists.
	 *
	 * @return boolean
	 */
	public function hasLocalFile()
	{
		return ! is_null($this->temp_filename) && file_exists($this->temp_filename);
	}

	/**
	 * Get local filename.
	 *
	 * @return string
	 */
	public function getLocalFilename()
	{
		if (isset($this->temp_filename)) {
			return $this->temp_filename;
		}

		$temp_filename = tempnam(sys_get_temp_dir(), 'fuzz-file-') . '.' . $this->getExtension();

		file_put_contents($temp_filename, $this->getRaw());

		return $this->temp_filename = $temp_filename;
	}

	/**
	 * Unlink this file's temporary location.
	 *
	 * @return void
	 */
	public function unlink()
	{
		if ($this->hasLocalFile()) {
			unlink($this->temp_filename);
			$this->temp_filename = null;
		}
	}

	/**
	 * Create a file from a physical file.
	 *
	 * @param string $filename
	 *        Full path to the initial file
	 * @param string $mime_type
	 *        An optional user-declared MIME type
	 *
	 * @return Fuzz\File\File
	 *         Instance of the File object for the file provided
	 */
	public static function createFromFile($filename, $mime_type = null)
	{
		if (file_exists($filename)) {
			return new static(new SymfonyFile($filename, false), $mime_type);
		}

		return false;
	}

	/**
	 * Get raw image data from the SplFileObject.
	 *
	 * @return string
	 *         String representation of the raw image binary data
	 */
	public function getRaw()
	{
		if (! is_null($this->raw)) {
			return $this->raw;
		}

		// Save our current position
		$position = $this->object->ftell();

		$this->object->rewind();

		$raw = '';

		while ($this->object->valid()) {
			$raw .= $this->object->fgets();
		}

		// Go back to our original position
		$this->object->fseek($position);

		return $this->raw = $raw;
	}

	/**
	 * Returns the file's size in bytes.
	 *
	 * @return int
	 */
	public function getSize()
	{
		return $this->info->getSize(); // @todo this should be right
		return strlen($this->getRaw());
	}

	/**
	 * Hash the raw contents of the file to get a unique-ish obfuscated title.
	 *
	 * @return string
	 *         MD5 of the file
	 */
	public function getFilename()
	{
		if (! is_null($this->filename)) {
			return $this->filename;
		}

		return $this->filename = hash('md5', $this->getRaw());
	}

	/**
	 * Use Symfony components to guess the file extension.
	 *
	 * @return string
	 *         File extension
	 */
	public function getExtension()
	{
		if (! is_null($this->extension)) {
			return $this->extension;
		}

		return $this->extension = ($this->info->getExtension() ?: $this->info->guessExtension());
	}

	/**
	 * Use Symfony components to guess the content type.
	 *
	 * @return string
	 *         The mime-type
	 */
	public function getMimeType()
	{
		if (! is_null($this->mime_type)) {
			return $this->mime_type;
		}

		if ($this->info instanceof SymfonyFile) {
			return $this->mime_type = $this->info->getMimeType();
		}

		return $this->mime_type = 'application/octet-stream';
	}

	/**
	 * Get the full filename.
	 *
	 * @return string
	 *         Full filename with extension
	 */
	public function getFullFilename()
	{
		return $this->getFilename() . '.' . $this->getExtension();
	}

	/**
	 * Check if the file MIME prefix is a certain thing.
	 *
	 * @param string $prefix
	 * @return bool
	 */
	public function hasMimePrefix($prefix)
	{
		return strpos($this->getMimeType(), "$prefix/") === 0;
	}

	/**
	 * Check if the file is an image.
	 *
	 * @return bool
	 *         True if the mimetype contains 'image', false otherwise
	 */
	public function isImage()
	{
		return $this->hasMimePrefix('image');
	}

	/**
	 * Check if the file is audio.
	 *
	 * @return bool
	 */
	public function isAudio()
	{
		return $this->hasMimePrefix('audio');
	}

	/**
	 * Check if the file is video.
	 *
	 * @return bool
	 */
	public function isVideo()
	{
		return $this->hasMimePrefix('video');
	}
}
