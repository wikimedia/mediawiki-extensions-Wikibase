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
			var site = $( '#wb-entitieswithoutpage-language' ).siteselector( 'getSelectedSite' );

			$( this ).append(
				$( '<input>' )
					.attr( 'type', 'hidden' )
					.attr( 'name', 'site' )
					.val( site.getId() )
				).append(
				$( '<input>' )
					.attr( 'type', 'hidden' )
					.attr( 'name', 'languagecode' )
					.val( site.getLanguageCode() )
				);

		} );

	} );

} )( jQuery, wikibase );
