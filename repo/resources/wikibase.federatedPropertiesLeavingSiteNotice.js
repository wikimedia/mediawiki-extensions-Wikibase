/**
 * Modal for notifying the user of leaving the current Wikibase instance
 *
 * @license GPL-2.0-or-later
 *
 */
( function () {
	'use strict';
	var cookieKey = 'wikibase.dismissleavingsitenotice';
	var optionsKey = 'wb-dismissleavingsitenotice';

	var dismissLeavingSiteNotice = function () {
		return mw.cookie.get( cookieKey ) || mw.user.options.get( optionsKey );
	};

	// used as a cache if api is called
	var cachedCheckBoxValue = dismissLeavingSiteNotice();

	// no need to continue if it won't get called
	if ( cachedCheckBoxValue ) {
		return;
	}

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

			var inputCheckbox = new OO.ui.CheckboxInputWidget( {
				selected: dismissLeavingSiteNotice() || cachedCheckBoxValue
			} );
			var checkBoxFieldLayout = new OO.ui.FieldLayout(
				inputCheckbox,
				{
					label: mw.msg( 'wikibase-federated-properties-leaving-site-notice-checkbox-label' ),
					align: 'inline'
				}
			);
			inputCheckbox.on( 'change', function ( selected ) {
				selected = selected || null;
				cachedCheckBoxValue = selected;

				if ( mw.user.isAnon() ) {
					mw.cookie.set( cookieKey, selected, { expires: 3 * 365 * 24 * 60 * 60, path: '/' } );
				} else {
					var api = new mw.Api();
					api.saveOption( optionsKey, selected );
				}
			} );

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
				checkBoxFieldLayout.$element,
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

		var fireLeavingSiteDialog = function ( data ) {
			// Create a new dialog window.
			var processDialog = new LeavingSiteNoticeDialog( {
				size: 'medium',
				data: data
			} );

			windowManager.addWindows( [ processDialog ] );
			windowManager.openWindow( processDialog );
		};

		mw.hook( 'wikibase.entityPage.entityView.rendered' ).add( function () {
			$( '.wikibase-statementgroupview-property-label a' ).on( 'click', function ( e ) {
				if ( dismissLeavingSiteNotice() ) {
					return;
				}

				e.preventDefault();
				fireLeavingSiteDialog( this.href );
			} );
		} );
		$( '.comment a.fedprop' ).on( 'click', function ( e ) {
			e.preventDefault();
			fireLeavingSiteDialog( this.href );
		} );

	} );
}() );
