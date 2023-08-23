'use strict';

const NonExistingItemPage = require( 'wdio-wikibase/pageobjects/nonexisting.item' );

describe( 'WikibaseRepoNonExistingItemPage', () => {

	it( 'edit tab does should not be there', async () => {
		await NonExistingItemPage.open();

		await expect( NonExistingItemPage.editTab ).not.toExist();
	} );

	it( 'the title should match', async () => {
		await NonExistingItemPage.open();

		await expect( NonExistingItemPage.title ).toHaveText( 'Item:Q999999999' );
	} );

} );
