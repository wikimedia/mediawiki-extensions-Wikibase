const Util = require( 'wdio-mediawiki/Util' );
const assert = require( 'assert' );
const WikibaseApi = require( 'wdio-wikibase/wikibase.api' );
const EntityPage = require( 'wdio-wikibase/pageobjects/entity.page' );
const ItemPage = require( 'wdio-wikibase/pageobjects/item.page' );

describe( 'item', function () {

	it.skip( 'can add a statement using the keyboard', function () {
		// high-level overview: add statement, add qualifier, add second qualifier, add reference, save

		const itemId = browser.call( () => WikibaseApi.createItem( Util.getTestString( 'T154869-' ) ) );
		const propertyId = browser.call( () => WikibaseApi.getProperty( 'string' ) );

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

		browser.waitUntil( () => ItemPage.isSaveButtonEnabled() );

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

	// skip this until further investigation of flakiness T227266
	it.skip( 'old revisions do not have an edit link', function () {
		const itemId = browser.call( () => WikibaseApi.createItem( Util.getTestString( 'T95406-' ) ) );

		EntityPage.open( itemId );
		ItemPage.editItemDescription( 'revision 1' );
		ItemPage.goToPreviousRevision();
		assert( !ItemPage.editButton.isExisting() );
	} );
} );
