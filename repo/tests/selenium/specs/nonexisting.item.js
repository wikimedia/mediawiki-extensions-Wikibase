'use strict';

const assert = require( 'assert' );

const NonExistingItemPage = require( 'wdio-wikibase/pageobjects/nonexisting.item' );

describe( 'WikibaseRepoNonExistingItemPage', () => {

	it( 'edit tab does should not be there', async () => {
		await NonExistingItemPage.open();

		assert.strictEqual( await NonExistingItemPage.editTab.isExisting(), false );
	} );

	it( 'the title should match', async () => {
		await NonExistingItemPage.open();

		const fullTitle = await NonExistingItemPage.title.getText();
		const title = fullTitle.slice( fullTitle.indexOf( ':' ) + 1 );

		assert.strictEqual( title, 'Q999999999' );
	} );

} );
