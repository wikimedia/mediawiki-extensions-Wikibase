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

		assert.ok( DataBridgePage.app.isVisible() );
	} );

	describe( 'Errors-Init-Value-Switch', () => {
		// TODO testing the loading behaviour actually fails,
		// because the tests running are to slow to see the loading components

		it( 'shows the occurence of errors', () => {
			const title = DataBridgePage.getDummyTitle();
			const entityId = 'Q23';
			const propertyId = 'P123';
			const editFlow = 'overwrite';
			const content = `{|class="wikitable"
|-
| official website
| {{#statements:${propertyId}|from=${entityId}}}&nbsp;<span data-bridge-edit-flow="${editFlow}">[https://wikidata.beta.wmflabs.org/wiki/Item:${entityId}?uselang=en#${propertyId} Edit this on Wikidata]</span>
|}`;

			browser.call( () => {
				return Api.edit( title, content );
			} );

			DataBridgePage.open( title );
			DataBridgePage.overloadedLink.click();
			DataBridgePage.error.waitForVisible();

			assert.ok( DataBridgePage.error.isVisible() );
		} );

		it( 'shows the current targetValue', () => {
			const title = DataBridgePage.getDummyTitle();
			const entityId = 'Q11';
			const propertyId = 'P443';
			const editFlow = 'overwrite';
			const content = `{|class="wikitable"
|-
| official website
| {{#statements:${propertyId}|from=${entityId}}}&nbsp;<span data-bridge-edit-flow="${editFlow}">[https://wikidata.beta.wmflabs.org/wiki/Item:${entityId}?uselang=en#${propertyId} Edit this on Wikidata]</span>
|}`;
			browser.call( () => {
				return Api.edit( title, content );
			} );

			DataBridgePage.open( title );
			DataBridgePage.overloadedLink.click();
			DataBridgePage.bridge.waitForVisible();

			assert.ok( DataBridgePage.bridge.isVisible() );
			// TODO test on value
		} );
	} );
} );
