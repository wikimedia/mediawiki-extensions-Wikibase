const Page = require( 'wdio-mediawiki/Page' );

class NonExistingItem extends Page {
	get editTab() { return browser.element( '.ca-edit' ); }

	open() {
		super.openTitle( 'Item:Q1xy' );
	}

	get title() { return browser.element( '.firstHeading' ); }
}

module.exports = new NonExistingItem();
