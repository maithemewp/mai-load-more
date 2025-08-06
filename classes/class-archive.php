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

		$data = [
			'type'             => $args['params']['args']['type'] ?? 'post',
			'template'         => \mai_get_template_args(),
			'query'            => $wp_query->query_vars,
			'page'             => max( $wp_query->query_vars['paged'], 1 ),
			'total_entries'    => $wp_query->found_posts,
			'total_pages'      => $wp_query->max_num_pages,
			'no_entries_text'  => $this->args['no_entries_text'],
			'no_entries_class' => $this->args['no_entries_class'],
		];

		return $data;
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
