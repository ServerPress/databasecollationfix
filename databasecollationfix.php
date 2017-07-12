<?php
/**
Plugin Name: Database Collation Fix
Plugin URL: https://serverpress.com/plugins/databasecollationfix
Description: Convert tables using utf8mb4_unicode_520_ci or utf8_unicode_520_ci collation to standard collation on a cron interval, plus on DesktopServer Create, Copy, Move, Import and Export operations.
Version: 1.2.2
Author: Dave Jesch
Author URI: http://serverpress.com
Text Domain: dbcollationfix
Domain path: /language
 */

class DS_DatabaseCollationFix
{
	private static $_instance = NULL;

	const CRON_NAME = 'ds_database_collation_fix';
	const TRIGGER_FILE = 'trigger.txt';

	/* Collation Algorithm to change database to: */
	private $_collation = 'utf8mb4_unicode_ci';
	/* List of Collation Algorithms to look for and change */
	private $_change_collation = array(
		'utf8mb4_unicode_520_ci',
		'utf8_unicode_520_ci',
	);

	private $_report = FALSE;
	private $_output = FALSE;

	/**
	 * Constructor
	 */
	private function __construct()
	{
		if (!defined('DAY_IN_SECONDS'))
			define('DAY_IN_SECONDS', 60 * 60 * 24);
		add_action('init', array($this, 'init'));

		// use override from wp-config.php if provided -- and not an undesired algorithm
		if (defined('DB_COLLATE')) {
			$collation = DB_COLLATE;
			if (!empty($collation) && !in_array(DB_COLLATE, $this->_change_collation))
				$this->_collation = DB_COLLATE;
		}
//$this->_collation = 'utf8_general_ci';			// force collation to this instead
	}

	/**
	 * Obtains a singleton instance of the plugin
	 * @return DS_DatabaseCollationFix instance
	 */
	public static function get_instance()
	{
		if (NULL === self::$_instance)
			self::$_instance = new self();
		return self::$_instance;
	}

	/**
	 * Callback for 'init' action. Used to set up cron and check for trigger set by DesktopServer prepend.php actions
	 */
	public function init()
	{
$this->_log(__METHOD__.'() starting');
		$next = wp_next_scheduled(self::CRON_NAME);
		if (FALSE === $next)
			$this->_update_schedule(DAY_IN_SECONDS);

		add_action(self::CRON_NAME, array(__CLASS__, 'cron_run'));

		if (file_exists($trigger_file = dirname(__FILE__) . DIRECTORY_SEPARATOR . self::TRIGGER_FILE)) {
$this->_log(__METHOD__.'() trigger file found');
			add_action('wp_loaded', array(__CLASS__, 'cron_run'));
			@unlink($trigger_file);
		}

		if (is_admin() && current_user_can('manage_options'))
			add_action('admin_menu', array($this, 'admin_menu'));
	}

	/**
	 * Updates the Cron schedule, adding the CRON_NAME to be triggered once per day at midnight.
	 * @param string $interval
	 */
	private function _update_schedule($interval = NULL)
	{
		if ( defined( 'WP_INSTALLING' ) && WP_INSTALLING )
			return;				// do nothing if WP is trying to install

		$time_start = strtotime('yesterday');
		if (NULL === $time_start)
			$interval = DAY_IN_SECONDS;

		$timestamp = $time_start + $interval;
		wp_clear_scheduled_hook(self::CRON_NAME);
		wp_schedule_event($timestamp, 'daily', self::CRON_NAME);
	}

	/**
	 * Callback for the cron operation (set up in init() method)
	 */
	public static function cron_run()
	{
		$uc = self::get_instance();
		$uc->modify_collation();
	}

