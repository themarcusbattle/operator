window.Nock = ( function( window, document, $ ) {

	var app = {};

	app.init = function() {
		$(document).on( 'change', 'select[name="account_id"]', app.changeAccount );
		$(document).on( 'click', '.send-message-button', app.sendMessageButton );
	};

	app.changeAccount = function( change_event ) {

		$.ajax({
			method: "GET",
			url   : '/wp-json/nock/v1/groups',
			data  : { account_id: change_event.target.value },
			cache : false
		})
		.done(function( response ) {

			if ( response.success ) {
				
				var $select = $('#select-group');

				$select.empty(); // remove old options
				$.each( response.data, function( key, value ) {
					console.log( $("<option></option>").attr( "value", value.id ).text( value.name ) );
					$select.append( $("<option></option>").attr( "value", value.id ).text( value.name ) );
				});
			}
		});
	}

	app.sendMessageButton = function( click_event ) {

		click_event.preventDefault();

		$.ajax({
			method: "POST",
			url: '/wp-json/nock/v1/messages',
			data: $('#message-editor').serialize()
		})
		.done(function( msg ) {
			console.log( "Data Saved: " + msg );
		});

		return;
	}

	$( document ).ready( app.init );

	return app;

} )( window, document, jQuery );
