/**
 * JavaScript for 'wikibase' extension special page 'ItemDisambiguation'
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jens Ohlig
 */
( function( $, mw, wb, undefined ) {
	'use strict';

	$( document ).ready( function() {
		if( ( mw.config.get( 'wgCanonicalSpecialPageName' ) !== 'ItemDisambiguation' ) ) {
			return; // not the right special page
		}

		// TODO: Migth want to use the siteselector jquery widget or some other suggester derivate
		var langList = [];
		if ( $.uls !== undefined ) {
			var languages = $.uls.data.getAutonyms();
			$.each( languages, function( key, value ) {
				langList.push( {
					'label': value + ' (' + key + ')',
					'value': value + ' (' + key + ')'
				} );
			} );
		}
		$( '#wb-itemdisambiguation-languagename' ).suggester( { 'source': langList } );

		// On submit, replace human readable value like "English (en)" with actual language name ("en")
		$( '#wb-itemdisambiguation-form1' ).submit( function() {
			var $input = $( '#wb-itemdisambiguation-languagename' );
			var langID = String( $input.val().replace( /.*\(|\).*/gi,'' ) );
			$input.val( langID );
		});
	} );

} )( jQuery, mediaWiki, wikibase );
