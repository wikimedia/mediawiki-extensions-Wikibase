'use strict';

const assert = require( 'assert' );

const NonExistingItemPage = require( 'wdio-wikibase/pageobjects/nonexisting.item' );

describe( 'WikibaseRepoNonExistingItemPage', function () {

	it( 'edit tab does should not be there', function () {
		NonExistingItemPage.open();

		assert.strictEqual( NonExistingItemPage.editTab.isExisting(), false );
	} );

	it( 'the title should match', function () {

		NonExistingItemPage.open();

		const fullTitle = NonExistingItemPage.title.getText();
		const title = fullTitle.slice( fullTitle.indexOf( ':' ) + 1 );

		assert.strictEqual( title, 'Q999999999' );
	} );

} );
