<?

	/**
	 *  imageIndex object represents an image directory with its contents indexed in the database
	 *    The save and update methods are overridden to upload an image as well as write to the db
	 */
	class imageIndex extends activeRecord {
		// active record table
		protected $table;
		// existing auto increment field
		protected $autoincrement = 'imageID';
		// array unique id fields
		protected $idFields = array(
			'imageID'
		);
		// field array
		//   array(friendly name => array(field name, field type, min chars, max chars))
		protected $fields = array(
			'imageID'      => array('imageID', 'integer', 0, 10),
			'image'        => array('image', 'filename', 1, 45),
			'size'         => array('size', 'integer', 1, 10),
			'width'        => array('width', 'integer', 1, 4),
			'height'       => array('height', 'integer', 1, 4),
			'dateAdded'    => array('dateAdded', 'datetime', 0, 19),
			'lastModified' => array('lastModified', 'datetime', 0, 19)
		);
		// images folder
		protected $imageFolder;
		// images directory
		protected $imageDir;

		/**
		 *  Override: set image directory
		 *  Args: none
		 *  Return: none
		 */
		public function initialize() {
			$this->imageDir = systemSettings::get('IMAGEDIR');
			if (!preg_match('/\/$/', $this->imageDir)) {
				$this->imageDir .= '/';
			}
			$this->imageDir = $this->imageDir.$this->imageFolder;
			if (!preg_match('/\/$/', $this->imageDir)) {
				$this->imageDir .= '/';
			}
		} // function initialize

		/**
		 *  Set defaults for saving
		 *  Args: none
		 *  Return: none
		 */
		public function assertSaveDefaults() {
			$this->set('imageID', NULL, false);
			$this->set('dateAdded', 'NOW()', false);
			$this->enclose('dateAdded', false);
			$this->set('lastModified', 'NOW()', false);
			$this->enclose('lastModified', false);
		} // function assertSaveDefaults

		/**
		 *  Set defaults for updating
		 *  Args: none
		 *  Return: none
		 */
		public function assertUpdateDefaults() {
			$this->set('lastModified', 'NOW()', false);
			$this->enclose('lastModified', false);
		} // function assertUpdateDefaults

		/**
		 *  Parent function override, check for duplicate record
		 *  Args: none
		 *  Return: (boolean) duplicate record found
		 */
		public function isDuplicate() {
			if ($this->exists()) {
				// duplicate on updating
				//   load the original
				//   check if unique indexes have changed
				//   if change, query for duplicate on new values
				$class = get_class($this);
				$imageIndex = new $class($this->getID());
				if (!$imageIndex->exists()) {
					return false;
				} else {
					if ($imageIndex->get('image') != $this->get('image')) {
						$sql = "SELECT * FROM `".$this->table."` WHERE `".$this->fields['image'][0]."` = '".prep($this->get('image'))."'";
					} else {
						return false;
					}
				}
			} else {
				// duplicate on saving
				$sql = "SELECT * FROM `".$this->table."` WHERE `".$this->fields['image'][0]."` = '".prep($this->get('image'))."'";
			}
			$result = $this->dbh->query($sql);
			if ($result->rowCount) {
				addError('A duplicate image exists');
				return true;
			} else {
				return false;
			}
		} // function isDuplicate

		/**
		 *  Upload an image
		 *  Args: (str) image file
		 *  Return: (boolean) success
		 */
		protected function uploadImage($file) {
			if (isset($_FILES[$file]) && $_FILES[$file]['name']) {
				$image = new image($file);
				// always convert to gif
				$image->convertImage('gif');
				if ($image->copyImage($this->imageDir, $this->get('image'))) {
					$this->set('size', $image->get('size'));
					$this->set('width', $image->get('width'));
					$this->set('height', $image->get('height'));
					return true;
				}
			}
			return false;
		} // function uploadImage

		/**
		 *  Rename an image
		 *  Args: (str) image file, (str) new name
		 *  Return: (boolean) success
		 */
		protected function renameImage($original, $new) {
			$image = new image($original, 'file', $this->imageDir);
			if ($image->exists()) {
				if ($image->copyImage($this->imageDir, $new)) {
					return true;
				}
			}
			return false;
		} // function renameImage

		/**
		 *  Remove an image
		 *  Args: (str) image file
		 *  Return: (boolean) success
		 */
		protected function removeImage($file) {
			if (file_exists($this->imageDir.$file)) {
				if (unlink($this->imageDir.$file)) {
					return true;
				}
			}
			return false;
		} // function removeImage

		/**
		 *  Save an image index record and create its image file
		 *  Args: (str) $_FILES index of uploaded image
		 *  Return: (boolean) success
		 */
		public function save($file) {
			if (!$this->isDuplicate()) {
				if ($this->uploadImage($file)) {
					if (parent::save()) {
						return true;
					} else {
						$this->removeImage($this->get('image'));
					}
				} else {
					trigger_error('Unable to upload image ('.$this->get('image').' from '.$file.' to '.$this->imageDir.')', E_USER_NOTICE);
				}
			} else {
				addError('The image already exists');
			}
			return false;
		} // function save

		/**
		 *  Rename an image index record and its image file
		 *  Args: (str) new name
		 *  Return: (boolean) success
		 */
		public function rename($name) {
			if ($this->exists()) {
				$original = $this->get('image');
				$name = clean($name, $this->fields['image'][1]);
				if ($name && $name != $original) {
					$this->set('image', $name);
					if (!$this->isDuplicate()) {
						if ($this->renameImage($original, $name)) {
							if ($this->update()) {
								if (!$this->removeImage($original)) {
									trigger_error('There was an error while removing the original image ('.$original.' at '.$this->imageDir.')', E_USER_NOTICE);
								}
								return true;
							} else {
								// could not update the database, revert
								$this->set('image', $original);
								if (!$this->removeImage($name)) {
									trigger_error('Image index update error, unable to remove new image on failed update ('.$name.' at '.$this->imageDir.')', E_USER_NOTICE);
								}
							}
						} else {
							trigger_error('Unable to rename image ('.$original.' at '.$this->imageDir.')', E_USER_NOTICE);
							$this->set('image', $original);
						}
					} else {
						addError('The image already exists');
						$this->set('image', $original);
					}
				}
			}
			return false;
		} // function rename

		/**
		 *  Peplace an image index image file
		 *  Args: (str) $_FILES index of uploaded image
		 *  Return: (boolean) success
		 */
		public function replace($file) {
			if ($this->exists()) {
				if (!$this->isDuplicate()) {
					if ($this->uploadImage($file)) {
						$this->set('lastModified', 'NOW()', false);
						if ($this->update()) {
							return true;
						} else {
							// update fail
							trigger_error('Image index update error, unable to update on replace ('.$this->get('image').' at '.$this->imageDir.')', E_USER_NOTICE);
						}
					} else {
						trigger_error('Unable to upload image for replace ('.$this->get('image').' from '.$file.' to '.$this->imageDir.')', E_USER_NOTICE);
					}
				} else {
					addError('The image already exists');
				}
			}
			return false;
		} // function replace

		/**
		 *  Delete an image index record and remove its image file
		 *  Args: none
		 *  Return: (boolean) success
		 */
		public function delete() {
			if ($this->exists()) {
				if ($this->removeImage($this->get('image'))) {
					$id = $this->getID();
					$identifier = array();
					foreach ($this->idFields as $key => $val) {
						$identifier[] = "`".$this->fields[$val][0]."` = '".prep($id[$key])."'";
					}
					$sql = "DELETE FROM `".$this->table."` WHERE ".implode(' AND ', $identifier);
					$result = $this->dbh->query($sql);
					if ($result->rowCount) {
						return true;
					}
				}
			}
			return false;
		} // function delete

	} // class content

?>