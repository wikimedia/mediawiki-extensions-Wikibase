import { getTestString } from 'wdio-mediawiki/Util.js';
import WikibaseApi from 'wdio-wikibase/wikibase.api.js';
import ItemPage from 'wdio-wikibase/pageobjects/item.page.js';

describe( 'WikibaseReferenceOnProtectedPage', () => {

	it( 'can expand collapsed references on a protected page as unprivileged user', async () => {
		const itemId = await WikibaseApi.createItem( getTestString( 'T186006-' ) );
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
