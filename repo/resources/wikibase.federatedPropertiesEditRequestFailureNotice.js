/**
 * Popup notification for failed edit due to failed request to the source wiki
 */
$( function () {
	'use strict';

	var isOpen = false;

	// Create and append the window manager.
	var windowManager = new OO.ui.WindowManager();
	$( document.body ).append( windowManager.$element );

	function FailedEditErrorDialog( config ) {
		FailedEditErrorDialog.super.call( this, config );
	}
	OO.inheritClass( FailedEditErrorDialog, OO.ui.ProcessDialog );

	FailedEditErrorDialog.static.name = 'FailedEditRequestNotice';
	FailedEditErrorDialog.static.title = mw.msg( 'wikibase-federated-properties-edit-request-failed-notice-header' );
	FailedEditErrorDialog.static.actions = [
		{ action: 'cancel', flags: [ 'safe', 'close' ] }
	];
	// Use the initialize() method to add content to the dialog's $body,
	// to initialize widgets, and to set up event handlers.
	FailedEditErrorDialog.prototype.initialize = function () {
		FailedEditErrorDialog.super.prototype.initialize.apply( this, arguments );
		var dialog = this;

		var tryAgainButton = new OO.ui.ButtonWidget( {
			label: mw.msg( 'wikibase-federated-properties-edit-request-failed-notice-try-again' ),
			flags: [
				'primary',
				'progressive'
			],
			classes: [ 'wb-failed-request-notice-try-again' ]
		} );
		tryAgainButton.on( 'click', function () {
			dialog.close();
			isOpen = false;
		} );

		var $content = $( '<div>' ).append(
			$( '<p>' ).text( mw.msg( 'wikibase-federated-properties-edit-request-failed-notice-notice' ) ),
			$( '<br>' ),
			tryAgainButton.$element
		);

		this.content = new OO.ui.PanelLayout( { padded: true, expanded: false } );
		this.content.$element.append( $content );
		this.$body.append( this.content.$element );
	};

	FailedEditErrorDialog.prototype.getActionProcess = function ( action ) {
		var dialog = this;
		dialog.close();
		isOpen = false;
		// Fallback to parent handler.
		return FailedEditErrorDialog.super.prototype.getActionProcess.call( this, action );
	};

	$( document ).on( 'ajaxError', function ( event, xhr, settings ) {
		if ( xhr.responseJSON && xhr.responseJSON.errors ) {
			var shouldOpen = false;

			for ( var i = 0; i < xhr.responseJSON.errors.length; i++ ) {
				var error = xhr.responseJSON.errors[ i ];
				if ( error.code === 'federated-properties-failed-request' && error.data && error.data.property ) {
					$( '#' + error.data.property ).find( '.wikibase-toolbar-item .wikibase-toolbar-button-cancel a' ).trigger( 'click' );
					shouldOpen = true;
				}
			}
			if ( shouldOpen && !isOpen ) {
				var processDialog = new FailedEditErrorDialog( {
					size: 'medium'
				} );

				windowManager.addWindows( [ processDialog ] );
				windowManager.openWindow( processDialog );

				isOpen = true;
			}
		}
	} );
} );
