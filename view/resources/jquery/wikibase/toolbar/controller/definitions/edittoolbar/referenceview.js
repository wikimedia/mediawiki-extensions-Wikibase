( function( $, mw ) {
	'use strict';

/**
 * @ignore
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
$.wikibase.toolbarcontroller.definition( 'edittoolbar', {
	id: 'referenceview',
	selector: ':' + $.wikibase.referenceview.prototype.namespace
		+ '-' + $.wikibase.referenceview.prototype.widgetName,
	events: {
		referenceviewcreate: function( event ) {
			var $referenceview = $( event.target ),
				referenceview = $referenceview.data( 'referenceview' ),
				options = {},
				$container = $referenceview.find( '.wikibase-toolbar-container' );

			if ( !$container.length ) {
				$container = $( '<div/>' ).appendTo(
					$referenceview.find( '.wikibase-referenceview-heading' )
				);
			}

			options.$container = $container;

			function removeFromListView() {
				var $statementview = $referenceview.closest( ':wikibase-statementview' ),
					statementview = $statementview.data( 'statementview' );

				statementview._referencesListview.removeItem( $referenceview );
			}

			if ( !referenceview.options.statementGuid || !referenceview.value() ) {
				var $statementview = $referenceview.closest( ':wikibase-statementview' ),
					statementview = $statementview.data( 'statementview' );
				if ( !statementview.isInEditMode() ) {
					options.label = mw.msg( 'wikibase-cancel' );
				}
				$referenceview.removetoolbar( options )
				.on( 'removetoolbarremove.removetoolbar', function( event ) {
					removeFromListView();
				} );

				return;
			}

			options.getHelpMessage = function() {
				return $.Deferred().resolve( referenceview.options.helpMessage ).promise();
			};

			var edittoolbar = $referenceview.edittoolbar( options ).data( 'edittoolbar' );

			var guid = referenceview.options.statementGuid;
			var referencesChanger = referenceview.options.referencesChanger;
			var controller = new wikibase.view.ToolbarViewController(
				{
					remove: function( reference ) {
						return referencesChanger.removeReference( guid, reference );
					},
					save: function( reference ) {
						return referencesChanger.setReference( guid, reference );
					}
				},
				edittoolbar,
				referenceview
			);
			edittoolbar.setController( controller );
			edittoolbar.option(
				'onRemove',
				function() {
					return controller.remove();
				}
			);

			$referenceview.on( 'keydown.edittoolbar', function( event ) {
				if ( referenceview.option( 'disabled' ) ) {
					return;
				}
				if ( event.keyCode === $.ui.keyCode.ESCAPE ) {
					controller.stopEditing( true );
				} else if ( event.keyCode === $.ui.keyCode.ENTER ) {
					controller.stopEditing( false );
				}
			} );
		},
		referenceviewdisable: function( event ) {
			var $referenceview = $( event.target ),
				referenceview = $referenceview.data( 'referenceview' );

			if ( !referenceview ) {
				return;
			}

			var disable = referenceview.option( 'disabled' ),
				edittoolbar = $referenceview.data( 'edittoolbar' );

			if ( !edittoolbar ) {
				return;
			}

			var btnSave = edittoolbar.getButton( 'save' ),
				enableSave = ( referenceview.isValid() && !referenceview.isInitialValue() );

			edittoolbar.option( 'disabled', disable );
			if ( !disable ) {
				btnSave.option( 'disabled', !enableSave );
			}
		}

		// Destroying the referenceview will destroy the toolbar. Trying to destroy the toolbar
		// in parallel will cause interference.
	}
} );

}( jQuery, mediaWiki ) );
