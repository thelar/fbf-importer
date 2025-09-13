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
			let data = {
				prod: 23984723,
				color: 'red',
			};
			$.ajax({
				// eslint-disable-next-line no-undef
				url: '/test_importer/start',
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

        $('#fbf-importer-reset-batch').bind('click', function(){
            console.log('reset batch');
            console.log($(this));
            let data = {
                action: 'fbf_importer_reset_batch',
                log_id: $(this).attr('data-log-id'),
                batch: $(this).attr('data-batch'),
                ajax_nonce: fbf_importer_admin.ajax_nonce,
            };
            $.ajax({
                // eslint-disable-next-line no-undef
                url: fbf_importer_admin.ajax_url,
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function (response) {
                    if (response.status==='success') {
                        console.log(response);
                    }
                },
            })
            return false;
        })
	});

})( jQuery );
