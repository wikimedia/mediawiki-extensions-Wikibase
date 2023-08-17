'use strict';

const Util = require( 'wdio-mediawiki/Util' );
const WikibaseApi = require( 'wdio-wikibase/wikibase.api' );
const ItemPage = require( 'wdio-wikibase/pageobjects/item.page' );

describe( 'WikibaseReferenceOnProtectedPage', () => {

	it( 'can expand collapsed references on a protected page as unprivileged user', async () => {
		const itemId = await WikibaseApi.createItem( Util.getTestString( 'T186006-' ) );
		const propertyId = await WikibaseApi.getProperty( 'string' );

		await ItemPage.open( itemId );
		await ItemPage.addMainStatement( propertyId, 'mval' );
		await ItemPage.addReferenceToNthStatementOfStatementGroup( 0, propertyId, propertyId, 'refval' );
		await WikibaseApi.protectEntity( itemId );
		await ItemPage.open( itemId );

		await $( '.wikibase-statementview-references-container .wikibase-statementview-references-heading a.ui-toggler' ).waitForDisplayed();
		await expect( $( '.wikibase-addtoolbar' ) ).not.toExist( { message: "This shouldn't exist on a protected page!" } );
	} );
} );
