<?php
/**
 * @package smz_slideshow
 * @version 3.6
 * @author Sergio Manzi
 * @link http://smz.it
 * @copyright Copyright (c) 2013 - 2015 Sergio Manzi. All Rights Reserved.
 * @license GNU General Public License version 3, or later
 *
 * Based on https://gist.github.com/philBrown/880506
 * https://github.com/philBrown
 *
 */

defined('_JEXEC') or die;

class SMZImage {

	/**
	 * @var resource
	 */
	protected $image;

	/**
	 * @var int
	 */
	protected $width;

	/**
	 * @var int
	 */
	protected $height;

	/**
	 * @var int
	 */
	protected $type;


	/**
	 * Class constructor
	 *
	 * @param string $file OPTIONAL Path to image file or image data as string
	 * @return void
	 */
	public function __construct($imagesource = null)
	{
		$this->load($imagesource);

		return true;
	}


	/**
	 * Get image from file, URL or image data
	 *
	 * @param string $file OPTIONAL Path to image file, image URL or image data as string
	 * @return ImageManipulator for a fluent interface
	 * @throws InvalidArgumentException
	 */
	public function load($imagesource = null) {
		if (is_null($imagesource))
		{
			return;
		}

		// Destroy current image
		if (is_resource($this->image))
		{
			imagedestroy($this->image);
		}
		$this->width = null;
		$this->height = null;
		$this->type = null;

		if (is_file($imagesource))
		{
			// Get image from file
			if (!is_readable($imagesource))
			{
				throw new InvalidArgumentException('SMZImage::getImage() - Unable to read from file: ' . $imagesource);
			}
			list ($this->width, $this->height, $this->type) = getimagesize($imagesource);
			switch ($this->type)
			{
				case IMAGETYPE_GIF :
					$this->image = imagecreatefromgif($imagesource);
					break;
				case IMAGETYPE_JPEG :
					$this->image = imagecreatefromjpeg($imagesource);
					break;
				case IMAGETYPE_PNG :
					$this->image = imagecreatefrompng($imagesource);
					break;
				default :
					throw new InvalidArgumentException('SMZImage::getImage() - Unsupported image type: ' . $this->type);
			}
		}
		else
		{
			// Get image from URL or string
			if (filter_var($imagesource, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED))
			{
				// Get image from URL
				$imagesource = file_get_contents($imagesource);
			}

			if (!$this->image = imagecreatefromstring($imagesource))
			{
				throw new InvalidArgumentException('SMZImage::getImage() - Unable to create image from data string');
			}
			$this->width = imagesx($this->image);
			$this->height = imagesy($this->image);
			$this->type = IMAGETYPE_JPEG;  // TODO: get the original image type
		}

		return $this;

	}


	/**
	 * Save current image to file
	 *
	 * @param string $fileName
	 * @return void
	 * @throws RuntimeException
	 */
	public function save($fileName, $type = null) {
		if ($type == null)
		{
			$type = $this->type;
		}
		$dir = dirname($fileName);
		if (!is_dir($dir))
		{
			if (!mkdir($dir, 0755, true))
			{
				throw new RuntimeException('SMZImage::save() - Error creating directory ' . $dir);
			}
		}

		try
		{
			switch ($type)
			{
				case IMAGETYPE_GIF :
					if (!imagegif($this->image, $fileName))
					{
						throw new RuntimeException('SMZImage::save() - Unable to save GIF image ' . $fileName);
					}
					break;
				case IMAGETYPE_PNG :
					if (!imagepng($this->image, $fileName))
					{
						throw new RuntimeException('SMZImage::save() - Unable to save PNG image ' . $fileName);
					}
					break;
				case IMAGETYPE_JPEG :
				default :
					if (!imagejpeg($this->image, $fileName, 95))
					{
						throw new RuntimeException('SMZImage::save() - Unable to save JPEG image ' . $fileName);
					}
			}
		}
		catch (Exception $ex)
		{
			return;
			//throw new RuntimeException('SMZImage::save() - Unable to save image ' . $fileName);
		}
	}


