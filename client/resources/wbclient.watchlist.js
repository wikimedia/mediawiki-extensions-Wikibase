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
	getShowHideLink: function( toggleOn ) {
		var $linkMsg = 'show';
		if ( toggleOn ) {
			$linkMsg = 'hide';
		}
		var $link = '<a id="wb-toggle-link" href="javascript:void(0);">'
            + mw.message( $linkMsg ).escaped() + '</a>';
		return $link;
	},

	getToggle: function( toggleLink ) {
		return '<span id="wb-toggle">'
            +  mw.message( 'wbc-rc-hide-wikidata' ).escaped().replace( '$1', toggleLink )
            + '</span>';
	},

	addFilter: function() {
		var $showLink = watchlist.getShowHideLink( watchlist.toggleOn );
		var $wbToggle = watchlist.getToggle( $showLink );
		$( '#mw-watchlist-form-namespaceselector' ).before( ' | ' + $wbToggle );
		$( '#wb-toggle-link' ).click( watchlist.toggleWikibase );
		watchlist.toggleSections( watchlist.getSections() );
	},

	getSections: function() {
		return $( '.special' );
	},

	toggleSections: function( sections ) {
		$( sections ).each( function() {
			watchlist.toggleSection( this );
		});
	},

	/**
	 * Toggles the h4 date heading if that date has only wikibase changes
	 */
	toggleSection: function( section ) {
		if ( watchlist.hasWBEditsOnly( section ) ) {
			if ( watchlist.toggleOn === false ) {
				$( section ).prev().hide();
			} else {
				$( section ).prev().show();
			}
		}
	},

	/**
	 * Determines if a list of changes are wikibase only
	 * @param element el
	 */
	hasWBEditsOnly: function( section ) {
		var $section = $( section ),
			$edits = $section.find( 'li' ),
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
		watchlist.toggleSections( watchlist.getSections() );
		$( '#wb-toggle-link' ).html( watchlist.getShowHideLink( watchlist.toggleOn ) );
	},

	/**
	 * Initialises and adds the filter to the Special:Watchlist page
	 */
	init: function () {
		watchlist.addFilter();
	}
};

$( document ).ready( watchlist.init );

if ( !mw.wbclient ) {
	mw.wbclient = {};
}

mw.wbclient.watchlist = watchlist;

}( mediaWiki, jQuery ) );
