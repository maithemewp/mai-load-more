<?php

/**
 * Plugin Name:       Mai Load More
 * Plugin URI:        https://bizbudding.com
 * Description:       Load more posts functionality for Mai Theme.
 * Version:           0.1.0
 * Requires PHP:      8.2
 * Requires at least: 6.8
 *
 * Author:            BizBudding
 * Author URI:        https://bizbudding.com
 */

namespace Mai\LoadMore;

defined( 'ABSPATH' ) || exit;

// Classes.
require_once __DIR__ . '/classes/class-load-more.php';
require_once __DIR__ . '/classes/class-ajax.php';
require_once __DIR__ . '/classes/class-archive.php';
require_once __DIR__ . '/classes/class-post-grid.php';

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
}
