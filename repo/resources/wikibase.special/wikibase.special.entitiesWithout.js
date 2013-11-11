/**
 * JavaScript for 'wikibase' extension special page 'EntitiesWithout*'
 *
 * @file
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author lbenedix
 */
( function( $, mw, wb, undefined ) {
	'use strict';

	$( document ).ready( function() {
		$('#wb-entitieswithoutpage-language').siteselector( { resultSet: wb.getSites() });
		$('#wb-entitieswithoutpage-form').submit( function( event ){
			$('#wb-entitieswithoutpage-site').val( $('#wb-entitieswithoutpage-language').siteselector( 'getSelectedSite' ).getId() );
			$('#wb-entitieswithoutpage-language-code').val( $('#wb-entitieswithoutpage-language').siteselector( 'getSelectedSite' ).getLanguageCode() );
		});

	} );

} )( jQuery, mediaWiki, wikibase );
