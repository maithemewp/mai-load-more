<?php

/**
 * Plugin Name:       Mai Load More
 * Plugin URI:        https://bizbudding.com
 * Description:       Load more posts functionality for Mai Theme.
 * Version:           0.3.0
 * Requires PHP:      8.2
 * Requires at least: 6.8
 *
 * Author:            BizBudding
 * Author URI:        https://bizbudding.com
 */

namespace Mai\LoadMore;

defined( 'ABSPATH' ) || exit;

// Must be at the top of the file.
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

// Classes.
require_once __DIR__ . '/classes/class-load-more.php';
require_once __DIR__ . '/classes/class-ajax.php';
require_once __DIR__ . '/classes/class-archive.php';
require_once __DIR__ . '/classes/class-post-grid.php';
require_once __DIR__ . '/classes/class-term-grid.php';

// Initialize the classes.
new Ajax();

add_action( 'template_redirect', __NAMESPACE__ . '\init' );
/**
 * Initialize the load more functionality.
 *
 * @since 0.1.0
 *
 * @return void
 */
function init() {
	// Bail if not loading.
	if ( ! class_exists( 'Mai_Engine' ) ) {
		return;
	}

	new Archive();
	new PostGrid();
	new TermGrid();
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\updater' );
/**
 * Setup the updater.
 *
 * composer require yahnis-elsts/plugin-update-checker
 *
 * @since 0.1.0
 *
 * @uses https://github.com/YahnisElsts/plugin-update-checker/
 *
 * @return void
 */
function updater() {
	// Bail if plugin updater is not loaded.
	if ( ! class_exists( 'YahnisElsts\PluginUpdateChecker\v5\PucFactory' ) ) {
		return;
	}

	// Setup the updater.
	$updater = PucFactory::buildUpdateChecker( 'https://github.com/maithemewp/mai-load-more/', __FILE__, 'mai-load-more' );

	// Maybe set github api token.
	if ( defined( 'MAI_GITHUB_API_TOKEN' ) ) {
		$updater->setAuthentication( MAI_GITHUB_API_TOKEN );
	}

	// Add icons for Dashboard > Updates screen.
	if ( function_exists( 'mai_get_updater_icons' ) && $icons = \mai_get_updater_icons() ) {
		$updater->addResultFilter(
			function ( $info ) use ( $icons ) {
				$info->icons = $icons;
				return $info;
			}
		);
	}
}