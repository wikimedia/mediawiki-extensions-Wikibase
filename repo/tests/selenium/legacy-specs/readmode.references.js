'use strict';

const Util = require( 'wdio-mediawiki/Util' );
const WikibaseApi = require( 'wdio-wikibase/wikibase.api' );
const ItemPage = require( 'wdio-wikibase/pageobjects/item.page' );

describe( 'WikibaseReferenceOnProtectedPage', function () {

	it( 'can expand collapsed references on a protected page as unprivileged user', function () {
		const itemId = browser.call( () => WikibaseApi.createItem( Util.getTestString( 'T186006-' ) ) );
		const propertyId = browser.call( () => WikibaseApi.getProperty( 'string' ) );

		ItemPage.open( itemId );
		ItemPage.addMainStatement( propertyId, 'mval' );
		ItemPage.addReferenceToNthStatementOfStatementGroup( 0, propertyId, propertyId, 'refval' );
		browser.call( () => WikibaseApi.protectEntity( itemId ) );
		ItemPage.open( itemId );

		$( '.wikibase-statementview-references-container .wikibase-statementview-references-heading a.ui-toggler' ).waitForDisplayed();
		if ( $( '.wikibase-addtoolbar' ).isExisting() ) {
			throw new Error( 'This shouldn\'t exist on a protected page!' );
		}
	} );
} );
