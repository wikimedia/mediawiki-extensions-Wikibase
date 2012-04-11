/**
 * JavasSript for 'wikibase' extension, initializing some stuff when ready
 * @todo: this might not be necessary or only for ui stuff when we add more js modules!
 * 
 * @since 0.1
 * @file wikibase.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner at wikimedia.de >
 */
( function( $ ) {
	
	// add an edit tool for all properties in the data grid view
	$( 'body' )
	.find( '.wb-property-container' )
	.each( function() {
		new window.wikibase.ui.PropertyEditTool( this );
	} )

} )( jQuery );
