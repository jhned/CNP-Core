<?php

class CNP_Admin_General {

	public static function add_favicon() {
		$subdomain = cnp_get_subdomain();
		$prefix = '';
		$suffix = '';
		$field_name = 'favicon_url';

		if ( $subdomain == 'dev' ) {
			$suffix = '-dev';
			$field_name = 'dev_favicon_url';
		}
		if ( is_admin() ) {
			$prefix = 'admin-';
			$field_name = 'admin_favicon_url';
		}
		if ( is_admin() && $subdomain == 'dev' ) {
			$field_name = 'dev_admin_favicon_url';
		}

		$favicon_path = '/img/icons/'. $prefix .'favicon'. $suffix .'.ico';

		if ( function_exists('get_field') ) {
			$acf_favicon_path = get_field($field_name, 'option');

			if ( !empty($acf_favicon_path) ) {
				$favicon_path = $acf_favicon_path;
			}
		}
		echo '<link rel="shortcut icon" href="'. get_stylesheet_directory_uri() . $favicon_path .'" />';
	}

	public static function admin_footer_text() {
		ob_start(); ?>
			Created by <a href="http://clarknikdelpowell.com/">Clark Nikdel Powell</a>. Powered by <a href="http://wordpress.org">WordPress</a>.
		<?php return ob_get_clean();
	}

	public static function hide_upgrade_notices() {
		if (!current_user_can('update_core'))
			add_filter('pre_site_transient_update_core', function($a) { return null; });
	}

	public static function enqueue_scripts() {
		global $wp_scripts;
		$ui = $wp_scripts->query('jquery-ui-core');

		wp_enqueue_media();

		wp_enqueue_style(
			'cnp_jquery-ui-smoothness',
			"//ajax.googleapis.com/ajax/libs/jqueryui/{$ui->ver}/themes/smoothness/jquery-ui.css",
			false,
			null
		);
		wp_enqueue_style('cnp_admin_styles', CNP_URL.'resources/css/admin.css');

		wp_enqueue_script('cnp_admin_scripts', CNP_URL.'resources/js/admin.js', array(
			'jquery',
			'jquery-ui-datepicker',
			'jquery-ui-slider'
		));
	}

	public static function initialize() {
		add_action('login_head', array(__CLASS__, 'add_favicon'));
		add_action('admin_head', array(__CLASS__, 'add_favicon'));
		add_action('wp_head',    array(__CLASS__, 'add_favicon'));
		add_filter('admin_footer_text', array(__CLASS__, 'admin_footer_text'), 999);
		add_action('after_setup_theme', array(__CLASS__, 'hide_upgrade_notices'));
		add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
	}
}