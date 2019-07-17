const assert = require( 'assert' ),
	Api = require( 'wdio-mediawiki/Api' ),
	DataBridgePage = require( '../pageobjects/dataBridge.page' );

describe( 'init', () => {
	beforeEach( () => {
		browser.deleteCookie();
	} );

	it( 'opens app in OOUI dialog', () => {
		const title = DataBridgePage.getDummyTitle();
		const content = `{|class="wikitable"
|-
| official website
| {{#statements:P443|from=Q11}}&nbsp;<span data-bridge-edit-flow="overwrite">[https://wikidata.beta.wmflabs.org/wiki/Item:Q11?uselang=en#P443 Edit this on Wikidata]</span>
|}`;

		browser.call( () => {
			return Api.edit( title, content );
		} );

		DataBridgePage.open( title );
		DataBridgePage.overloadedLink.click();
		DataBridgePage.dialog.waitForVisible();

		assert.strictEqual(
			DataBridgePage.app.$( 'h1' ).getText(),
			'Welcome to Your Vue.js + TypeScript App'
		);
	} );

} );
