var assert = require( 'assert' ),
	NonExistingItemPage = require( 'wdio-wikibase/pageobjects/nonexisting.item' );

describe( 'WikibaseRepoNonExistingItemPage', function () {

	it( 'edit tab does should not be there', function () {
		NonExistingItemPage.open();

		assert.strictEqual( NonExistingItemPage.editTab.isExisting(), false );
	} );

	it( 'the title should match', function () {
		var fullTitle, title;

		NonExistingItemPage.open();

		fullTitle = NonExistingItemPage.title.getText();
		title = fullTitle.substring( fullTitle.indexOf( ':' ) + 1 );

		assert.strictEqual( title, 'Q999999999' );
	} );

} );
