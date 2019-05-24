const Util = require( 'wdio-mediawiki/Util' );

let WikibaseApi, EntityPage, ItemPage;
try {
	WikibaseApi = require( 'wdio-wikibase/wikibase.api' );
	EntityPage = require( 'wdio-wikibase/pageobjects/entity.page' );
	ItemPage = require( 'wdio-wikibase/pageobjects/item.page' );
} catch ( e ) {
	WikibaseApi = require( '../wdio-wikibase/wikibase.api' );
	EntityPage = require( '../wdio-wikibase/pageobjects/entity.page' );
	ItemPage = require( 'wdio-wikibase/pageobjects/item.page' );
}

describe( 'item', function () {

	const MAIN_STATEMENTS = 'div.wikibase-entityview-main > .wikibase-statementgrouplistview ',
		PROPERTY_INPUT = '.ui-entityselector-input ',
		VALUE_INPUT = '.valueview-input ',
		QUALIFIERS = '.wikibase-statementview-qualifiers ',
		REFERENCES = '.wikibase-statementview-references ',
		NTH_ITEM = ( n ) => `.wikibase-listview > .listview-item:nth-child(${n}) `;

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
		$( MAIN_STATEMENTS + PROPERTY_INPUT ).waitForVisible();
		browser.keys( propertyId );
		// value input automatically focused
		$( MAIN_STATEMENTS + VALUE_INPUT ).waitForVisible();
		browser.keys( 'main value' );

		// move focus to “add qualifier” and activate link
		browser.keys( [ 'Tab' ] );
		browser.keys( [ 'Enter' ] );
		// property input automatically focused
		$( MAIN_STATEMENTS + QUALIFIERS + NTH_ITEM( 1 ) + PROPERTY_INPUT ).waitForVisible();
		browser.keys( propertyId );
		// value input automatically focused
		$( MAIN_STATEMENTS + QUALIFIERS + NTH_ITEM( 1 ) + VALUE_INPUT ).waitForExist();
		browser.keys( 'qualifier 1' );

		browser.waitUntil( () => {
			return ItemPage.saveButtonEnabled;
		} );

		// move focus to “add qualifier” and activate link
		// (first Tab skips over link to remove current qualifier)
		browser.keys( [ 'Tab', 'Tab' ] );
		browser.keys( [ 'Enter' ] ); // this should *not* save the statement (T154869)
		// property input automatically focused
		$( MAIN_STATEMENTS + QUALIFIERS + NTH_ITEM( 2 ) + PROPERTY_INPUT ).waitForVisible();
		browser.keys( propertyId );
		// value input automatically focused
		$( MAIN_STATEMENTS + QUALIFIERS + NTH_ITEM( 2 ) + VALUE_INPUT ).waitForExist();
		browser.keys( 'qualifier 2' );

		browser.waitUntil( () => {
			return ItemPage.saveButtonEnabled;
		} );

		// move focus to “add reference” and activate link
		// (first Tab skips over link to remove current qualifier, second one over link to add another qualifier)
		browser.keys( [ 'Tab', 'Tab', 'Tab' ] );
		browser.keys( [ 'Enter' ] ); // this should also not save the statement (T154869)
		// property input automatically focused
		$( MAIN_STATEMENTS + REFERENCES + NTH_ITEM( 1 ) + NTH_ITEM( 1 ) + PROPERTY_INPUT ).waitForVisible();
		browser.keys( propertyId );
		// value input automatically focused
		$( MAIN_STATEMENTS + REFERENCES + NTH_ITEM( 1 ) + NTH_ITEM( 1 ) + VALUE_INPUT ).waitForExist();
		browser.keys( 'reference 1-1' );

		browser.waitUntil( () => {
			return ItemPage.saveButtonEnabled;
		} );

		// focus still on reference value input, can save entire statement from there
		browser.keys( [ 'Enter' ] );

		$( MAIN_STATEMENTS + VALUE_INPUT ).waitForExist( null, true );
	} );

} );
