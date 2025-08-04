<?php

namespace Mai\LoadMore;

use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * The Ajax class.
 *
 * @since 0.1.0
 */
class Ajax {
	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp_ajax_mai_load_more_posts',        [ $this, 'load_more_posts' ] );
		add_action( 'wp_ajax_nopriv_mai_load_more_posts', [ $this, 'load_more_posts' ] );
	}

	/**
	 * Load more posts.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function load_more_posts() {
		// Check nonce for security.
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'mai_load_more_nonce' ) ) {
			wp_die( 'Security check failed' );
		}

		// Get query vars and page.
		$query_args    = isset( $_POST['query_args'] ) ? json_decode( wp_unslash( $_POST['query_args'] ), true ) : [];
		$template_args = isset( $_POST['template_args'] ) ? json_decode( wp_unslash( $_POST['template_args'] ), true ) : [];

		// Bail if no query vars are provided.
		if ( empty( $query_args ) ) {
			wp_die( 'No query vars provided' );
		}

		// Bail if no args are provided.
		if ( empty( $template_args ) ) {
			wp_die( 'No template args provided' );
		}

		// Performance optimization.
		$query_args['no_found_rows']          = true;
		$query_args['update_post_meta_cache'] = false;
		$query_args['update_post_term_cache'] = false;

		// Calculate the correct offset for this page.
		$current_page = $query_args['paged'] ?? 1;
		$base_offset  = $query_args['offset'] ?? 0;

		// If we're not on page 1, add the offset.
		if ( $current_page > 1 ) {
			$posts_per_page       = $query_args['posts_per_page'] ?? get_option( 'posts_per_page' );
			$query_args['offset'] = $base_offset + ( ( $current_page - 1 ) * $posts_per_page );
		}

		// Get posts per page times current page.
		$total_posts_loaded = $current_page * $query_args['posts_per_page'];

		// Loop through the number of posts to load.
		for ( $i = 0; $i < $total_posts_loaded; $i++ ) {
			\mai_get_index( \mai_get_entry_index_context( $template_args['context'] ) );
		}

		// Start output buffering.
		ob_start();

		// Get the query.
		$query = new WP_Query( $query_args );

		// If we have posts, loop through them and output the title.
		if ( $query->have_posts() ) {
			$function = function() { return true; };
			add_filter( 'mai_has_custom_loop', $function );
			\mai_setup_loop();
			// \mai_do_entries_open( $template_args );
			while ( $query->have_posts() ) {
				$query->the_post();
				do_action( 'genesis_before_entry' );
				\mai_do_entry( get_post(), $template_args );
				do_action( 'genesis_after_entry' );
			}
			// \mai_do_entries_close( $template_args );
			remove_filter( 'mai_has_custom_loop', $function );
			wp_reset_postdata();
		}

		// Get the HTML.
		$html = ob_get_clean();

		// Send the response.
		wp_send_json_success( [
			'html'      => $html,
			'has_more'  => $this->has_more_posts( $query ),
		] );
	}

	/**
	 * Detect if there are more posts.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_Query $query The query object.
	 *
	 * @return bool
	 */
	public function has_more_posts( $query ) {
		// Get the original query args to determine the base offset.
		$query_args = isset( $_POST['query_args'] ) ? json_decode( wp_unslash( $_POST['query_args'] ), true ) : [];
		$base_offset = $query_args['offset'] ?? 0;

		// Get the total posts count from the button data.
		$total_posts = isset( $_POST['total_posts'] ) ? (int) $_POST['total_posts'] : 0;

		// Calculate how many posts we've already loaded.
		$current_page = $query->get( 'paged' ) ?? 1;
		$posts_per_page = get_option( 'posts_per_page' );
		$posts_loaded = $current_page * $posts_per_page;

		// Check if there are more posts to load.
		// Since $total_posts is the actual total, we need to account for the offset.
		return ( $posts_loaded + $base_offset ) < $total_posts;
	}
}