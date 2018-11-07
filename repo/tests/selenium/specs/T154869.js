const assert = require( 'assert' ),
	  Page = require( 'wdio-mediawiki/Page' ),
	  Util = require( 'wdio-mediawiki/Util' ),
	  EntityPage = require( '../pageobjects/entity.page' ),
	  WikibaseApi = require( '../wikibase.api' );

describe( 'T154869', function () {

	it( 'code is not English', function () {
		let itemId, propertyId;

		/*
		browser.call( () => {
			return WikibaseApi.createItem( Util.getTestString( 'T154869-' ) )
				.then( ( id ) => {
					itemId = id;
				} );
		} );
		browser.call( () => {
			return WikibaseApi.createProperty( 'string' )
				.then( ( id ) => {
					propertyId = id;
				} );
		} );
		*/
		itemId = 'Q542';
		propertyId = 'P13';

		EntityPage.open( itemId );

		const mainStatementsContainer = () => $( 'div.wikibase-entityview-main > .wikibase-statementgrouplistview' ),
			  addMainStatementLink = () => mainStatementsContainer().$( 'div.wikibase-addtoolbar > span > a' );

		addMainStatementLink().waitForVisible();
		addMainStatementLink().click();

		mainStatementsContainer().$( '.ui-entityselector-input' ).setValue( propertyId );
		mainStatementsContainer().$( '.valueview-input' ).waitForVisible();
		mainStatementsContainer().$( '.valueview-input' ).setValue( 'main value' );

		// add qualifier
		browser.keys( [ 'Tab' ] );
		browser.keys( [ 'Enter' ] );
		mainStatementsContainer().$( '.wikibase-statementview-qualifiers > .wikibase-listview > .listview-item .ui-entityselector-input' ).waitForVisible();
		mainStatementsContainer().$( '.wikibase-statementview-qualifiers > .wikibase-listview > .listview-item .ui-entityselector-input' ).setValue( propertyId );
		mainStatementsContainer().$( '.wikibase-statementview-qualifiers > .wikibase-listview > .listview-item .valueview-input' ).waitForVisible();
		mainStatementsContainer().$( '.wikibase-statementview-qualifiers > .wikibase-listview > .listview-item .valueview-input' ).setValue( 'qualifier 1' );

		mainStatementsContainer().$( '.wikibase-toolbar-button-save' ).waitUntil( function () {
			return mainStatementsContainer().$( '.wikibase-toolbar-button-save' ).getAttribute( 'aria-disabled' ) === 'false';
		} );

		// add another qualifier (first Tab skips over link to remove current qualifier)
		browser.keys( [ 'Tab', 'Tab' ] );
		browser.keys( [ 'Enter' ] );
		mainStatementsContainer().$( '.wikibase-statementview-qualifiers > .wikibase-listview > .listview-item + .listview-item .ui-entityselector-input' ).waitForVisible();
		mainStatementsContainer().$( '.wikibase-statementview-qualifiers > .wikibase-listview > .listview-item + .listview-item .ui-entityselector-input' ).setValue( propertyId );
		mainStatementsContainer().$( '.wikibase-statementview-qualifiers > .wikibase-listview > .listview-item + .listview-item .valueview-input' ).waitForVisible();
		mainStatementsContainer().$( '.wikibase-statementview-qualifiers > .wikibase-listview > .listview-item + .listview-item .valueview-input' ).setValue( 'qualifier 2' );

		mainStatementsContainer().$( '.wikibase-toolbar-button-save' ).waitUntil( function () {
			return mainStatementsContainer().$( '.wikibase-toolbar-button-save' ).getAttribute( 'aria-disabled' ) === 'false';
		} );

		browser.keys( [ 'Enter' ] );

		mainStatementsContainer().$( '.valueview-input' ).waitForExist( null, true );
	} );

} );
