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
		$context = $args['params']['args']['context'] ?? '';
		$classes = explode( ' ', $args['params']['args']['class'] ?? '' );
		$classes = array_filter( $classes );
		$classes = array_flip( $classes );

		return 'block' === $context && isset( $classes['mai-grid-load-more'] );
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
		$max_num_pages = $query->max_num_pages;
		$found_posts   = $query->found_posts;
		$current_page  = max( $query->get( 'paged' ), 1 );

		return [
			'template'        => $template_args,
			'query'           => $query->query_vars,
			'page'            => $current_page,
			'total_posts'     => $found_posts,
			'max_num_pages'   => $max_num_pages,
			'no_posts_text'   => $this->args['no_posts_text'],
			'no_posts_class'  => $this->args['no_posts_class'],
		];
	}
}