	/**
	 * This method does the work of modifying the collation used by the database tables and their columns
	 * @param boolean $report TRUE to output report of operations; otherwise FALSE
	 */
	public function modify_collation($report = FALSE)
	{
$this->_log_action(__METHOD__);
		global $wpdb;

		$this->_report = $report;
		$table_count = $column_count = 0;
		if ($report)
			echo '<div style="width:100%; margin-top:15px">';

		// search for this collation method
		$collation = 'utf8mb4_unicode_520_ci';
		$collation_term = " COLLATE={$collation}";

		// get all tables that match $wpdb's table prefix
		$sql = "SHOW TABLES LIKE '{$wpdb->prefix}%'";
		$res = $wpdb->get_col($sql);
		if (NULL !== $res) {
			foreach ($res as $table) {
$this->_log(__METHOD__.'() checking table ' . $table);
				$this->_report(sprintf(__('Checking table "%s"...', 'dbcollationfix'), $table));
				// check how the table was created
				$sql = "SHOW CREATE TABLE `{$table}`";
				$create_table_res = $wpdb->get_row($sql, ARRAY_A);
$this->_log(__METHOD__.'() res=' . var_export($create_table_res, TRUE));
				$create_table = $create_table_res['Create Table'];

				// check table collation and modify if it's an undesired algorithm
				$mod = FALSE;
				foreach ($this->_change_collation as $coll) {
					$collation_term =" COLLATE={$coll}";
$this->_log(__METHOD__.'() checking collation: ' . $collation_term);
					if (FALSE !== stripos($create_table, $collation_term)) {
						$this->_report(sprintf(__('- found "%1$s" and ALTERing to "%2$s"...', 'dbcollationfix'),
							$collation_term, $this->_collation));
						++$table_count;

						$sql = "ALTER TABLE `{$table}` COLLATE={$this->_collation}";
$this->_report($sql, TRUE);
						$alter = $wpdb->query($sql);
$this->_log(__METHOD__.'() sql=' . $sql . ' res=' . var_export($alter, TRUE));
						$mod = TRUE;
						break;
					}
				}
				if (!$mod) {
					$this->_report(__('- no ALTERations required.', 'dbcollationfix'));
				}

				// check collumn collation and modify if it's an undesired algorithm
				$sql = "SHOW FULL COLUMNS FROM `{$table}`";
				$columns_res = $wpdb->get_results($sql, ARRAY_A);
				if (NULL !== $columns_res) {
					foreach ($columns_res as $row) {
						// if the column is using an undesired Collation Algorithm
$this->_log(__METHOD__.'() checking collation of `' . $row['Collation'] . '`: (' . implode(',', $this->_change_collation) . ')');
						if (in_array($row['Collation'], $this->_change_collation)) {
$this->_log(__METHOD__.'() updating column\'s collation');
							$row['Collation'] = $this->_collation;
							$null = 'NO' === $row['Null'] ? 'NOT NULL' : 'NULL';
							$default = !empty($rpw['Default']) ? "DEFAULT '{$row['Default']}" : '';

							$this->_report(sprintf(__('- found column `%1$s` with collation of "%2$s" and ALTERing to "%3$s".', 'dbcollationfix'),
								$row['Field'], $row['Collation'], $this->_collation));
							++$column_count;

							// alter the table, changing the column's Collation Algorithm
							$sql = "ALTER TABLE `{$table}`
								CHANGE `{$row['Field']}` `{$row['Field']}` {$row['Type']} COLLATE {$row['Collation']} {$null} {$default}";
$this->_report($sql, TRUE);
							$alter_res = $wpdb->query($sql);
$this->_log(__METHOD__.'() alter=' . $sql . ' res=' . var_export($alter_res, TRUE));
						}
					}
				}
			}
		}

		$this->_report(sprintf(__('Altered %1$d tables and %2$d columns.', 'dbcollationfix'),
			$table_count, $column_count));
		if ($report)
			echo '</div>';
	}

	/**
	 * Outputs reporting information to the screen when performing ALTERations on demand.
	 * @param string $message Message to output
	 * @param boolean $debug TRUE if debugging data; otherwise FALSE
	 */
	private function _report($message, $debug = FALSE)
	{
//echo $message, PHP_EOL;
		if ($this->_report || $this->_output) {
			$out = TRUE;
			if ($debug && (!defined('WP_DEBUG') || !WP_DEBUG))
				$out = FALSE;

			if ($out || $this->_output)
				echo $message, ($this->_output ? PHP_EOL : '<br/>');
		}
	}

