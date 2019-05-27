const Util = require( 'wdio-mediawiki/Util' );

let WikibaseApi, EntityPage, ItemPage;
try {
	WikibaseApi = require( 'wdio-wikibase/wikibase.api' );
	EntityPage = require( 'wdio-wikibase/pageobjects/entity.page' );
	ItemPage = require( 'wdio-wikibase/pageobjects/item.page' );
} catch ( e ) {
	WikibaseApi = require( '../wdio-wikibase/wikibase.api' );
	EntityPage = require( '../wdio-wikibase/pageobjects/entity.page' );
	ItemPage = require( '../wdio-wikibase/pageobjects/item.page' );
}

describe( 'item', function () {

	it( 'can add a statement using the keyboard', function () {
		// high-level overview: add statement, add qualifier, add second qualifier, add reference, save
		let itemId, propertyId;

		browser.call( () => {
			return WikibaseApi.createItem( Util.getTestString( 'T154869-' ) )
				.then( ( id ) => {
					itemId = id;
				} );
		} );
		browser.call( () => {
			return WikibaseApi.getProperty( 'string' )
				.then( ( id ) => {
					propertyId = id;
				} );
		} );

		EntityPage.open( itemId );

		// begin adding statement (using the mouse)
		ItemPage.addStatementLink.waitForVisible();
		ItemPage.addStatementLink.click();
		// enter the main value
		// property input automatically foclused
		ItemPage.propertyInputField.waitForVisible();
		browser.keys( propertyId );
		// value input automatically focused
		ItemPage.valueInputField.waitForVisible();
		browser.keys( 'main value' );

		// move focus to “add qualifier” and activate link
		browser.keys( [ 'Tab' ] );
		browser.keys( [ 'Enter' ] );
		// property input automatically focused
		let statement = ItemPage.statements[ 0 ];
		ItemPage.getNthQualifierPropertyInput( statement, 0 ).waitForVisible();
		browser.keys( propertyId );
		// value input automatically focused
		ItemPage.getNthQualifierValueInput( statement, 0 ).waitForExist();
		browser.pause( 1000 );
		browser.keys( 'qualifier 1' );
		browser.pause( 1000 );

		browser.waitUntil( () => {
			return ItemPage.isSaveButtonEnabled();
		} );

		// move focus to “add qualifier” and activate link
		// (first Tab skips over link to remove current qualifier)
		browser.keys( [ 'Tab', 'Tab' ] );
		browser.keys( [ 'Enter' ] ); // this should *not* save the statement (T154869)
		// property input automatically focused
		ItemPage.getNthQualifierPropertyInput( statement, 1 ).waitForVisible();
		browser.keys( propertyId );
		// value input automatically focused
		ItemPage.getNthQualifierValueInput( statement, 1 ).waitForExist();
		browser.pause( 1000 );
		browser.keys( 'qualifier 2' );
		browser.pause( 1000 );
		browser.waitUntil( () => {
			return ItemPage.isSaveButtonEnabled();
		} );

		// move focus to “add reference” and activate link
		// (first Tab skips over link to remove current qualifier, second one over link to add another qualifier)
		browser.keys( [ 'Tab', 'Tab', 'Tab' ] );
		browser.keys( [ 'Enter' ] ); // this should also not save the statement (T154869)
		// property input automatically focused
		ItemPage.getNthReferencePropertyInput( statement, 0 ).waitForVisible();
		browser.keys( propertyId );
		// value input automatically focused
		ItemPage.getNthReferenceValueInput( statement, 0 ).waitForExist();
		browser.pause( 1000 );
		// value input automatically focused
		browser.keys( 'reference 1-1' );
		browser.pause( 1000 );
		browser.waitUntil( () => {
			return ItemPage.isSaveButtonEnabled();
		} );

		// focus still on reference value input, can save entire statement from there
		browser.keys( [ 'Enter' ] );
		ItemPage.valueInputField.waitForExist( null, true );
	} );

} );
