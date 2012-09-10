/**
 * JavaScript for 'wikibase' extension special page 'ItemDisambiguation'
 *
 * @since 0.1
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jens Ohlig
 */
( function( $, mw, wb, undefined ) {
	'use strict';

	$( document ).ready( function() {
		if( ( mw.config.get( 'wgCanonicalSpecialPageName' ) !== 'ItemDisambiguation' )  || ( $.uls === undefined ) ) {
			return; // not the right special page or ULS dependency unavailable
		}

		// TODO/FIXME: this should rather use wb.PropertyEditTool.EditableSiteLink fir mimicking the exact same behavior

		var langList = new Array();
		var languages = $.uls.data.autonyms();
		$.each( languages, function( key, value ) {
			langList.push( {
				'label': value + ' (' + key + ')',
				'value': value + ' (' + key + ')'
			} );
		} );
		$( '#wb-itemdisambiguation-languagename' ).wikibaseAutocomplete( { "source": langList } );

		// On submit, replace human readable value like "English (en)" with actual language name ("en")
		$( '#wb-itemdisambiguation-form1' ).submit( function( event ) {
			var langID = String( $( '#wb-itemdisambiguation-languagename' ).val().replace(/.*\(|\).*/gi,'') );
			$( '#wb-itemdisambiguation-languagename' ).val( langID );
		});
	} );

} )( jQuery, mediaWiki, wikibase );
