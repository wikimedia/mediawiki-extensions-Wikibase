/**
 * JavaScript for 'wikibase' extension special page 'ItemByTitle'
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jens Ohlig
 */
( function( $, mw, wb, undefined ) {
	'use strict';

	$( document ).ready( function() {
		if( ( mw.config.get( 'wgCanonicalSpecialPageName' ) !== 'ItemByTitle' ) ) {
			return; // not the right special page
		}

		function getSite( input ) {
			return wb.sites.getSite( input.replace( /.*\(|\).*/gi, '' ) );
		}

		// this will build a drop-down for the site selection:
		var api,
			sites = wb.sites.getSites(),
			siteList = [],
			$input = $( '#wb-itembytitle-sitename' );
		for( var siteId in sites ) {
			if( sites.hasOwnProperty( siteId ) ) {
				siteList.push( sites[ siteId ].getName() + ' (' + siteId + ')' );
			}
		}
		$input
		.attr( 'autocomplete', 'off' )
		.suggester( { source: siteList } )
		.on( 'change', function () {
			var site = getSite( $input.val() );
			if ( site ) {
				var apiUrl = site.getApi();
				if ( !api || apiUrl !== api.apiUrl ) {
					api = new mw.Api( {
						ajax: {
							url: apiUrl,
							dataType: 'jsonp'
						}
					} );
				}
			} else {
				api = null;
			}
		} );
		$( '#pagename' )
		.attr( 'autocomplete', 'off' )
		.on( 'input', function () {
			if ( api ) {
				var $pagename = $( this );
				api.get( {
					action: 'query',
					list: 'allpages',
					apprefix: $pagename.val()
				} ).done( function ( data ) {
					$pagename.suggester( {
						source: $.map( data.query.allpages, function ( item ) {
							return item.title;
						} )
					} );
				} );
			}
		} );
		// Hackety hack hack...
		// On submit, replace human readable value like "English (en)" with actual sitename ("enwiki")
		$( '#wb-itembytitle-form1' ).submit( function() {
			var site = getSite( $input.val() );
			if ( site ) {
				$input.val( site.getId() );
			}
		} );
	} );

} )( jQuery, mediaWiki, wikibase );
