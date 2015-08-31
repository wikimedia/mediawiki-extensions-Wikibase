/**
 * JavaScript for 'wikibase' extension special page 'ItemByTitle'
 *
 * @licence GNU GPL v2+
 * @author Jens Ohlig
 */
( function( $, mw, OO, wb ) {
	'use strict';

	$( document ).ready( function() {
		if( ( mw.config.get( 'wgCanonicalSpecialPageName' ) !== 'ItemByTitle' ) ) {
			return; // not the right special page
		}

		var api,
			input = OO.ui.infuse( $( '#wb-itembytitle-sitename' ) ),
			$pagename = OO.ui.infuse( $( '#pagename' ) ).$input;

		function setApi( value ) {
			var site = wb.sites.getSite( value );
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
		}

		setApi( input.getValue() );

		input.on( 'change', setApi );

		$pagename
		.attr( 'autocomplete', 'off' )
		.suggester( {
			source: function( term ) {
				var deferred = $.Deferred();

				if( api ) {
					api.get( {
						action: 'query',
						list: 'allpages',
						apprefix: $pagename.val()
					} )
					.done( function( data ) {
						deferred.resolve( $.map( data.query.allpages, function( page ) {
							return page.title;
						} ) );
					} )
					.fail( function() {
						deferred.reject();
					} );
				} else {
					deferred.resolve( [] );
				}

				return deferred.promise();
			}
		} );
	} );

} )( jQuery, mediaWiki, OO, wikibase );
