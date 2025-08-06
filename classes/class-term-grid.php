<?php

namespace Mai\LoadMore;

use WP_Term_Query;

defined( 'ABSPATH' ) || exit;

/**
 * The Term Grid class.
 *
 * @since 0.2.0
 */
class TermGrid extends LoadMore {
	/**
	 * Check if this class should run.
	 *
	 * @since 0.2.0
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

		return 'term' === $type && 'block' === $context && isset( $classes['mai-grid-load-more'] );
	}

	/**
	 * Get load more data for term grid context.
	 *
	 * @since 0.2.0
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
		$taxonomy     = $query->query_vars['taxonomy'][0] ?? '';
		$found_terms  = wp_count_terms($taxonomy, $query->query_vars);
		$number       = $query->query_vars['number'] ?? 10;
		$current_page = max( $query->query_vars['paged'] ?? 1, 1 );
		$total_pages  = $number > 0 ? ceil( $found_terms / $number ) : 0;

		$data = [
			'type'              => $args['params']['args']['type'] ?? 'term',
			'template'          => $template_args,
			'query'             => $query->query_vars,
			'page'              => $current_page,
			'total_entries'     => $found_terms,
			'total_pages'       => $total_pages,
			'no_entries_text'   => $this->args['no_entries_text'],
			'no_entries_class'  => $this->args['no_entries_class'],
		];

		return $data;
	}
}