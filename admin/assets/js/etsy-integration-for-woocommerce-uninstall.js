jQuery( document ).ready(
	function($) {

		var ajaxNonce = ced_etsy_admin_obj.ajax_nonce;
		var ajaxurl   = ced_etsy_admin_obj.ajax_url;
		$( '.deactivate a' ).each(
			function(i, ele) {
				if ($( ele ).attr( 'href' ).indexOf( 'woocommerce-etsy-integration' ) > -1) {
					$( '#ced-etsy-feedback-modal' ).find( 'a' ).attr( 'href', $( ele ).attr( 'href' ) );

					$( ele ).on(
						'click',
						function(e) {
							e.preventDefault();
							if ( ! $( '#ced-etsy-feedback-modal' ).length) {
								window.location.href = $( ele ).attr( 'href' );
								return;
							}

							$( '#ced-etsy-feedback-response' ).html( '' );
							$( '#ced-etsy-feedback-modal' ).css( 'display', 'block' );
						}
					);

					$( '#ced-etsy-feedback-modal .ced-etsy-close' ).on(
						'click',
						function() {
							$( '#ced-etsy-feedback-modal' ).css( 'display', 'none' );
						}
					);

					$( 'input[name="ced-etsy-feedback"]' ).on(
						'change',
						function(e) {
							if ($( this ).val() == 4) {
								$( '#ced-etsy-feedback-other' ).show();
							} else {
								$( '#ced-etsy-feedback-other' ).hide();
							}
						}
					);

					$( '#ced-etsy-submit-feedback-button' ).on(
						'click',
						function(e) {
							e.preventDefault();

							$( '#ced-etsy-feedback-response' ).html( '' );

							if ( ! $( 'input[name="ced-etsy-feedback"]:checked' ).length) {
								$( '#ced-etsy-feedback-response' ).html( '<div style="color:#cc0033;font-weight:800">Please select your feedback.</div>' );
							} else {
								$( this ).val( 'Loading...' );
								$.post(
									ajaxurl,
									{
										// action: 'ced_etsy_submit_feedback',
										feedback: $( 'input[name="ced-etsy-feedback"]:checked' ).val(),
										others: $( '#ced-etsy-feedback-other' ).val(),
										ajax_nonce: ajaxNonce,
									},
									function(response) {
										window.location = $( ele ).attr( 'href' );
									}
								).always(
									function() {
										window.location = $( ele ).attr( 'href' );
									}
								);
							}
						}
					);
				}
			}
		);
	}
);
