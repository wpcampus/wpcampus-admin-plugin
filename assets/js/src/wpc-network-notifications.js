(function($) {
	'use strict';

	$(document).ready(function() {

		// Make sure the template exists.
		var template_id = 'wpc-notification-template';
		if ( ! $( '#' + template_id ).length ) {
			return;
		}

		// Process the notifications.
		$.get( wpc_net_notifications.main_url + 'wp-json/wpcampus/data/notifications?limit=1').done(function(data) {

			/*
			 * Get the template HTML.
			 *
			 * It is up to each theme to
			 * provide this template.
			 */
			var template = $( '#' + template_id ).html();
			Mustache.parse( template );

			// Render the template.
			var rendered = Mustache.render( template, data );

			// Add the result to the page.
			$( '#wpc-notifications' ).html( rendered ).fadeIn();

		});
	});
})(jQuery);