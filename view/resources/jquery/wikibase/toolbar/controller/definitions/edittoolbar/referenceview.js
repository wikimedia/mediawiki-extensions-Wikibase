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
		referenceviewafterstartediting: function( event ) {
			var $referenceview = $( event.target ),
				referenceview = $referenceview.data( 'referenceview' ),
				options = {},
				$container = $( '<div/>' ).appendTo(
					$referenceview.find( '.wikibase-referenceview-heading' )
				);

			options.$container = $container;

			var $statementview = $referenceview.closest( ':wikibase-statementview' ),
			statementview = $statementview.data( 'statementview' );

			function removeFromListView() {
				statementview._referencesListview.removeItem( $referenceview );
			}

			if ( ( !referenceview.options.statementGuid || !referenceview.value() ) && !statementview.isInEditMode() ) {
				options.label = mw.msg( 'wikibase-cancel' );
			}
			$referenceview.removetoolbar( options )
			.on( 'removetoolbarremove.removetoolbar', function( event ) {
				removeFromListView();
			} );

		}
	}
} );

}( jQuery, mediaWiki ) );
