var assert = require( 'assert' ),
	NonExistingItem = require( '../pageobjects/nonexisting.item' );

describe( 'WikibaseRepoNonExistingItem', function () {

	it( 'edit tab does should not be there', function () {
		NonExistingItem.open();

		assert.strictEqual( NonExistingItem.editTab.isExisting(), false );
	} );

	it( 'the title should match', function () {
		NonExistingItem.open();

		assert.strictEqual( NonExistingItem.title.getText(), 'Item:Q1xy' );
	} );

} );
