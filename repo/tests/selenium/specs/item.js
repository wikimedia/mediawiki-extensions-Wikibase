'use strict';

const Util = require( 'wdio-mediawiki/Util' );
const assert = require( 'assert' );
const WikibaseApi = require( 'wdio-wikibase/wikibase.api' );
const EntityPage = require( 'wdio-wikibase/pageobjects/entity.page' );
const ItemPage = require( 'wdio-wikibase/pageobjects/item.page' );
const Page = require( 'wdio-mediawiki/Page' );

describe( 'item', function () {

	it( 'can add a statement using the keyboard', function () {
		// high-level overview:
		// - add statement,
		// - add qualifier,
		// - add second qualifier,
		// - add reference,
		// - save

		const itemId = browser.call( () => WikibaseApi.createItem( Util.getTestString( 'T154869-' ) ) );
		const propertyId = browser.call( () => WikibaseApi.getProperty( 'string' ) );

		EntityPage.open( itemId );

		// begin adding statement (using the mouse)
		ItemPage.addStatementLink.waitForDisplayed();
		ItemPage.addStatementLink.click();
		// enter the main value
		ItemPage.propertyInputField.waitForDisplayed();
		// property field should be automatically focused
		browser.keys( propertyId );
		ItemPage.selectFirstSuggestedEntityOnEntitySelector();

		ItemPage.valueInputField.waitForDisplayed();
		// focus auto moved after selection
		browser.keys( 'main value' );

		// move focus to “add qualifier” and activate link
		browser.keys( [ 'Tab' ] );
		browser.keys( [ 'Enter' ] );
		// property input automatically focused
		const statement = ItemPage.statements[ 0 ];
		ItemPage.getNthQualifierPropertyInput( statement, 0 ).waitForDisplayed();
		// property field should be automatically focused
		browser.keys( propertyId );
		ItemPage.selectFirstSuggestedEntityOnEntitySelector();
		ItemPage.firstQualifier.waitForExist();
		// focus auto moved after selection
		browser.keys( 'qualifier 1' );
		browser.waitUntil( () => ItemPage.isSaveButtonEnabled() );

		// move focus to “add reference” and activate link
		// - first Tab skips over link to remove current qualifier,
		// - second one over link to add another qualifier
		browser.keys( [ 'Tab', 'Tab', 'Tab' ] );
		browser.keys( [ 'Enter' ] ); // this should also not save the statement (T154869)
		// property input automatically focused
		ItemPage.getNthReferencePropertyInput( statement, 0 ).waitForDisplayed();
		// property field should be automatically focused
		browser.keys( propertyId );
		ItemPage.selectFirstSuggestedEntityOnEntitySelector();

		ItemPage.firstReference.waitForExist();
		// focus auto moved after selection
		browser.keys( 'reference 1-1' );
		browser.waitUntil( () => {
			return ItemPage.isSaveButtonEnabled();
		} );

		// focus still on reference value input, can save entire statement from there
		browser.keys( [ 'Enter' ] );
		ItemPage.valueInputField.waitForExist( { reverse: true } );
	} );

	it.skip( 'old revisions do not have an edit link', function () {
		const itemId = browser.call( () => WikibaseApi.createItem( Util.getTestString( 'T95406-' ) ) );
		const entity = browser.call( () => WikibaseApi.getEntity( itemId ) );

		EntityPage.open( itemId );
		ItemPage.editButton.waitForExist();
		ItemPage.editItemDescription( 'revision 1' );
		ItemPage.editButton.waitForExist();

		( new Page() ).openTitle( `Special:EntityPage/${itemId}`, { oldid: entity.lastrevid } );

		// eslint-disable-next-line wdio/no-pause
		browser.pause( 1000 );
		assert( !ItemPage.editButton.isExisting() );
	} );
} );
