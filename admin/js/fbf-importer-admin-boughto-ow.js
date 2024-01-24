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

    $(function() {
        let $start_button = $('#boughto-ow-start');
        let starting = false;
        check_boughto_ow_status();
        setInterval(check_boughto_ow_status, 5000);

        $start_button.bind('click', function(){
            $start_button.prop('disabled', true);
            starting = true;
            console.log('mts ow start');
            let data = {
                action: 'fbf_importer_boughto_ow_start',
                ajax_nonce: fbf_importer_admin_boughto_ow.ajax_nonce,
            }
            $.ajax({
                // eslint-disable-next-line no-undef
                url: fbf_importer_admin_boughto_ow.ajax_url,
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function (response) {
                    starting = false;
                    if(response.status==='success'){
                        $('#boughto-ow-status').text(response.option.status);
                    }
                },
            });
            return false;
        });

        function check_boughto_ow_status(){
            let $status = $('#boughto-ow-status');
            let data = {
                action: 'fbf_importer_boughto_ow_check_status',
                ajax_nonce: fbf_importer_admin_boughto_ow.ajax_nonce,
            };
            $.ajax({
                // eslint-disable-next-line no-undef
                url: fbf_importer_admin_boughto_ow.ajax_url,
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function (response) {
                    if(!starting){
                        console.log(response.status);

                        if(response.status==='STOPPED'){
                            $start_button.prop('disabled', false);
                            $status.text(response.status);
                        }else{
                            $start_button.prop('disabled', true);
                            if(response.status==='RUNNING'){
                                $status.text(response.status + ' - ' + response.stage);
                            }else{
                                $status.text(response.status);
                            }
                        }
                    }
                },
            });
        }
    });
})( jQuery );
