( function( $ ) {
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
				options = {
					interactionWidget: referenceview
				},
				$container = $referenceview.find( '.wikibase-toolbar-container' );

			if( !$container.length ) {
				$container = $( '<div/>' ).appendTo(
					$referenceview.find( '.wb-referenceview-heading' )
				);
			}

			options.$container = $container;

			if( !!referenceview.value() ) {
				options.onRemove = function() {
					var $statementview = $referenceview.closest( ':wikibase-statementview' ),
						statementview = $statementview.data( 'statementview' );
					if( statementview ) {
						statementview.remove( referenceview );
					}
				};
			}

			$referenceview.edittoolbar( options );

			$referenceview.on( 'keydown.edittoolbar', function( event ) {
				if( referenceview.option( 'disabled' ) ) {
					return;
				}
				if( event.keyCode === $.ui.keyCode.ESCAPE ) {
					referenceview.stopEditing( true );
				} else if( event.keyCode === $.ui.keyCode.ENTER ) {
					referenceview.stopEditing( false );
				}
			} );
		},
		'referenceviewchange referenceviewafterstartediting': function( event ) {
			var $referenceview = $( event.target ),
				referenceview = $referenceview.data( 'referenceview' ),
				edittoolbar = $referenceview.data( 'edittoolbar' ),
				btnSave = edittoolbar.getButton( 'save' ),
				enableSave = referenceview.isValid() && !referenceview.isInitialValue();

			btnSave[enableSave ? 'enable' : 'disable']();
		},
		referenceviewdisable: function( event ) {
			var $referenceview = $( event.target ),
				referenceview = $referenceview.data( 'referenceview' );

			if( !referenceview ) {
				return;
			}

			var disable = referenceview.option( 'disabled' ),
				edittoolbar = $referenceview.data( 'edittoolbar' ),
				btnSave = edittoolbar.getButton( 'save' ),
				enableSave = ( referenceview.isValid() && !referenceview.isInitialValue() );

			edittoolbar.option( 'disabled', disable );
			if( !disable ) {
				btnSave.option( 'disabled', !enableSave );
			}
		}

		// Destroying the referenceview will destroy the toolbar. Trying to destroy the toolbar
		// in parallel will cause interference.
	}
} );

}( jQuery ) );
