<?php
/**
 * Plugin Name: domOS Dummy Data
 * Description: Used to add dummy data to your website
 * Author: raldea89
 * Version: 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Comment from branch-from-main - again and gain


/**
 * These are the dummy data populating functions.
 *
 * add_dlm_dummy_data() adds dummy data to the report_log table ( so data used inside the Reports page of the plugin ) in order to test the Reports page. To use this you can add it to functions.php of your theme file and attach an action to it, for example wp_head, and visit the website. This way it will populate with data, and when you'll exit the website the function will terminate.
 *
 * add_dlm_dummy_logs() adds dummy data to the download_log table used to test the database tables upgrading process. To use this you can add it to functions.php of your theme file and attach an action to it, for example wp_head, and visit the website. This way it will populate with data, and when you'll exit the website the function will terminate.
 *
 * displayDates() is a function, used by the above two functions, that manipulates the dates that we need to set into the tables of our database. This function does not need a calling.
 */
function add_dlm_dummy_data() {
	// want to change this to test in the main branch.
	global $wpdb;
	$dlms  = get_posts(
		array(
			'post_type'      => 'dlm_download',
			'posts_per_page' => - 1,
		)
	);
	$dates = displayDates( '2019-05-10', '2021-12-06' );

	foreach ( $dates as $date ) {
		$structured_dlm = array();
		foreach ( $dlms as $dlm ) {
			$structured_dlm[ $dlm->ID ] = array(
				'title'     => $dlm->post_title,
				'downloads' => rand( 1, 99999 ),
			);
		}
// Something else in the main i do not want in the test branch.
		$table = "{$wpdb->dlm_reports}";

		$sql_check  = "SELECT * FROM $table  WHERE date = %s;";
		$sql_insert = "INSERT INTO $table (date,download_ids) VALUES ( %s , %s );";
		$sql_update = "UPDATE $table dlm SET dlm.download_ids = %s WHERE dlm.date = %s";
		$check      = $wpdb->get_results( $wpdb->prepare( $sql_check, $date ), ARRAY_A );

		if ( null !== $check && ! empty( $check ) ) {
			$downloads = json_decode( $check[0]['download_ids'], ARRAY_A );

			foreach ( $structured_dlm as $item_key => $item ) {
				if ( isset( $downloads[ $item_key ] ) ) {
					$downloads[ $item_key ]['downloads'] = $downloads[ $item_key ]['downloads'] + $item['downloads'];
					unset( $downloads[ $item_key ]['date'] );
				} else {
					$downloads[ $item_key ] = array(
						'downloads' => $item['downloads'],
						'title'     => $item['title'],
					);
				}
			}

			$wpdb->query( $wpdb->prepare( $sql_update, wp_json_encode( $downloads ), $date ) );
		} else {
			foreach ( $structured_dlm as $item_key => $item ) {
				$downloads[ $item_key ] = array(
					'downloads' => $item['downloads'],
					'title'     => $item['title'],
				);
			}

			$wpdb->query( $wpdb->prepare( $sql_insert, $date, wp_json_encode( $downloads ) ) );
		}
	}
}

function add_dlm_dummy_logs() {
	global $wpdb;

	$dlms  = get_posts(
		array(
			'post_type'      => 'dlm_download',
			'posts_per_page' => - 1,
		)
	);
	$dates = displayDates( '2019-05-10', '2021-12-06' );

	foreach ( $dates as $date ) {
		$structured_dlm = array();
		foreach ( $dlms as $dlm ) {
			$structured_dlm[] = $dlm->ID;
		}

		$table = "{$wpdb->download_log}";

		foreach ( $dlms as $dlm ) {
			$id     = $structured_dlm[ ARRAY_RAND( $structured_dlm ) ];
			$agents = array( 'Edge', 'Chrome', 'Firefox', 'Safari' );
			$status = array( 'completed', 'redirected', 'failed' );
			$wpdb->insert(
				$table,
				array(
					'user_id'         => wp_rand( 0, 500 ),
					'user_agent'      => $agents[ array_rand( $agents ) ],
					'download_id'     => $id,
					'version_id'      => $id,
					'download_date'   => $date,
					'download_status' => $status[ array_rand( $status ) ],
					'user_ip'         => '127.0.0.1',

				)
			);
		}
	}
}

function displayDates( $date1, $date2, $format = 'Y-m-d' ) {
	$dates   = array();
	$current = strtotime( $date1 );
	$date2   = strtotime( $date2 );
	$stepVal = '+1 day';
	while ( $current <= $date2 ) {
		$dates[] = date( $format, $current );
		$current = strtotime( $stepVal, $current );
	}

	return $dates;
}

function domos_add_custom_posts_to_db() {
	// Add custom posts to the database
	for ( $i = 0; $i < 30000; $i ++ ) {
		$title = 'Download ' . wp_rand( 0, 500 );
		$post  = array(
			'post_title'  => $title,
			'post_type'   => 'dlm_download',
			'post_status' => 'publish',
		);
		$id    = wp_insert_post( $post );

		// Update the manual download count
		update_post_meta( $id, '_download_count', wp_rand( 0, 500 ) );

		// Insert the version
		$version    = array(
			'post_title'  => $title,
			'post_type'   => 'dlm_download_version',
			'post_status' => 'publish',
			'post_parent' => $id,
		);
		$version_id = wp_insert_post( $version );
		// Update version metas
		update_post_meta( $version_id, '_version', wp_rand( 0, 30 ) );
		update_post_meta( $version_id, '_files', '["/license.txt"]' );
		update_post_meta( $version_id, '_filesize', '19915' );
		update_post_meta( $version_id, '_download_count', wp_rand( 0, 500 ) );

		// Insert logs
		global $wpdb;
		$table  = "{$wpdb->download_log}";
		$dates  = displayDates( '2019-05-10', '2021-12-06' );
		$agents = array( 'Edge', 'Chrome', 'Firefox', 'Safari' );
		$status = array( 'completed', 'redirected', 'failed' );
		for ( $j = 0; $j < 100; $j ++ ) {
			$wpdb->insert(
				$table,
				array(
					'user_id'         => wp_rand( 0, 500 ),
					'user_agent'      => $agents[ array_rand( $agents ) ],
					'download_id'     => $id,
					'version_id'      => $version_id,
					'download_date'   => $dates[ array_rand( $dates ) ],
					'download_status' => $status[ array_rand( $status ) ],
					'user_ip'         => '127.0.0.1',

				)
			);
		}
	}
}

add_action( 'wp_footer', 'domos_add_custom_posts_to_db' );