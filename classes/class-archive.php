<?php

namespace Mai\LoadMore;

defined( 'ABSPATH' ) || exit;

/**
 * The Archive class.
 *
 * @since 0.1.0
 */
class Archive extends LoadMore {
	/**
	 * Check if this class should run.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args The args from genesis_{*} filters.
	 *
	 * @return bool
	 */
	public function should_run( $args ) {
		if ( 'archive' !== ( $args['params']['args']['context'] ?? '' ) ) {
			return false;
		}

		// Allow filtering if the load more functionality should run.
		$should_run = apply_filters( 'mai_load_more_archive_should_run', false, $args );

		return (bool) $should_run;
	}

	/**
	 * Get load more data for archive context.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args The args from genesis_{*} filters.
	 *
	 * @return array Data array with required keys.
	 */
	public function get_data( $args ) {
		global $wp_query;

		// Build a curated query array from the original URL-derived query params,
		// supplemented with essential vars that may have been set by pre_get_posts.
		$query = $this->get_archive_query_args( $wp_query );

		$data = [
			'type'             => $args['params']['args']['type'] ?? 'post',
			'template'         => \mai_get_template_args(),
			'query'            => $query,
			'page'             => max( $wp_query->query_vars['paged'], 1 ),
			'total_entries'    => $wp_query->found_posts,
			'total_pages'      => $wp_query->max_num_pages,
			'no_entries_text'  => $this->args['no_entries_text'],
			'no_entries_class' => $this->args['no_entries_class'],
		];

		return $data;
	}

	/**
	 * Filter query vars to only meaningful, non-default values.
	 *
	 * The full query_vars array has 100+ keys with empty defaults.
	 * Filtering to non-empty values produces a clean set for AJAX reuse.
	 *
	 * @since 0.4.1
	 *
	 * @param \WP_Query $wp_query The main query object.
	 *
	 * @return array Filtered query arguments.
	 */
	private function get_archive_query_args( $wp_query ) {
		return array_filter( $wp_query->query_vars, function( $value ) {
			if ( '' === $value || 0 === $value || false === $value ) {
				return false;
			}

			if ( is_array( $value ) && empty( $value ) ) {
				return false;
			}

			return true;
		});
	}

	/**
	 * Run additional hooks.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function hooks() {
		// Remove pagination.
		remove_action( 'genesis_after_endwhile',                 'mai_posts_nav', 9 );
		add_filter( 'genesis_markup_archive-pagination_open',    '__return_empty_string' );
		add_filter( 'genesis_markup_archive-pagination_content', '__return_empty_string' );
		add_filter( 'genesis_markup_archive-pagination_close',   '__return_empty_string' );
	}
}
