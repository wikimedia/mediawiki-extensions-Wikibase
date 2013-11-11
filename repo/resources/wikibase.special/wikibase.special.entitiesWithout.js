/**
 * JavaScript for 'wikibase' extension special page 'EntitiesWithout*'
 *
 * @licence GNU GPL v2+
 * @author lbenedix
 */
( function( $, wb, undefined ) {
	'use strict';

	$( document ).ready( function() {
		$( '#wb-entitieswithoutpage-language' ).siteselector( { resultSet: wb.getSites() } );

		$('#wb-entitieswithoutpage-form').submit( function( event ){
			var languageSeletor = $( '#wb-entitieswithoutpage-language' ).siteselector( 'getSelectedSite' );

			$( '#wb-entitieswithoutpage-form' ).append(
				$( '<input>' )
					.attr( 'type', 'hidden' )
					.attr( 'name', 'site' )
					.val( languageSeletor.getId() )
				);

			$( '#wb-entitieswithoutpage-form' ).append(
				$( '<input>' )
					.attr( 'type', 'hidden' )
					.attr( 'name', 'languagecode' )
					.val( languageSeletor.getLanguageCode() )
				);

		} );

	} );

} )( jQuery, wikibase );
