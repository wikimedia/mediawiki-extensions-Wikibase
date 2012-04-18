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
	
	// add an edit tool for all properties in the data grid view:
	$( 'body' )
	.find( '.wb-property-container' )
	.each( function() {
		// TODO: Make this nicer when we have implemented a JS class for properties
		if( $( this ).children( '.wb-property-container-key' ).attr( 'title') == 'description' ) {
			new window.wikibase.ui.DescriptionEditTool( this );
		} else {
			new window.wikibase.ui.PropertyEditTool( this );
		}
	} );
	
	// add an edit tool for the main label. This will be integrated into the heading nicely:
	new window.wikibase.ui.HeadingEditTool( $( '#firstHeading' ) );
	
	// edit tool for site links:
	new window.wikibase.ui.SiteLinksEditTool( $( 'table.wb-languagelinks' ) );
} )( jQuery );
