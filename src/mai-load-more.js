/**
 * Mai Load More JavaScript.
 * Handles load more functionality for archives and grids.
 */
(function() {
	'use strict';

	// Get the load more entries.
	const entries = document.querySelectorAll('.entries[data-load-more]');

	// Loop through the entries.
	entries.forEach(container => {
		// Get the button.
		const button = container.querySelector('.mai-load-more');

		// Add click event listener.
		button?.addEventListener('click', function(e) {
			// Get the button text.
			const buttonText = this.textContent;
			const styleTag   = `<style id="mai-load-more-style">@keyframes mailoadmorespin { to { transform: rotate(360deg); } }</style>`;

			// Modify the button.
			this.insertAdjacentHTML('beforeBegin', styleTag);
			this.innerHTML = `<span class="mai-load-more__text" style="opacity:0;">${buttonText}</span><span class="mai-load-more__loading" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);line-height:0;">${maiLoadMore.args.button_text_loading}</span>`;
			this.disabled  = true;

			// Get the data.
			const template = JSON.parse(atob(decodeURIComponent(container.dataset.template)));
			const query    = JSON.parse(atob(decodeURIComponent(container.dataset.query)));
			const nextPage = parseInt(container.dataset.page) + 1;

			// Set up query parameters.
			query.paged = nextPage;

			// Build data for AJAX request.
			const data = new FormData();
			data.append('action', 'mai_load_more_posts');
			data.append('query_args', JSON.stringify(query));
			data.append('template_args', JSON.stringify(template));
			data.append('total_posts', container.dataset.totalPosts);
			data.append('nonce', maiLoadMore.nonce);

			// Make AJAX request.
			fetch(maiLoadMore.ajaxUrl, {
				method: 'POST',
				body: data,
			})
			.then(res => {
				if (!res.ok) {
					throw new Error('Network response was not ok');
				}
				return res.json();
			})
			.then(data => {
				// Check if we have successful response with HTML content.
				if (data.success && data.data?.html?.trim()) {
					// Insert new HTML after the current entries.
					container.querySelector('.entries-wrap')?.insertAdjacentHTML('beforeEnd', data.data.html);

					// Update page number.
					container.dataset.page = nextPage;

					// Check if there are more posts to load.
					if (!data.data.has_more) {
						// Show "no more posts" message if configured.
						if (container.dataset.noposts && entries) {
							container.querySelector('.entries-wrap')?.insertAdjacentHTML('afterEnd', `<p class="${container.dataset.nopostsClass}">${container.dataset.noposts}</p>`);
						}
						// Remove the button since there are no more posts.
						this.remove();
					} else {
						// Re-enable button and restore original text.
						this.disabled    = false;
						this.textContent = buttonText;
					}
				} else {
					// Remove button if no valid response.
					this.remove();
				}

				// Remove the style tag.
				document.getElementById('mai-load-more-style')?.remove();
			})
			.catch(error => {
				console.error('Error:', error);
				// Re-enable button and restore original text on error.
				this.disabled    = false;
				this.textContent = buttonText;
			});
		});
	});
})();