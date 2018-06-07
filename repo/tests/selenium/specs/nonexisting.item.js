var assert = require( 'assert' ),
	NonExistingItemPage = require( '../pageobjects/nonexisting.item' );

describe( 'WikibaseRepoNonExistingItemPage', function () {

	it( 'edit tab does should not be there', function () {
		NonExistingItemPage.open();

		assert.strictEqual( NonExistingItemPage.editTab.isExisting(), false );
	} );

	it( 'the title should match', function () {
		NonExistingItemPage.open();

		assert.strictEqual( NonExistingItemPage.title.getText(), 'Item:Q1xy' );
	} );

} );
