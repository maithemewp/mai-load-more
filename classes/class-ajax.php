<?php

namespace Mai\LoadMore;

use WP_Query;
use WP_Term_Query;

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
		add_action( 'wp_ajax_mai_load_more_terms',        [ $this, 'load_more_terms' ] );
		add_action( 'wp_ajax_nopriv_mai_load_more_terms', [ $this, 'load_more_terms' ] );
	}

	/**
	 * Load more posts.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function load_more_posts() {
		// Validate the request.
		$data = $this->validate_request();
		if ( ! $data ) {
			return;
		}

		// Calculate offset for pagination.
		$data['query_args'] = $this->calculate_post_offset( $data['query_args'] );

		// Get the entries.
		$html = $this->get_post_entries( $data['query_args'], $data['template_args'] );

		// Send the response.
		$this->send_post_response( $html, $data['query_args'] );
	}

	/**
	 * Load more terms.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function load_more_terms() {
		// Validate the request.
		$data = $this->validate_request();
		if ( ! $data ) {
			return;
		}

		// Calculate offset for pagination.
		$data['query_args'] = $this->calculate_term_offset( $data['query_args'] );

		// Get the entries.
		$html = $this->get_term_entries( $data['query_args'], $data['template_args'] );

		// Send the response.
		$this->send_term_response( $html, $data['query_args'] );
	}

	/**
	 * Validate the AJAX request.
	 *
	 * @since 0.2.0
	 *
	 * @return array|false Array with query_args and template_args, or false on failure.
	 */
	private function validate_request() {
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

		return [
			'query_args'    => $query_args,
			'template_args' => $template_args,
		];
	}

	/**
	 * Calculate offset for post pagination.
	 *
	 * @since 0.2.0
	 *
	 * @param array $query_args The query arguments.
	 *
	 * @return array Modified query arguments with offset.
	 */
	private function calculate_post_offset( $query_args ) {
		$current_page = $query_args['paged'] ?? 1;
		$base_offset  = $query_args['offset'] ?? 0;

		// If we're not on page 1, add the offset.
		if ( $current_page > 1 ) {
			$posts_per_page = $query_args['posts_per_page'] ?? get_option( 'posts_per_page' );
			$query_args['offset'] = $base_offset + ( ( $current_page - 1 ) * $posts_per_page );
		}

		return $query_args;
	}

	/**
	 * Calculate offset for term pagination.
	 *
	 * @since 0.2.0
	 *
	 * @param array $query_args The query arguments.
	 *
	 * @return array Modified query arguments with offset.
	 */
	private function calculate_term_offset( $query_args ) {
		$current_page = $query_args['paged'] ?? 1;
		$base_offset  = $query_args['offset'] ?? 0;

		// If we're not on page 1, add the offset.
		if ( $current_page > 1 ) {
			$number = $query_args['number'] ?? 12;
			$query_args['offset'] = $base_offset + ( ( $current_page - 1 ) * $number );
		}

		return $query_args;
	}

	/**
	 * Get post entries.
	 *
	 * @since 0.2.0
	 *
	 * @param array $query_args    The query arguments.
	 * @param array $template_args The template arguments.
	 *
	 * @return string The generated HTML.
	 */
	private function get_post_entries( $query_args, $template_args ) {
		// Set performance optimizations.
		$query_args['no_found_rows']          = true;
		$query_args['update_post_meta_cache'] = false;
		$query_args['update_post_term_cache'] = false;

		// Reset entry index.
		\mai_get_index( \mai_get_entry_index_context( $template_args['context'] ), true );

		// Start output buffering.
		ob_start();

		// Get posts per page times current page.
		$posts_per_page     = $query_args['posts_per_page'] ?? get_option( 'posts_per_page' );
		$total_posts_loaded = $query_args['paged'] * $posts_per_page;

		// Loop through the number of posts to load.
		for ( $i = 0; $i < $total_posts_loaded; $i++ ) {
			\mai_get_index( \mai_get_entry_index_context( $template_args['context'] ) );
		}

		// Get the query.
		$query = new WP_Query( $query_args );

		// If we have posts, loop through them and output the title.
		if ( $query->have_posts() ) {
			$function = function() { return true; };
			add_filter( 'mai_has_custom_loop', $function );
			\mai_setup_loop();
			while ( $query->have_posts() ) {
				$query->the_post();
				do_action( 'genesis_before_entry' );
				\mai_do_entry( get_post(), $template_args );
				do_action( 'genesis_after_entry' );
			}
			remove_filter( 'mai_has_custom_loop', $function );
			wp_reset_postdata();
		}

		return ob_get_clean();
	}

	/**
	 * Get term entries.
	 *
	 * @since 0.2.0
	 *
	 * @param array $query_args    The query arguments.
	 * @param array $template_args The template arguments.
	 *
	 * @return string The generated HTML.
	 */
	private function get_term_entries( $query_args, $template_args ) {
		// Reset entry index.
		\mai_get_index( \mai_get_entry_index_context( $template_args['context'] ), true );

		// Start output buffering.
		ob_start();

		// Get number of terms per page times current page.
		$number             = $query_args['number'] ?? 12;
		$total_terms_loaded = $query_args['paged'] * $number;

		// Loop through the number of terms to load.
		for ( $i = 0; $i < $total_terms_loaded; $i++ ) {
			\mai_get_index( \mai_get_entry_index_context( $template_args['context'] ) );
		}

		// Get the query.
		$query = new WP_Term_Query( $query_args );

		// If we have terms, loop through them and output.
		if ( ! empty( $query->terms ) ) {
			foreach ( $query->terms as $term ) {
				do_action( 'genesis_before_entry' );
				\mai_do_entry( $term, $template_args );
				do_action( 'genesis_after_entry' );
			}
		}

		return ob_get_clean();
	}

	/**
	 * Send the post AJAX response.
	 *
	 * @since 0.2.0
	 *
	 * @param string $html       The generated HTML.
	 * @param array  $query_args The query arguments.
	 *
	 * @return void
	 */
	private function send_post_response( $html, $query_args ) {
		$has_more = $this->has_more_posts( $query_args );

		wp_send_json_success( [
			'html'      => $html,
			'has_more'  => $has_more,
		] );
	}

	/**
	 * Send the term AJAX response.
	 *
	 * @since 0.2.0
	 *
	 * @param string $html       The generated HTML.
	 * @param array  $query_args The query arguments.
	 *
	 * @return void
	 */
	private function send_term_response( $html, $query_args ) {
		$has_more = $this->has_more_terms( $query_args );

		wp_send_json_success( [
			'html'      => $html,
			'has_more'  => $has_more,
		] );
	}

	/**
	 * Detect if there are more posts.
	 *
	 * @since 0.1.0
	 *
	 * @param array $query_args The query arguments.
	 *
	 * @return bool
	 */
	private function has_more_posts( $query_args ) {
		$base_offset    = $query_args['offset'] ?? 0;
		$total_entries  = isset( $_POST['total_entries'] ) ? (int) $_POST['total_entries'] : 0;
		$current_page   = $query_args['paged'] ?? 1;
		$posts_per_page = get_option( 'posts_per_page' );
		$posts_loaded   = $current_page * $posts_per_page;

		return ( $posts_loaded + $base_offset ) < $total_entries;
	}

	/**
	 * Detect if there are more terms.
	 *
	 * @since 0.2.0
	 *
	 * @param array $query_args The query arguments.
	 *
	 * @return bool
	 */
	private function has_more_terms( $query_args ) {
		$base_offset   = $query_args['offset'] ?? 0;
		$total_entries = isset( $_POST['total_entries'] ) ? (int) $_POST['total_entries'] : 0;
		$current_page  = $query_args['paged'] ?? 1;
		$number        = $query_args['number'] ?? 12;
		$terms_loaded  = $current_page * $number;

		return ( $terms_loaded + $base_offset ) < $total_entries;
	}
}