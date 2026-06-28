<?php

namespace Mai\LoadMore;

defined( 'ABSPATH' ) || exit;

/**
 * The Post Grid class.
 *
 * @since 0.1.0
 */
class PostGrid extends LoadMore {
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
		$type    = $args['params']['args']['type'] ?? '';
		$context = $args['params']['args']['context'] ?? '';
		$classes = explode( ' ', $args['params']['args']['class'] ?? '' );
		$classes = array_filter( $classes );
		$classes = array_flip( $classes );

		return 'post' === $type && 'block' === $context && isset( $classes['mai-grid-load-more'] );
	}

	/**
	 * Register hooks. Runs once at construction (template_redirect), before any grid builds its
	 * query, so the found-rows filter is in place in time.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function run() {
		parent::run();
		add_filter( 'mai_post_grid_query_args', [ $this, 'restore_found_rows' ], 10, 2 );
	}

	/**
	 * Restore found-rows counting for load-more post grids.
	 *
	 * Mai Engine defaults grids to `no_found_rows` for speed, but load-more needs an accurate
	 * `found_posts` / `max_num_pages` on the initial render to know how many pages exist. Opt the
	 * grid back into the count when it carries the `mai-grid-load-more` class (the same marker
	 * `should_run()` keys on).
	 *
	 * @since TBD
	 *
	 * @param array $query_args The grid query args.
	 * @param array $args       The Mai_Grid block args.
	 *
	 * @return array
	 */
	public function restore_found_rows( $query_args, $args ) {
		$classes = array_filter( explode( ' ', $args['class'] ?? '' ) );

		if ( in_array( 'mai-grid-load-more', $classes, true ) ) {
			$query_args['no_found_rows'] = false;
		}

		return $query_args;
	}

	/**
	 * Get load more data for post grid context.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args The args from genesis_{*} filters.
	 *
	 * @return array Data array with required keys.
	 */
	public function get_data( $args ) {
		$template_args = $args['params']['args'] ?? [];
		$query         = $args['params']['query'] ?? null;

		// Bail if missing args or query.
		if ( ! ( $template_args && $query ) ) {
			return [];
		}

		// Get pagination info from the query object.
		$total_pages  = $query->max_num_pages;
		$found_posts  = $query->found_posts;
		$current_page = max( $query->get( 'paged' ), 1 );

		$data = [
			'type'              => $args['params']['args']['type'] ?? 'post',
			'template'          => $template_args,
			'query'             => $query->query_vars,
			'page'              => $current_page,
			'total_entries'     => $found_posts,
			'total_pages'       => $total_pages,
			'no_entries_text'   => $this->args['no_entries_text'],
			'no_entries_class'  => $this->args['no_entries_class'],
		];

		return $data;
	}
}