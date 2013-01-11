/**
 * JavaScript for Special:Watchlist
 */
( function ( mw, $ ) {
	var watchlist;

	watchlist = {

		toggleOn : false,

		addFilter: function() {
			showLink = ' | <a id="wikibase-toggle">' + mw.message( 'show' ).escaped() + '</a>';
			wbToggle = mw.msg( 'wbc-rc-hide-wikidata', showLink );
			$( '#mw-watchlist-form-namespaceselector' ).before( wbToggle );
			$( '#wikibase-toggle' ).click( watchlist.toggleWikibase );
			$( '.special' ).each( function() {
				if( watchlist.wbEditsOnly( this ) ) {
					$( this ).prev().hide();
				}
			});
		},

		sectionToggle: function() {
			$( '.special' ).each( function() {
				if( watchlist.wbEditsOnly( this ) ) {
					if ( watchlist.toggleOn === false ) {
						$( this ).prev().hide();
					} else {
						$( this ).prev().show();
					}
				}
			});
		},

		wbEditsOnly: function( el ) {
			all = $( el ).find( 'li' ).length;
			wbEdits = $( el ).find( 'li.wikibase-edit' ).length;

			if ( all === wbEdits ) {
				return true;
			}
			return false;
		},

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

		init: function () {
			watchlist.addFilter();
		}
	};

	$( document ).ready( watchlist.init );

}( mediaWiki, jQuery ) );
