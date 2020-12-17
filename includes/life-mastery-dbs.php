<?php

global $wpdb;

$charset_collate = $wpdb->get_charset_collate();

$table_name = $wpdb->prefix . 'lm_attendance_logs';


if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
	$sql = "CREATE TABLE `{$table_name}` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`group_id` int(11) NOT NULL,
	`user_id` int(11) NOT NULL,
	`ip_address` varchar(16) DEFAULT NULL,
	`device_id` text DEFAULT NULL,
	`log_by_user_id` int(11) DEFAULT NULL COMMENT 'The user_id who generate this log',
	`attendance_type` ENUM('absent','present') NOT NULL DEFAULT 'absent',
	`comment` TEXT NOT NULL,
	`created_at` datetime NOT NULL,
	`modified_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NULL,
	PRIMARY KEY (`id`)
	) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	dbDelta( $sql );
}