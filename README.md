# Mai Load More

A WordPress plugin for Mai Theme v2 (requires Mai Engine plugin) that adds "Load More" functionality to content archives and Mai Post Grid blocks. This plugin replaces traditional pagination with an AJAX-powered load more button, providing a smoother user experience.

## Features

- **Archive Support**: Replaces pagination on configured archive pages with a load more button
- **Mai Post Grid Support**: Adds load more functionality to Mai Post Grid blocks using the `mai-grid-load-more` class
- **AJAX Loading**: Seamless content loading without page refreshes
- **Loading States**: Visual feedback during content loading with spinner animation
- **Responsive Design**: Automatically loads more posts with the same layout/configuration as the current posts
- **Performance Optimized**: Efficient query handling and minimal server load

## Requirements

- WordPress 6.8 or higher
- PHP 8.2 or higher
- Mai Theme (Mai Engine)

## Installation

1. Upload the `mai-load-more` folder to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the plugin to work with your archives and Mai Post Grid blocks (see Usage section below)

## Usage

### Archive Pages

The plugin can replace pagination on archive pages with a load more button. You need to configure which archives should use the load more functionality by adding a filter to your theme's `functions.php`:

```php
/**
 * Determine where the load more functionality should run.
 *
 * @return bool
 */
add_filter( 'mai_load_more_archive_should_run', function() {
	return is_home() || is_category();
});
```

This example enables load more on the home page and category archives. You can modify the conditions to include other archive types like `is_tag()`, `is_author()`, `is_date()`, etc.

### Mai Post Grid Blocks

To enable load more functionality on a Mai Post Grid block, add the `mai-grid-load-more` class to the block's CSS Classes field in the block editor:

1. Edit a page/post with a Mai Post Grid block
2. Select the Mai Post Grid block
3. In the block settings sidebar, find the "CSS Classes" field under the Advanced section
4. Add `mai-grid-load-more` to the CSS Classes field
5. Update the page/post

The load more button will appear below the grid (on the front end) and automatically load more posts with the same layout and configuration as the current posts.

### Customization

You can customize the load more button appearance and behavior using WordPress filters:

```php
/**
 * Customize load more button arguments
 *
 * @param array The load more args.
 *
 * @return array
 */
add_filter( 'mai_load_more_args', function( $args ) {
	$args['button_text']         = 'Load More Posts';
	$args['button_text_loading'] = 'Loading...';
	$args['no_posts_text']       = 'No more posts available';
	$args['button_class']        = 'button button-secondary';

	return $args;
});
```

### Available Arguments

| Argument | Default | Description |
|----------|---------|-------------|
| `button_wrap_class` | `'has-xl-margin-top has-text-align-center'` | CSS classes for button wrapper |
| `button_class` | `'button'` | CSS classes for the load more button |
| `button_text` | `'Load More'` | Text displayed on the button |
| `button_text_loading` | SVG spinner | Content shown during loading |
| `no_posts_text` | `'No more posts to show'` | Text when no more posts are available |
| `no_posts_class` | `'mai-no-posts'` | CSS class for no posts message |

## Development

### Building Assets

The plugin uses WordPress Scripts for asset building:

```bash
# Install dependencies
npm install

# Build for production
npm run build

# Development mode with watch
npm start
```

### File Structure

```
mai-load-more/
├── classes/
│   ├── class-ajax.php          # AJAX request handling
│   ├── class-archive.php       # Archive page functionality
│   ├── class-load-more.php     # Base load more class
│   └── class-post-grid.php     # Post grid functionality
├── src/
│   └── mai-load-more.js        # Frontend JavaScript
├── build/                       # Compiled assets
├── mai-load-more.php           # Main plugin file
└── package.json                # Build configuration
```

## Support

For support and feature requests, please visit [BizBudding](https://bizbudding.com).

## License

This plugin is developed by BizBudding and is part of the Mai Theme ecosystem.

## Credits

Developed by [BizBudding](https://bizbudding.com) for the Mai Theme.
