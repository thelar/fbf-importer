(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	$(document).ready(function(){
		console.log('fbf-importer-admin.js');

		$('#start-import').bind('click', function(){
			let data = {};
			$.ajax({
				// eslint-disable-next-line no-undef
				url: 'http://staging.4x4tyres.co.uk:3001/start',
				type: 'POST',
				data: data,
				dataType: 'json',
				success: function (response) {
					console.log(response);
				},
			});
		});

		$('#stop-import').bind('click', function(){
			let data = {};
			$.ajax({
				// eslint-disable-next-line no-undef
				url: 'http://4x4tyres.localhost:3000/stop',
				type: 'POST',
				data: data,
				dataType: 'json',
				success: function (response) {
					console.log(response);
				},
			});
		});

		$('#import-state').bind('click', function(){
			let data = {};
			$.ajax({
				// eslint-disable-next-line no-undef
				url: 'http://4x4tyres.localhost:3000/get_state',
				type: 'POST',
				data: data,
				dataType: 'json',
				success: function (response) {
					console.log(response);
				},
			});
		});
	});

})( jQuery );
