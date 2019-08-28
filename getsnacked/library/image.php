<?

	class image {

		// supported image types
		private $imageTypes = array(
			'image/gif' => IMAGETYPE_GIF,
			'image/jpeg' => IMAGETYPE_JPEG,
			'image/pjpeg' => IMAGETYPE_JPEG,
			'image/bmp' => IMAGETYPE_BMP,
			'image/png' => IMAGETYPE_PNG
		);
		// image data
		private $imageFile = false;
		// image type
		private $imageType = false;
		// image resource
		private $image = false;

		/**
		 *  Populate image information either from file upload or local source
		 *  Args: (str) image file name or index of $_FILES upload, (str) image source
		 *  Args: (str) local source directory
		 *  Return: none
		 */
		public function __construct($image, $source = 'upload', $dir = false) {
			switch ($source) {
				case 'param':
					if (array_key_exists($image['type'], $this->imageTypes)) {
						$this->imageType = $image['type'];
						$this->imageFile = $image;
						$size = getimagesize($this->imageFile['tmp_name']);
						$this->imageFile['width'] = $size[0];
						$this->imageFile['height'] = $size[1];
						$this->createImageResource();
					}
					break;
				case 'file':
					if (file_exists($dir.$image)) {
						if (function_exists('exif_imagetype')) {
							$imageType = exif_imagetype($dir.$image);
						} elseif (isDevEnvironment()) {
							// attempt to set image type via file extension
							//   this should only be allowed in dev environments for security reasons
							if (preg_match('/\.gif$/i', $image)) {
								$imageType = IMAGETYPE_GIF;
							} elseif (preg_match('/\.jpe?g$/i', $image)) {
								$imageType = IMAGETYPE_JPEG;
							} elseif (preg_match('/\.bmp$/i', $image)) {
								$imageType = IMAGETYPE_BMP;
							} elseif (preg_match('/\.png$/i', $image)) {
								$imageType = IMAGETYPE_PNG;
							} else {
								$imageType = false;
								trigger_error('Invalid image file type', E_USER_NOTICE);
							}
						} else {
							$imageType = false;
							trigger_error('Unable to determine image file type', E_USER_NOTICE);
						}
						if (in_array($imageType, $this->imageTypes)) {
							$this->imageType = $imageType;
							$this->imageFile = array();
							$this->imageFile['name'] = $image;
							$this->imageFile['type'] = array_search($imageType, $this->imageTypes);
							$this->imageFile['tmp_name'] = $dir.$image;
							$this->imageFile['error'] = 0;
							$this->imageFile['size'] = filesize($dir.$image);
							$size = getimagesize($dir.$image);
							$this->imageFile['width'] = $size[0];
							$this->imageFile['height'] = $size[1];
							$this->createImageResource();
						}
					}
					break;
				case 'upload':
				default:
					if (isset($_FILES[$image]) && array_key_exists($_FILES[$image]['type'], $this->imageTypes)) {
						$this->imageType = $_FILES[$image]['type'];
						$this->imageFile = $_FILES[$image];
						$size = getimagesize($this->imageFile['tmp_name']);
						$this->imageFile['width'] = $size[0];
						$this->imageFile['height'] = $size[1];
						$this->createImageResource();
					}
					break;
			}
		} // function __construct
		
		/**
		 *  Create image resource from valid image data
		 *  Args: none
		 *  Return: (boolean) success
		 */
		private function createImageResource() {
			if ($this->imageFile) {
				switch ($this->imageFile['type']) {
					case 'image/gif':
						$this->image = imagecreatefromgif($this->imageFile['tmp_name']);
						break;
					case 'image/pjpeg':
					case 'image/jpeg':
						$this->image = imagecreatefromjpeg($this->imageFile['tmp_name']);
						break;
					case 'image/bmp':
						$this->image = imagecreatefromwbmp($this->imageFile['tmp_name']);
						break;
					case 'image/png':
						$this->image = imagecreatefrompng($this->imageFile['tmp_name']);
						break;
					default:
						return false;
						break;
				}
				return true;
			}
			return false;
		} // function createImageResource

		/**
		 *  Return requested image data from imageFile array
		 *  Args: (str) data request
		 *  Return: (str) image data
		 */
		public function getImageData($request) {
			if ($this->imageFile) {
				return $this->imageFile[$request];
			} else {
				return false;
			}
		} // function getImageData

		/**
		 *  Copy an uploaded image from tmp to destination
		 *  Args: (str) destination directory, (str) image name
		 *  Return: (boolean) success
		 */
		public function copyImage($destination, $name = false) {
			if ($this->imageFile) {
				if (!$name) {
					$name = $this->imageFile['name'];
				}
				if (!preg_match('/\/$/', $destination)) {
					$destination .= '/';
				}
				if (copy($this->imageFile['tmp_name'], $destination.$name)) {
					return true;
				} else {
					return false;
				}
			} else {
				return false;
			}
		} // function copyImage

		/**
		 *  Resizes an image
		 *  Args: (str) width, (str) height
		 *  Return: (boolean) success
		 */
		public function resize($width, $height) {
			if (is_resource($this->image)) {
				$resized = imagecreatetruecolor($width, $width);
				imagecopyresampled($resized, $this->image, 0, 0, 0, 0, $width, $height, $this->imageFile['width'], $this->imageFile['height']);
				switch ($this->imageFile['type']) {
					case 'image/gif':
						imagegif($resized, $this->imageFile['tmp_name']);
						break;
					case 'image/pjpeg':
					case 'image/jpeg':
						imagejpeg($resized, $this->imageFile['tmp_name']);
						break;
					case 'image/bmp':
						imagewbmp($resized, $this->imageFile['tmp_name']);
						break;
					case 'image/png':
						imagepng($resized, $this->imageFile['tmp_name']);
						break;
					default:
						return false;
						break;
				}
				$this->image = $resized;
				return true;
			}
			return false;
		} // function resize

		/**
		 *  Convert image file format from one to another (supported image format)
		 *  Args: (str) image format
		 *  Return: (boolean) success
		 */
		public function convertImage($format) {
			if ($this->imageFile) {
				$format = strtolower($format);
				$fileName = preg_replace('/\.(gif|jpe?g|pjpe?g|bmp|png)$/i', '', $this->imageFile['tmp_name']);
				if ($format != preg_replace('/^image\//', '', $this->imageFile['type'])) {
					switch ($format) {
						case 'gif':
							imagegif($this->image, $fileName.'.gif');
							$this->imageFile['type'] = 'image/gif';
							$this->imageFile['tmp_name'] = $fileName.'.gif';
							break;
						case 'jpeg':
						case 'jpg':
							if ($this->imageFile['type'] == 'image/gif' || $this->imageFile['type'] == 'image/png') {
								$this->replaceTransparentWhite();
								imagejpeg($this->image, $fileName.'.jpg');
							} else {
								imagejpeg($this->image, $fileName.'.jpg');
							}
							$this->imageFile['type'] = 'image/jpeg';
							$this->imageFile['tmp_name'] = $fileName.'.jpg';
							break;
						case 'bmp':
							imagewbmp($this->image, $fileName.'.bmp');
							$this->imageFile['type'] = 'image/bmp';
							$this->imageFile['tmp_name'] = $fileName.'.bmg';
							break;
						case 'png':
							imagepng($this->image, $fileName.'.png');
							$this->imageFile['type'] = 'image/png';
							$this->imageFile['tmp_name'] = $fileName.'.png';
						default:
							return false;
							break;
					}
					$fileName = preg_replace('/\.(gif|jpe?g|pjpe?g|bmp|png)$/i', '', $this->imageFile['name']);
					$this->imageFile['name'] = $fileName.'.'.$format;
					return true;
				}
			}
			return false;
		} // function convertImage

		/**
		 *  Replaces transparent pixels with white pixels
		 *  Args: none
		 *  Return: (boolean) success
		 */
		public function replaceTransparentWhite(){
			if (is_resource($this->image)) {
				$src_w = ImageSX($this->image);
				$src_h = ImageSY($this->image);
				$replaced = imagecreatetruecolor($src_w, $src_h);
				$white = ImageColorAllocate($replaced, 255, 255, 255);
				ImageFill($replaced, 0, 0, $white);
				ImageAlphaBlending($replaced, TRUE);
				imagecopy($replaced, $this->image, 0,0,0,0, $src_w, $src_h);
				switch ($this->imageFile['type']) {
					case 'image/gif':
						imagegif($replaced, $this->imageFile['tmp_name']);
						break;
					case 'image/pjpeg':
					case 'image/jpeg':
						imagejpeg($replaced, $this->imageFile['tmp_name']);
						break;
					case 'image/bmp':
						imagewbmp($replaced, $this->imageFile['tmp_name']);
						break;
					case 'image/png':
						imagepng($replaced, $this->imageFile['tmp_name']);
						break;
					default:
						return false;
						break;
				}
				$this->image = $replaced;
				return true;
			}
			return false;
		} // function replaceTransparentWhite

		/**
		 *  Returns true if an image resource was established
		 *  Args: none
		 *  Return: (boolean) image resource exists
		 */
		public function exists() {
			if ($this->image !== false) {
				return true;
			} else {
				return false;
			}
		} // function exists

		/**
		 *  Returns requested image data
		 *  Args: (str) image data
		 *  Return: (mixed) image data value, null if not available
		 */
		public function get($data) {
			if (isset($this->imageFile[$data])) {
				return $this->imageFile[$data];
			} else {
				return NULL;
			}
		} // function get

	} // class image

?>