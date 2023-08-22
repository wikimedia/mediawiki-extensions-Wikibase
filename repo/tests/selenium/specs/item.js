'use strict';

const Util = require( 'wdio-mediawiki/Util' );
const WikibaseApi = require( 'wdio-wikibase/wikibase.api' );
const EntityPage = require( 'wdio-wikibase/pageobjects/entity.page' );
const ItemPage = require( 'wdio-wikibase/pageobjects/item.page' );
const Page = require( 'wdio-mediawiki/Page' );

describe( 'item', () => {
	it( 'can add a statement using the keyboard', async () => {
		// high-level overview:
		// - add statement,
		// - add qualifier,
		// - add second qualifier,
		// - add reference,
		// - save

		const itemId = await WikibaseApi.createItem( Util.getTestString( 'T154869-' ) );
		const propertyId = await WikibaseApi.getProperty( 'string' );

		await EntityPage.open( itemId );

		// begin adding statement (using the mouse)
		await ItemPage.addStatementLink.waitForDisplayed();
		await ItemPage.addStatementLink.click();
		// enter the main value
		await ItemPage.propertyInputField.waitForDisplayed();
		// property field should be automatically focused
		await browser.keys( propertyId );
		await ItemPage.selectFirstSuggestedEntityOnEntitySelector();

		await ItemPage.valueInputField.waitForDisplayed();
		// focus auto moved after selection
		await browser.keys( 'main value' );

		// move focus to “add qualifier” and activate link
		await browser.keys( [ 'Tab' ] );
		await browser.keys( [ 'Enter' ] );
		// property input automatically focused
		const statement = ItemPage.statements[ 0 ];
		await ItemPage.getNthQualifierPropertyInput( statement, 0 ).waitForDisplayed();
		// property field should be automatically focused
		await browser.keys( propertyId );
		await ItemPage.selectFirstSuggestedEntityOnEntitySelector();
		await ItemPage.firstQualifier.waitForExist();
		// focus auto moved after selection
		await browser.keys( 'qualifier 1' );
		await browser.waitUntil( () => ItemPage.isSaveButtonEnabled() );

		// move focus to “add reference” and activate link
		// - first Tab skips over link to remove current qualifier,
		// - second one over link to add another qualifier
		await browser.keys( [ 'Tab', 'Tab', 'Tab' ] );
		await browser.keys( [ 'Enter' ] ); // this should also not save the statement (T154869)
		// property input automatically focused
		await ItemPage.getNthReferencePropertyInput( statement, 0 ).waitForDisplayed();
		// property field should be automatically focused
		await browser.keys( propertyId );
		await ItemPage.selectFirstSuggestedEntityOnEntitySelector();

		await ItemPage.firstReference.waitForExist();
		// focus auto moved after selection
		await browser.keys( 'reference 1-1' );
		await browser.waitUntil( () => ItemPage.isSaveButtonEnabled() );

		// focus still on reference value input, can save entire statement from there
		await browser.keys( [ 'Enter' ] );
		await ItemPage.valueInputField.waitForExist( { reverse: true } );

		await expect( $( `#${propertyId}` ) ).toExist();
	} );

	it.skip( 'old revisions do not have an edit link', async () => {
		const itemId = await WikibaseApi.createItem( Util.getTestString( 'T95406-' ) );
		const item = await WikibaseApi.getEntity( itemId );

		await EntityPage.open( itemId );
		await ItemPage.editItemDescription( 'revision 1' );
		await ItemPage.editButton.waitForExist();

		await ( new Page() ).openTitle( `Special:EntityPage/${itemId}`, { oldid: item.lastrevid } );

		await expect( ItemPage.editButton ).not.toExist();
	} );

	it( 'has its label not rendered when linked on a Wikipage', async () => {
		const itemId = await WikibaseApi.createItem( Util.getTestString( 'T111346-' ) );
		await EntityPage.open( itemId );

		const {
			wgPageName: itemTitle,
			wgFormattedNamespaces,
			wgNamespaceNumber
		} = await browser.execute(
			// eslint-disable-next-line no-undef
			() => window.mw.config.get( [ 'wgPageName', 'wgFormattedNamespaces', 'wgNamespaceNumber' ] )
		);
		const talkPageTitle = wgFormattedNamespaces[ wgNamespaceNumber + 1 ] + ':' + itemTitle;

		await ( new Page() ).openTitle( talkPageTitle, { action: 'submit', vehidebetadialog: 1, hidewelcomedialog: 1 } );

		const wpTextbox1 = $( '#wpTextbox1' );
		await wpTextbox1.waitForExist();
		await wpTextbox1.setValue( `[[${itemTitle}]]` );

		// Now the actual action happens: an api request with action=stashedit that caused T111346
		await $( '#wpSummary' ).click();
		await browser.keys( 'typing some letters so the action=stashedit API request can finish' );

		await $( '#wpSave' ).click();
		await expect( $( '#mw-content-text' ) ).toHaveText( itemTitle );
	} );
} );
