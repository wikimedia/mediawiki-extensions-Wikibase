/**
 * JavaScript for Special:Watchlist
 */
( function ( mw, $ ) {
	var watchlist;

	watchlist = {

		toggleOn : false,

		addFilter: function() {
			$( '#mw-watchlist-form-namespaceselector' )
				.before( ' | <a href="#" id="wikibase-toggle">Show</a> Wikidata' );
			$( '#wikibase-toggle' ).click( watchlist.toggleWikibase );
		},

		toggleWikibase: function() {
			if ( watchlist.toggleOn === false ) {
				watchlist.toggleOn = true;
				$( '.wikibase-edit' ).show();
			} else {
				watchlist.toggleOn = false;
				$( '.wikibase-edit' ).hide();
			}
		},

		init: function () {
			//$( '.wikibase-edit' ).hide();
			watchlist.addFilter();
			//$select.change( rc.updateCheckboxes );
		}
	};

	$( document ).ready( watchlist.init );

	//wikibase-client.watchlist = watchlist;

}( mediaWiki, jQuery ) );
