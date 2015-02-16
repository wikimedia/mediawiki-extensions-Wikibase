( function( $ ) {
	'use strict';

/**
 * @ignore
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
$.wikibase.toolbarcontroller.definition( 'removetoolbar', {
	id: 'statementview-referenceview',
	selector: ':' + $.wikibase.statementview.prototype.namespace
		+ '-' + $.wikibase.statementview.prototype.widgetName,
	events: {
		'statementviewafterstartediting referenceviewcreate': function( event, toolbarController ) {
			var $statementview,
				statementview,
				$referenceview;

			if( event.type.indexOf( 'statementview' ) === 0 ) {
				$statementview = $( event.target );
				statementview = $statementview.data( 'statementview' );
				$referenceview = statementview.$references.find( ':wikibase-referenceview' );
			} else {
				$referenceview = $( event.target );
				$statementview = $referenceview.closest( ':wikibase-statementview' );
				statementview = $statementview.data( 'statementview' );

				if( !statementview.isInEditMode() ) {
					return;
				}
			}

			$referenceview.each( function() {
				var $referenceview = $( this ),
					$container = $referenceview.find( '.wikibase-toolbar-container' );

				if( !$container.length ) {
					$container = $( '<div/>' ).appendTo(
						$referenceview.find( '.wikibase-referenceview-heading' )
					);
				}

				$referenceview
				.removetoolbar( {
					$container: $container
				} )
				.on( 'removetoolbarremove.removetoolbar', function( event ) {
					var $suspectedReferenceview = $( event.target );

					if( $suspectedReferenceview[0] === $referenceview[0] ) {
						statementview.$references.children( ':wikibase-listview' )
							.data( 'listview' ).removeItem( $referenceview );
					}
				} );
			} );
		},
		'statementviewafterstopediting referenceviewdestroy': function( event, toolbarcontroller ) {
			var $referenceview;

			if( event.type === 'statementviewafterstopediting' ) {
				var $statementview = $( event.target ),
					statementview = $statementview.data( 'statementview' );
				$referenceview = statementview.$references.find( ':wikibase-referenceview' );
			} else {
				$referenceview = $( event.target );
			}

			$referenceview.each( function() {
				var $referenceview = $( this ),
					removetoolbar = $referenceview.data( 'removetoolbar' );

				if( removetoolbar ) {
					toolbarcontroller.destroyToolbar( removetoolbar );
				}
				$referenceview.off( '.removetoolbar' );
			} );
		},
		referenceviewdisable: function( event ) {
			var $referenceview = $( event.target ),
				referenceview = $referenceview.data( 'referenceview' ),
				removetoolbar = $referenceview.data( 'removetoolbar' );

			if( removetoolbar ) {
				removetoolbar.option( 'disabled', referenceview.option( 'disabled' ) );
			}
		}
	}
} );

}( jQuery ) );
