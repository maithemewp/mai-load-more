<?php

namespace Mai\LoadMore;

defined( 'ABSPATH' ) || exit;

/**
 * The generic load more class.
 * To be extended for context.
 *
 * @since 0.1.0
 */
class LoadMore {
	/**
	 * Args.
	 *
	 * @since 0.1.0
	 *
	 * @var array
	 */
	public $args;

	/**
	 * Data.
	 *
	 * @since 0.1.0
	 *
	 * @var array
	 */
	public $data;

	/**
	 * Check if this class should run.
	 * This must be overridden in the child class.
	 *
	 * @param array $args The args from genesis_{*} filters.
	 *
	 * @since 0.1.0
	 *
	 * @return bool|null
	 */
	public function should_run( $args ) {
		// This must be overridden in the child class.
		return null;
	}

	/**
	 * Get load more data for the current context.
	 * Must be overridden in child classes.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args The args from genesis_{*} filters.
	 *
	 * @return array Data array with required keys.
	 */
	public function get_data( $args ) {
		// This must be overridden in child classes
		return [];
	}

	/**
	 * Run hooks.
	 * This can be overridden in the child class.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function hooks() {}

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function __construct() {
		// Get args.
		$args = [
			'button_wrap_class'   => 'has-xl-margin-top has-text-align-center',
			'button_class'        => 'button',
			'button_text'         => __( 'Load More', 'mai-engine' ),
			'button_text_loading' => '<svg style="animation:mailoadmorespin 2s linear infinite;" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-loader"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 6l0 -3" /><path d="M16.25 7.75l2.15 -2.15" /><path d="M18 12l3 0" /><path d="M16.25 16.25l2.15 2.15" /><path d="M12 18l0 3" /><path d="M7.75 16.25l-2.15 2.15" /><path d="M6 12l-3 0" /><path d="M7.75 7.75l-2.15 -2.15" /></svg>',
			'no_entries_text'     => __( 'No more entries to show', 'mai-engine' ),
			'no_entries_class'    => 'mai-no-entries has-md-margin-top has-text-align-center',
		];

		$this->args = apply_filters( 'mai_load_more_args', $args );

		// Run hooks.
		$this->run();
	}

	/**
	 * Run hooks.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function run() {
		add_action( 'wp_enqueue_scripts',   [ $this, 'register_script' ] );
		add_filter( 'genesis_attr_entries', [ $this, 'add_attributes' ], 10, 3 );
	}

	/**
	 * Register the script.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_script() {
		static $registered = false;

		// Bail if already registered.
		if ( $registered ) {
			return;
		}

		// Get the asset file.
		$asset_file = include plugin_dir_path( dirname( __FILE__ ) ) . 'build/mai-load-more.asset.php';

		// Register the script.
		wp_register_script(
			'mai-load-more',
			plugins_url( 'build/mai-load-more.js', dirname( __FILE__ ) ),
			$asset_file['dependencies'],
			$asset_file['version'],
			[
				'in_footer' => true,
				'strategy'  => 'defer',
			]
		);

		// Localize the script.
		wp_localize_script( 'mai-load-more', 'maiLoadMore', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'mai_load_more_nonce' ),
			'args'    => $this->args,
		] );

		$registered = true;
	}

	/**
	 * Add attributes.
	 *
	 * @since 0.1.0
	 *
	 * @param array  $attr    Existing attr for entry title.
	 * @param string $context Context where the filter is run.
	 * @param array  $args    Additional arguments passed to the filter.
	 *
	 * @return array
	 */
	public function add_attributes( $attr, $context, $args ) {
		// Bail if not valid.
		if ( ! $this->should_run( $args ) ) {
			return $attr;
		}

		// Run context-specific hooks.
		$this->hooks();

		// Enqueue the script.
		wp_enqueue_script( 'mai-load-more' );

		// Get context-specific data.
		$this->data = $this->get_data( $args );

		// Sanitize the data.
		$this->data = $this->sanitize_data( $this->data );

		// Bail if data validation fails.
		if ( ! $this->validate_data( $this->data ) ) {
			return $attr;
		}

		// Add the data attributes.
		$attr['data-load-more']       = 'true';
		$attr['data-type']            = esc_attr( $this->data['type'] );
		$attr['data-template']        = esc_attr( base64_encode( wp_json_encode( $this->data['template'] ) ) );
		$attr['data-query']           = esc_attr( base64_encode( wp_json_encode( $this->data['query'] ) ) );
		$attr['data-page']            = esc_attr( $this->data['page'] );
		$attr['data-total-entries']   = esc_attr( $this->data['total_entries'] );
		$attr['data-noentries']       = esc_attr( $this->data['no_entries_text'] );
		$attr['data-noentries-class'] = esc_attr( $this->data['no_entries_class'] );

		// Add the button.
		add_filter( 'genesis_markup_entries_close', [ $this, 'add_button' ], 10, 2 );

		return $attr;
	}