	/**
	 * Resamples the current image
	 *
	 * @param int $width New width
	 * @param int $height New height
	 * @param bool $constrainProportions Constrain current image proportions when resizing
	 * @return ImageManipulator for a fluent interface
	 * @throws RuntimeException
	 */
	public function resample($width, $height, $constrainProportions = true)
	{
		if (!is_resource($this->image))
		{
			throw new RuntimeException('SMZImage::resample() - No image set');
		}
		if ($constrainProportions)
		{
			if ($this->height >= $this->width)
			{
				$width = round($height / $this->height * $this->width);
			}
			else
			{
				$height = round($width / $this->width * $this->height);
			}
		}
		$temp = imagecreatetruecolor($width, $height);
		if ($this->type == IMAGETYPE_PNG)
		{
			imagealphablending($temp, false);
			imagesavealpha($temp, true);
			$transparent = imagecolorallocatealpha($temp, 255, 255, 255, 127);
			imagefilledrectangle($temp, 0, 0, $width, $height, $transparent);
		}
		imagecopyresampled($temp, $this->image, 0, 0, 0, 0, $width, $height, $this->width, $this->height);
		return $this->_replace($temp);
	}


	public function scale($width, $height) {
		if (!is_resource($this->image))
		{
			throw new RuntimeException('SMZImage::scale() - No image set');
		}
		$rate = $this->width / $this->height;
		//if()
		$curHeight = $height;
		$curWidth = $width;
		$height = $width / $rate;
		if ($height > $curHeight)
		{
			$height = $curHeight;
			$width = $height * $rate;
		}
		if ($width > $curWidth)
		{
			$width = $curWidth;
			$height = $width / $rate;
		}
		$temp = imagecreatetruecolor($width, $height);
		if ($this->type == IMAGETYPE_PNG)
		{
			imagealphablending($temp, false);
			imagesavealpha($temp, true);
			$transparent = imagecolorallocatealpha($temp, 255, 255, 255, 127);
			imagefilledrectangle($temp, 0, 0, $width, $height, $transparent);
		}
		imagecopyresampled($temp, $this->image, 0, 0, 0, 0, $width, $height, $this->width, $this->height);
		return $this->_replace($temp);
	}


	public function scaleUp($width, $height) {
		if (!is_resource($this->image))
		{
			throw new RuntimeException('SMZImage::scaleUp() - No image set');
		}
		$rate = $this->width / $this->height;
		$curHeight = $height;
		$height = $width / $rate;
		if ($height < $curHeight)
		{
			$height = $curHeight;
			$width = $height * $rate;
		}
		$temp = imagecreatetruecolor($width, $height);
		if ($this->type == IMAGETYPE_PNG)
		{
			imagealphablending($temp, false);
			imagesavealpha($temp, true);
			$transparent = imagecolorallocatealpha($temp, 255, 255, 255, 127);
			imagefilledrectangle($temp, 0, 0, $width, $height, $transparent);
		}
		imagecopyresampled($temp, $this->image, 0, 0, 0, 0, $width, $height, $this->width, $this->height);
		return $this->_replace($temp);
	}


	public function reFill($width, $height) {
		if (!is_resource($this->image))
		{
			throw new RuntimeException('SMZImage::reFill() - No image set');
		}
		$this->scaleUp($width, $height);
		$temp = imagecreatetruecolor($width, $height);
		if ($this->type == IMAGETYPE_PNG)
		{
			imagealphablending($temp, false);
			imagesavealpha($temp, true);
			$transparent = imagecolorallocatealpha($temp, 255, 255, 255, 127);
			imagefilledrectangle($temp, 0, 0, $width, $height, $transparent);
		}
		imagecopy($temp, $this->image, 0, 0, 0 - ($width - $this->width) / 2, 0 - ($height - $this->height) / 2, $width, $height);
		return $this->_replace($temp);
	}


	/**
	 * Enlarge canvas
	 *
	 * @param int $width Canvas width
	 * @param int $height Canvas height
	 * @param array $rgb RGB colour values
	 * @param int $xpos X-Position of image in new canvas, null for centre
	 * @param int $ypos Y-Position of image in new canvas, null for centre
	 * @return ImageManipulator for a fluent interface
	 * @throws RuntimeException
	 */
	public function enlargeCanvas($width, $height, array $rgb = array(), $xpos = null, $ypos = null) {
		if (!is_resource($this->image))
		{
			throw new RuntimeException('SMZImage::enlargeCanvas() - No image set');
		}
		$width = max($width, $this->width);
		$height = max($height, $this->height);
		$temp = imagecreatetruecolor($width, $height);
		if (count($rgb) == 3)
		{
			$bg = imagecolorallocate($temp, $rgb[0], $rgb[1], $rgb[2]);
			imagefill($temp, 0, 0, $bg);
		}
		if (null === $xpos)
		{
			$xpos = round(($width - $this->width) / 2);
		}
		if (null === $ypos)
		{
			$ypos = round(($height - $this->height) / 2);
		}
		imagecopy($temp, $this->image, (int) $xpos, (int) $ypos, 0, 0, $this->width, $this->height);
		return $this->_replace($temp);
	}


