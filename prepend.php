<?php

// do a few defines for the WP environment
if (!defined('DB_COLLATE'))
	define('DB_COLLATE', 'utf8mb4_unicode_ci');

/**
 * Process on Create, copy, import, and move events.
 */
global $ds_runtime;
uc_debug('last event: ' . var_export($ds_runtime->last_ui_event, TRUE));

if ( FALSE !== $ds_runtime->last_ui_event ) {
	$events = array('site_created', 'site_copied', 'site_imported', 'site_moved', 'site_exported', 'site_deployed');
	if ( in_array( $ds_runtime->last_ui_event->action, $events ) ) {
uc_debug('triggering update...');
		$ds_runtime->add_action('init', array('UpdateCollation', 'cron_run'));
		$file = dirname(__FILE__) . '/trigger.txt';
		fopen($file, 'w+');
	}
}

function uc_debug($msg)
{
	if (function_exists('trace'))
		trace($msg);
}