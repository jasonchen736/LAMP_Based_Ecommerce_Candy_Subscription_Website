<?

	class sessionHandler {

		// database handler
		private static $dbh;
		private static $life;

		/**
		 *  Set session life
		 *  Args: none
		 *  Return: none
		 */
		public static function initialize() {
			self::$dbh = database::getInstance();
			self::$life = systemSettings::get('SESSIONDURATION');
		} // function initialize

		/**
		 *  Establish custom session functions
		 *  Args: none
		 *  Return: none
		 */
		public static function setHandler() {
			session_set_save_handler(
				array('sessionHandler', 'open'),
				array('sessionHandler', 'close'),
				array('sessionHandler', 'read'),
				array('sessionHandler', 'write'),
				array('sessionHandler', 'destroy'),
				array('sessionHandler', 'gc')
			);
		} // function setHandler

		/**
		 *  Session open
		 *  Args: (str) save path, (str) session name
		 *  Return: (boolean) true
		 */
		public static function open($save_path, $session_name) {
			return true;
		} // function open

		/**
		 *  Session close
		 *  Args: none
		 *  Return: (boolean) true
		 */
		public static function close() {
			self::gc();
			return true;
		} // function close

		/**
		 *  Read a session and return data
		 *  Args: (str) session id
		 *  Return: (str) session data
		 */
		public static function read($id) {
			$data = '';
			$id = prep($id);
			$result = self::$dbh->query("SELECT `session_data` FROM `sessions` WHERE `session_id` = '".$id."' AND `expires` > ".time());
			if ($result->rowCount) {
				$row = $result->fetchAssoc();
				$data = $row['session_data'];
			}
			return $data;
		} // function read

		/**
		 *  Write session data to database
		 *  Args: (str) session id, (str) session data
		 *  Return: (boolean) true
		 */
		public static function write($id, $data) {
			$time = time() + self::$life;
			$id = prep($id);
			$data = prep($data);
			self::$dbh->query("INSERT INTO `sessions` (`session_id`, `session_data`, `expires`) VALUES ('".$id."', '".$data."', ".$time.") ON DUPLICATE KEY UPDATE `session_data` = '".$data."', `expires` = ".$time);
			return true;
		} // function write

		/**
		 *  Destroys a session
		 *  Args: (str) session id
		 *  Return: (boolean) true
		 */
		public static function destroy($id) {
			$id = prep($id);
			self::$dbh->query("DELETE FROM `sessions` WHERE `session_id` = '".$id."'");
			return true;
		} // function destroy

		/**
		 *  Garbage collection removes expired sessions
		 *  Args: none
		 *  Return: (boolean) true
		 */
		public static function gc() {
			self::$dbh->query('DELETE FROM `sessions` WHERE `expires` < '.time());
			return true;
		} // function gc

} // class sessionHandler