	/**
	 * Crop image
	 *
	 * @param int|array $x1 Top left x-coordinate of crop box or array of coordinates
	 * @param int $y1 Top left y-coordinate of crop box
	 * @param int $x2 Bottom right x-coordinate of crop box
	 * @param int $y2 Bottom right y-coordinate of crop box
	 * @return ImageManipulator for a fluent interface
	 * @throws RuntimeException
	 */
	public function crop($x1, $y1 = 0, $x2 = 0, $y2 = 0) {
		if (!is_resource($this->image))
		{
			throw new RuntimeException('SMZImage::crop() - No image set');
		}
		if (is_array($x1) && 4 == count($x1))
		{
			list($x1, $y1, $x2, $y2) = $x1;
		}
		$x1 = max($x1, 0);
		$y1 = max($y1, 0);
		$x2 = min($x2, $this->width);
		$y2 = min($y2, $this->height);
		$width = $x2 - $x1;
		$height = $y2 - $y1;
		$temp = imagecreatetruecolor($width, $height);
		imagecopy($temp, $this->image, 0, 0, $x1, $y1, $width, $height);
		return $this->_replace($temp);
	}


	/**
	 * Fit image
	 *
	 * @return ImageManipulator for a fluent interface
	 */
	public function fit($width, $height, $background) {
		$rgb = $this->hex2RGB($background);
		if (is_null($background))
		{
			$rgb = array(0, 0, 0);
		}
		$this->scale($width, $height);
		$this->enlargeCanvas($width, $height, $rgb);
		return $this;
	}


	/**
	 * Replace current image resource with a new one
	 *
	 * @param resource $res New image resource
	 * @return ImageManipulator for a fluent interface
	 * @throws UnexpectedValueException
	 */
	protected function _replace($res) {
		if (!is_resource($res))
		{
			throw new UnexpectedValueException('SMZImage::_replace() - Invalid resource');
		}
		if (is_resource($this->image))
		{
			imagedestroy($this->image);
		}
		$this->image = $res;
		$this->width = imagesx($res);
		$this->height = imagesy($res);
		return $this;
	}


	/**
	 * Returns the GD image resource
	 *
	 * @return resource
	 */
	public function getResource() {
		return $this->image;
	}


	/**
	 * Get current image resource width
	 *
	 * @return int
	 */
	public function getWidth() {
		return $this->width;
	}


	/**
	 * Get current image height
	 *
	 * @return int
	 */
	public function getHeight() {
		return $this->height;
	}


	/**
	 * Get current image type
	 *
	 * @return int
	 */
	public function getType() {
		return $this->type;
	}


	/**
	*
	* Thanks to  Michael Kaiser
	* https://plus.google.com/u/0/b/110725720433094046987
	*
	*/

	function hex2RGB($hex) 
	{
		$hex = substr($hex, 1);
		preg_match("/^#{0,1}([0-9a-f]{1,6})$/i",$hex,$match);
		if(!isset($match[1]))
		{
			return false;
		}

		if(strlen($match[1]) == 6)
		{
			list($r, $g, $b) = array($hex[0].$hex[1],$hex[2].$hex[3],$hex[4].$hex[5]);
		}
		elseif(strlen($match[1]) == 3)
		{
			list($r, $g, $b) = array($hex[0].$hex[0],$hex[1].$hex[1],$hex[2].$hex[2]);
		}
		else if(strlen($match[1]) == 2)
		{
			list($r, $g, $b) = array($hex[0].$hex[1],$hex[0].$hex[1],$hex[0].$hex[1]);
		}
		else if(strlen($match[1]) == 1)
		{
			list($r, $g, $b) = array($hex.$hex,$hex.$hex,$hex.$hex);
		}
		else
		{
			return false;
		}

		$color = array();
		$color[] = hexdec($r);
		$color[] = hexdec($g);
		$color[] = hexdec($b);

		return $color;
	}

}
