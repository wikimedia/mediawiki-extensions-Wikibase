/**
 * JavasSript for 'wikibase' extension, initializing some stuff when ready
 * @todo: this might not be necessary or only for ui stuff when we add more js modules!
 * 
 * @since 0.1
 * @file wikibase.startup.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner at wikimedia.de >
 */
"use strict";
( function( $ ) {
	
	// add an edit tool for the main label. This will be integrated into the heading nicely:
	new window.wikibase.ui.LabelEditTool( $( '#firstHeading' ) );
	
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
	
	// edit tool for site links:
	
	// if there are no site links yet, we have to create the table for it to initialize the ui
	// without css this is not required, so we build it here manually
	$( '.wb-sitelinks-empty' )
	.each( function() {
		$( this ).replaceWith( wikibase.ui.SiteLinksEditTool.getEmptyStructure() )
	} );
	
	$( 'table.wb-sitelinks' ).each( function() {
		// actual initialization
		new window.wikibase.ui.SiteLinksEditTool( $( this ) );
	} );
	
} )( jQuery );
