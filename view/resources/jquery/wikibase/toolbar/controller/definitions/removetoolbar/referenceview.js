( function( $ ) {
	'use strict';

/**
 * @ignore
 *
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 * @author Adrian Heine
 * @author Jonas Kress
 */
$.wikibase.toolbarcontroller.definition( 'removetoolbar', {
	id: 'referenceview',
	selector: ':' + $.wikibase.referenceview.prototype.namespace
		+ '-' + $.wikibase.referenceview.prototype.widgetName,
	events: {
		referenceviewafterstartediting: function( event ) {
			var $referenceview = $( event.target ),
				options = {
					$container: $( '<div/>' ).appendTo(
						$referenceview.find( '.wikibase-referenceview-heading' )
					)
				};

			var $statementview = $referenceview.closest( ':wikibase-statementview' ),
			statementview = $statementview.data( 'statementview' );

			function removeFromListView() {
				statementview._referencesListview.removeItem( $referenceview );
			}

			$referenceview.removetoolbar( options )
			.on( 'removetoolbarremove.removetoolbar', function( event ) {
				if ( event.target === $referenceview[0] ) {
					removeFromListView();
				}
			} );

		}
	}
} );

}( jQuery ) );