	/**
	 * Sets the output flag, used in reporting
	 * @param boolean $output TRUE to perform output; FALSE for no output
	 */
	public function set_output($output)
	{
		$this->_output = $output;
	}

	/**
	 * Callback for the 'admin_menu' action. Used to add the menu item to the Tools menu
	 */
	public function admin_menu()
	{
		add_management_page(__('Collation Fix', 'dbcollationfix'),			// page title
			__('Collation Fix', 'dbcollationfix'),							// menu title
			'manage_options',												// capability
			'ds-db-collation',												// menu_slug
			array($this, 'admin_page'));									// callback
	}

	/**
	 * Outputs the contents of the tool's admin page.
	 */
	public function admin_page()
	{
		echo '<div class="wrap">';
		echo '<h2>', __('ServerPress Database Collation Fix tool', 'dbcollationfix'), '</h2>';
		echo '<p>', __('This tool is used to convert your site\'s database tables from using the ...unicode_520_ci Collation Algorithms to use a slightly older, but more compatible utf8mb4_unicode_ci Collation Algorithm.', 'dbcollationfix'), '</p>';
		echo '<p>', __('The tool will automatically run every 24 hours and change any newly created database table. Or, you can use the button below to perform the database alterations on demand.', 'dbcollationfix'), '</p>';
		echo '<a class="button-primary" href="', esc_url(add_query_arg('run', '1')), '">', __('Fix Database Collation', 'dbcollationfix'), '</a>';

		if (isset($_GET['run']) && '1' === $_GET['run'])
			$this->modify_collation(TRUE);		// perform collation changes, with reporting

		echo '</div>';
	}

	/**
	 * Performs logging, including the DS action that was last triggered before the logging was done
	 * @param string $method The method name calling this method
	 */
	private function _log_action($method)
	{
		$action = $site_name = '';
		global $ds_runtime;
		if ( isset( $ds_runtime ) ) {
			if ( FALSE !== $ds_runtime->last_ui_event ) {
				$action = $ds_runtime->last_ui_event->action;

				$site_name = '';
				if ( in_array( $ds_runtime->last_ui_event->action, ['site_copied', 'site_moved'] ) ) {
					$site_name = $ds_runtime->last_ui_event->info[1];
				} else {
					$site_name = $ds_runtime->last_ui_event->info[0];
				}
			}
		}

		$this->_log( $method . '() on action "' . $action . '" for site name "' . $site_name . '"' );
	}

	/**
	 * Performs logging for debugging purposes
	 * @param string $msg The data to be logged
	 * @param boolean $backtrace TRUE to also log backtrace information
	 */
	private function _log($msg, $backtrace = FALSE)
	{
return;
		$file = dirname(__FILE__) . '/~log.txt';
		$fh = @fopen($file, 'a+');
		if (FALSE !== $fh) {
			if (NULL === $msg)
				fwrite($fh, date('Y-m-d H:i:s'));
			else
				fwrite($fh, date('Y-m-d H:i:s - ') . $msg . "\r\n");

			if ($backtrace) {
				$callers = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
				array_shift($callers);
				$path = dirname(dirname(dirname(plugin_dir_path(__FILE__)))) . DIRECTORY_SEPARATOR;

				$n = 1;
				foreach ($callers as $caller) {
					$func = $caller['function'] . '()';
					if (isset($caller['class']) && !empty($caller['class'])) {
						$type = '->';
						if (isset($caller['type']) && !empty($caller['type']))
							$type = $caller['type'];
						$func = $caller['class'] . $type . $func;
					}
					$file = isset($caller['file']) ? $caller['file'] : '';
					$file = str_replace('\\', '/', str_replace($path, '', $file));
					if (isset($caller['line']) && !empty($caller['line']))
						$file .= ':' . $caller['line'];
					$frame = $func . ' - ' . $file;
					$out = '    #' . ($n++) . ': ' . $frame . PHP_EOL;
					fwrite($fh, $out);
					if (self::$_debug_output)
						echo $out;
				}
			}

			fclose($fh);
		}
	}
}

DS_DatabaseCollationFix::get_instance();