	/**
	 * Add the load more button.
	 *
	 * @since 0.1.0
	 *
	 * @param string $content The content.
	 * @param array  $args    The args.
	 *
	 * @return string
	 */
	public function add_button( $content, $args ) {
		// Bail if there is no close tag.
		if ( ! isset( $args['close'] ) || empty( $args['close'] ) ) {
			return $content;
		}

		// Bail if there are no more pages.
		if ( $this->data['total_pages'] <= 1 ) {
			return $content;
		}

		// Output the load more button.
		$button  = '';
		$button .= sprintf( '<p class="%s">', esc_attr( $this->args['button_wrap_class'] ) );
		$button .= sprintf( '<button class="%s">%s</button>', esc_attr( trim( 'mai-load-more ' . $this->args['button_class'] ) ), esc_html( $this->args['button_text'] ) );
		$button .= '</p>';

		// Remove the filter so it doesn't run again.
		remove_filter( 'genesis_markup_entries_close', [ $this, 'add_button' ], 10, 2 );

		return $button . $content;
	}

	/**
	 * Validate that all required data is present.
	 *
	 * @since 0.1.0
	 *
	 * @param array $data The data array to validate.
	 *
	 * @return bool
	 */
	protected function validate_data( $data ) {
		$required_keys = [
			'template',
			'query',
			'page',
			'total_entries',
			'total_pages',
			'no_entries_text',
			'no_entries_class',
		];

		foreach ( $required_keys as $key ) {
			if ( ! isset( $data[ $key ] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Sanitize all data.
	 *
	 * @since 0.2.0
	 *
	 * @param array $data The data to sanitize.
	 *
	 * @return array Sanitized data.
	 */
	protected function sanitize_data( $data ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}

		$sanitized = [];

		foreach ( $data as $key => $value ) {
			switch ( $key ) {
				case 'template':
					$sanitized[ $key ] = $this->sanitize_template_args( $value );
					break;
				case 'query':
					$sanitized[ $key ] = $this->sanitize_query_vars( $value );
					break;
				case 'page':
				case 'total_entries':
				case 'total_pages':
					$sanitized[ $key ] = (int) $value;
					break;
				case 'no_entries_text':
					$sanitized[ $key ] = sanitize_text_field( $value );
					break;
				case 'no_entries_class':
					$classes           = explode( ' ', $value );
					$classes           = array_map( 'sanitize_html_class', $classes );
					$classes           = array_filter( $classes );
					$sanitized[ $key ] = implode( ' ', $classes );
					break;
				default:
					$sanitized[ $key ] = $value;
					break;
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize template arguments.
	 *
	 * @since 0.2.0
	 *
	 * @param array $template_args The template arguments.
	 *
	 * @return array Sanitized template arguments.
	 */
	protected function sanitize_template_args( $template_args ) {
		$sanitized = [];

		foreach ( $template_args as $key => $value ) {
			if ( is_string( $value ) ) {
				$sanitized[ $key ] = sanitize_text_field( $value );
			} elseif ( is_array( $value ) ) {
				$sanitized[ $key ] = $this->sanitize_template_args( $value );
			} else {
				$sanitized[ $key ] = $value;
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize query variables.
	 *
	 * @since 0.2.0
	 *
	 * @param array $query_vars The query variables.
	 *
	 * @return array Sanitized query variables.
	 */
	protected function sanitize_query_vars( $query_vars ) {
		$sanitized = [];

		foreach ( $query_vars as $key => $value ) {
			if ( is_string( $value ) ) {
				$sanitized[ $key ] = sanitize_text_field( $value );
			} elseif ( is_array( $value ) ) {
				$sanitized[ $key ] = $this->sanitize_query_vars( $value );
			} else {
				$sanitized[ $key ] = $value;
			}
		}

		return $sanitized;
	}
}