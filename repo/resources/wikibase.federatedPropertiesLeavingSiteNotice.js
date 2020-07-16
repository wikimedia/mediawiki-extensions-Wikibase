/**
 * Modal for notifying the user of leaving the current Wikibase instance
 *
 * @license GPL-2.0-or-later
 *
 */
( function () {
	'use strict';
	var hostWikibaseLocation = require( './federatedPropertiesHostWikibase.json' );

	$( function () {
		function LeavingSiteNoticeDialog( config ) {
			LeavingSiteNoticeDialog.super.call( this, config );
		}
		OO.inheritClass( LeavingSiteNoticeDialog, OO.ui.ProcessDialog );

		LeavingSiteNoticeDialog.static.name = 'leavingSiteNotice';
		LeavingSiteNoticeDialog.static.title = mw.msg( 'wikibase-federated-properties-leaving-site-notice-header' );
		LeavingSiteNoticeDialog.static.actions = [
			{ action: 'cancel', flags: [ 'safe', 'close' ] }
		];

		// Use the initialize() method to add content to the dialog's $body,
		// to initialize widgets, and to set up event handlers.
		LeavingSiteNoticeDialog.prototype.initialize = function () {
			LeavingSiteNoticeDialog.super.prototype.initialize.apply( this, arguments );
			var dialog = this;
			/* commenting out checkbox since it is not functional yet
			var inputCheckbox = new OO.ui.FieldLayout(
				new OO.ui.CheckboxInputWidget( {
					selected: false
				} ),
				{
					label: mw.msg( 'wikibase-federated-properties-leaving-site-notice-checkbox-label' ),
					align: 'inline'
				}
			);
*/
			var cancelButton = new OO.ui.ButtonWidget( {
				label: mw.msg( 'wikibase-federated-properties-leaving-site-notice-cancel' ),
				classes: [ 'wb-leaving-site-notice-cancel' ]
			} );
			cancelButton.on( 'click', function () {
				dialog.close();
			} );
			var continueButton = new OO.ui.ButtonWidget( {
				href: this.data,
				label: mw.msg( 'wikibase-federated-properties-leaving-site-notice-continue' ),
				flags: [
					'primary',
					'progressive'
				],
				target: '_blank',
				classes: [ 'wb-leaving-site-notice-continue' ]
			} );

			// Add an horizontal field layout for buttons
			var buttonLayout = new OO.ui.FieldLayout(
				new OO.ui.Widget( {
					content: [ new OO.ui.HorizontalLayout( {
						items: [
							cancelButton,
							continueButton
						]
					} ) ]
				} )
			);

			var $content = $( '<div>' ).append(
				$( '<p>' ).text( mw.msg( 'wikibase-federated-properties-leaving-site-notice-notice', hostWikibaseLocation ) ),
				buttonLayout.$element
			);

			this.content = new OO.ui.PanelLayout( { padded: true, expanded: false } );
			this.content.$element.append( $content );
			this.$body.append( this.content.$element );
		};

		LeavingSiteNoticeDialog.prototype.getActionProcess = function ( action ) {
			var dialog = this;
			if ( action ) {
				return new OO.ui.Process( function () {
					dialog.close( {
						action: action
					} );
				} );
			}
			// Fallback to parent handler.
			return LeavingSiteNoticeDialog.super.prototype.getActionProcess.call( this, action );
		};

		// Create and append the window manager.
		var windowManager = new OO.ui.WindowManager();
		$( document.body ).append( windowManager.$element );

		mw.hook( 'wikibase.entityPage.entityView.rendered' ).add( function () {
			$( '.wikibase-statementgroupview-property-label a' ).on( 'click', function ( e ) {
				e.preventDefault();
				// Create a new dialog window.
				var processDialog = new LeavingSiteNoticeDialog( {
					size: 'medium',
					data: this.href
				} );

				windowManager.addWindows( [ processDialog ] );
				windowManager.openWindow( processDialog );

			} );
		} );

	} );
}() );
