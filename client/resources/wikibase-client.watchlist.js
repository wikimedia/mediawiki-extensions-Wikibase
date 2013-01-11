/**
 * JavaScript for Special:Watchlist
 */
( function ( mw, $ ) {
'use strict';

var watchlist;

watchlist = {

	toggleOn : false,

	/**
	 * Adds a toggle link for showing and hiding wikibase edits
	 */
	addFilter: function() {
		var showLink = ' | <a id="wikibase-toggle">' + mw.message( 'show' ).escaped() + '</a>';
		var wbToggle = mw.message( 'wbc-rc-hide-wikidata' ).escaped().replace( '$1', showLink );
		$( '#mw-watchlist-form-namespaceselector' ).before( wbToggle );
		$( '#wikibase-toggle' ).click( watchlist.toggleWikibase );
		watchlist.sectionToggle();
	},

	/**
	 * Toggles the h4 date heading if that date has only wikibase changes
	 */
	sectionToggle: function() {
		$( '.special' ).each( function() {
			if ( watchlist.wbEditsOnly( this ) ) {
				if ( watchlist.toggleOn === false ) {
					$( this ).prev().hide();
				} else {
					$( this ).prev().show();
				}
			}
		});
	},

	/**
	 * Determines if a list of changes are wikibase only
	 * @param element el
	 */
	wbEditsOnly: function( el ) {
		var all = $( el ).find( 'li' ).length;
		var wbEdits = $( el ).find( 'li.wikibase-edit' ).length;

		if ( all === wbEdits ) {
			return true;
		}
		return false;
	},

	/**
	 * Performs the toggle, showing or hiding the <li> elements for wikibase
	 * edits and the date section heading if the section has wikibase only edits.
	 */
	toggleWikibase: function() {
		if ( watchlist.toggleOn === false ) {
			watchlist.toggleOn = true;
			$( '.wikibase-edit' ).show();
		} else {
			watchlist.toggleOn = false;
			$( '.wikibase-edit' ).hide();
		}
		watchlist.sectionToggle();
	},

	/**
	 * Initialises and adds the filter to the Special:Watchlist page
	 */
	init: function () {
		watchlist.addFilter();
	}
};

$( document ).ready( watchlist.init );

}( mediaWiki, jQuery ) );
