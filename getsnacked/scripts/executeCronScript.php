#!/usr/local/bin/php
<?

	ini_set('magic_quotes_gpc', 0);
	ini_set('mysql.default_host', 'localhost');
	ini_set('mysql.default_user', 'veisi_Admin');
	ini_set('mysql.default_password', 'cryptic');
	ini_set('include_path', '.:/usr/lib/php:/usr/local/lib/php:v_library/:../v_library');
	ini_set('error_log', '/home/dev/combined_dev_logs/veisi.com-php_cron_error_log');
	ini_set('log_errors_max_len', 0);
	ini_set('display_errors', 'On');

	require_once 'www/v_library/v_global.php';

	$cron = new cron($argv[1]);
	$cron->executeScript();

?>
