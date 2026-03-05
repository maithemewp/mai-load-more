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
	 * Build query args from the main query for AJAX reuse.
	 *
	 * Uses $wp_query->query (the original URL-derived params like author_name,
	 * category_name, s, etc.) as the base, then adds any non-default query_vars
	 * that were set by pre_get_posts or the theme (like posts_per_page, meta_query).
	 *
	 * This avoids sending the full 100+ key query_vars array which includes
	 * both original params AND internally-resolved values (e.g. author_name AND
	 * author), causing redundant/conflicting processing in the AJAX WP_Query.
	 *
	 * @since TBD
	 *
	 * @param \WP_Query $wp_query The main query object.
	 *
	 * @return array Query arguments for AJAX reuse.
	 */
	private function get_archive_query_args( $wp_query ) {
		// Start with the original URL-derived query params.
		// e.g. ['author_name' => 'john'] or ['category_name' => 'news'].
		$query = $wp_query->query;

		// Add any non-default query_vars that aren't already in the URL query.
		// This captures pre_get_posts customizations (posts_per_page, meta_query, etc.)
		// while avoiding redundant resolved values (e.g. author when author_name exists).
		foreach ( $wp_query->query_vars as $key => $value ) {
			// Already in the URL query — skip to avoid redundancy.
			if ( isset( $query[ $key ] ) ) {
				continue;
			}

			// Skip empty defaults (strings, arrays, zero, false).
			if ( '' === $value || 0 === $value || false === $value ) {
				continue;
			}

			if ( is_array( $value ) && empty( $value ) ) {
				continue;
			}

			$query[ $key ] = $value;
		}

		return $query;
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
