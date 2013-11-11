/**
 * JavaScript for 'wikibase' extension special page 'EntitiesWithout*'
 *
 * @licence GNU GPL v2+
 * @author lbenedix
 */
( function( $, wb, undefined ) {
	'use strict';

	$( document ).ready( function() {
		var siteSelector = $( '#wb-entitieswithoutpage-language' );

		siteSelector.siteselector( { resultSet: wb.getSites() } );

		$( '#wb-entitieswithoutpage-form' ).submit( function( event ){
			var site = $( siteSelector ).siteselector( 'getSelectedSite' );

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
