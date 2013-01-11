/**
 * JavaScript for Special:Watchlist
 */
( function ( mw, $ ) {
'use strict';

var watchlist;

watchlist = {

	/**
	 * @type bool
	 */
	toggleOn : false,

	/**
	 * Adds a toggle link for showing and hiding wikibase edits
	 */
	getShowHideLink: function() {
		var $linkMsg = 'show';
		if ( watchlist.toggleOn ) {
			$linkMsg = 'hide';
		}
		var $link = '<a id="wb-toggle-link" href="javascript:void(0);">'
            + mw.message( $linkMsg ).escaped() + '</a>';
		return $link;
	},

	addFilter: function() {
		var $showLink = watchlist.getShowHideLink();
		var $wbToggle = mw.message( 'wbc-rc-hide-wikidata' ).escaped().replace( '$1', $showLink );
		$( '#mw-watchlist-form-namespaceselector' ).before( ' | ' + $wbToggle );
		$( '#wb-toggle-link' ).click( watchlist.toggleWikibase );
		watchlist.toggleSection();
	},

	/**
	 * Toggles the h4 date heading if that date has only wikibase changes
	 */
	toggleSection: function() {
		$( '.special' ).each( function() {
			if ( watchlist.hasWBEditsOnly( this ) ) {
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
	hasWBEditsOnly: function( el ) {
		var $el = $( el ),
			$edits = $el.find( 'li' ),
			$wbEdits = $edits.filter( '.wikibase-edit' );

		return $edits.length === $wbEdits.length;
	},

	/**
	 * Performs the toggle, showing or hiding the <li> elements for wikibase
	 * edits and the date section heading if the section has wikibase only edits.
	 */
	toggleWikibase: function() {
		watchlist.toggleOn = !watchlist.toggleOn
		$( '.wikibase-edit' ).toggle();
		watchlist.toggleSection();
		$( '#wb-toggle-link' ).html( watchlist.getShowHideLink );
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
