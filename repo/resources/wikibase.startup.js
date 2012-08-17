/**
 * JavaScript for 'wikibase' extension, initializing some stuff when ready
 * @todo: this might not be necessary or only for ui stuff when we add more js modules!
 *
 * @since 0.1
 * @file wikibase.startup.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner at wikimedia.de >
 *
 * Events:
 * -------
 * restrictItemPageActions: Triggered when editing is not allowed for the user.
 *                          Parameters: (1) jQuery.event
 * blockItemPageActions:    Triggered when editing is not allowed for the user because he is blocked from the page.
 *                          Parameters: (1) jQuery.event
 * (see TODO/FIXME about these three events where they are being triggered!)
 */

( function( $, mw, wb, undefined ) {
	"use strict";

	$( document ).ready( function() {
		// add an edit tool for the main label. This will be integrated into the heading nicely:
		if ( $( '.wb-firstHeading' ).length ) { // Special pages do not have a custom wb heading
			new wb.ui.LabelEditTool( $( '.wb-firstHeading' ) );
		}

		// add an edit tool for all properties in the data view:
		$( 'body' )
		.find( '.wb-property-container' )
		.each( function() {
			// TODO: Make this nicer when we have implemented a JS class for properties
			if( $( this ).children( '.wb-property-container-key' ).attr( 'title') === 'description' ) {
				new wb.ui.DescriptionEditTool( this );
			} else {
				new wb.ui.PropertyEditTool( this );
			}
		} );

		if( mw.config.get( 'wbItemId' ) !== null ) {
			// if there are no aliases yet, the DOM structure for creating new ones is created manually since it is not
			// needed for running the page without JS
			$( '.wb-aliases-empty' )
			.each( function() {
				$( this ).replaceWith( wikibase.ui.AliasesEditTool.getEmptyStructure() );
			} );

			// edit tool for aliases:
			$( 'body' )
			.find( '.wb-aliases' )
			.each( function() {
				new wb.ui.AliasesEditTool( this );
			} );

			// if there are no site links yet, we have to create the table for it to initialize the ui
			// without JS this is not required, so we build it here manually
			$( '.wb-sitelinks-empty' )
			.each( function() {
				$( this ).replaceWith( wb.ui.SiteLinksEditTool.getEmptyStructure() );
			} );

			$( 'table.wb-sitelinks' ).each( function() {
				// actual initialization
				new wb.ui.SiteLinksEditTool( $( this ) );
			} );
		} else {
			// site-links are only editable if item exists, not on 'Special:CreateItem'
			$( '.wb-sitelinks-empty' ).remove();
		}


		if( mw.util.getParamValue( 'wbitemcreated' ) == 'yes' ) {
			// Display notification if the item was created on 'Special:CreateItem' and we just redirected from there
			// TODO: the parameter should be removed somehow, otherwise on a page reload it will still appear
			var notification = $( '<div>', {
				'id': 'wb-specialcreateitem-newitemnotification',
				'class': 'successbox',
				'text': mw.msg(
					'wb-special-createitem-new-item-notification',
					'q' + mw.config.get( 'wbItemId' ),
					'@@@@' // link to 'Special:CreateItem'
				)
			} ).hide();

			notification.html( notification.text().replace(
				"@@@@", '<a href="' + ( new mw.Title( 'Special:CreateItem' ) ).getUrl() + '">' + mw.msg( 'special-createitem' ) + '</a>'
			) );

			if( $( '#siteNotice' ).length ) {
				notification.insertAfter( $( '#siteNotice' ) ).fadeIn();
			} else {
				notification.prependTo( $( '#content' ) ).fadeIn();
			}
		}

		// handle edit restrictions
		// TODO/FIXME: most about this system sucks, especially the part where the Button constructor is hacked to disable
		//             all buttons when this is fired. it also doesn't effect any edit tools added after this point and
		//             edit tool initialized above do not even know that they are disabled.
		if (
			mw.config.get( 'wgRestrictionEdit' ) !== null &&
			mw.config.get( 'wgRestrictionEdit' ).length === 1
		) { // editing is restricted
			if (
				$.inArray(
					mw.config.get( 'wgRestrictionEdit' )[0],
					mw.config.get( 'wgUserGroups' )
				) === -1
			) {
				// user is not allowed to edit
				$( wikibase ).triggerHandler( 'restrictItemPageActions' );
			}
		}

		if ( mw.config.get( 'wbUserIsBlocked' ) ) {
			$( wikibase ).triggerHandler( 'blockItemPageActions' );
		}

		if( !mw.config.get( 'wbIsEditView' ) ) {
			// no need to implement a 'disableItemPageActions' since hiding all the toolbars directly like this is
			// not really worse than hacking the Toolbar prototype to achieve this:
			$( '.wb-ui-propertyedittool .wb-ui-toolbar' ).hide();
			$( 'body' ).addClass( 'wb-editing-disabled' );
			// make it even harder to edit stuff, e.g. if someone is trying to be smart, using
			// firebug to show hidden nodes again to click on them:
			$( wikibase ).triggerHandler( 'restrictItemPageActions' );
		}

	} );

} )( jQuery, mediaWiki, wikibase );
