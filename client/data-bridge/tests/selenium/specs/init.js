const assert = require( 'assert' ),
	Api = require( 'wdio-mediawiki/Api' ),
	DataBridgePage = require( '../pageobjects/dataBridge.page' ),
	WikibaseApi = require( 'wdio-wikibase/wikibase.api' );

describe( 'init', () => {
	beforeEach( () => {
		browser.deleteCookies();
	} );

	it( 'opens app in OOUI dialog', () => {
		const title = DataBridgePage.getDummyTitle();
		const propertyId = browser.call( () => WikibaseApi.getProperty( 'string' ) );
		const entityId = browser.call( () => WikibaseApi.createItem( 'data bridge browser test item', {
			'claims': [ {
				'mainsnak': {
					'snaktype': 'value',
					'property': propertyId,
					'datavalue': { 'value': 'ExampleString', 'type': 'string' },
				},
				'type': 'statement',
				'rank': 'normal',
			} ],
		} ) );
		const content = `{|class="wikitable"
|-
| official website
| {{#statements:${propertyId}|from=${entityId}}}&nbsp;<span data-bridge-edit-flow="overwrite">[https://example.org/wiki/Item:${entityId}?uselang=en#${propertyId} Edit this on Wikidata]</span>
|}`;
		browser.call( () => Api.edit( title, content, browser.config.username, browser.config.password ) );

		DataBridgePage.open( title );
		DataBridgePage.overloadedLink.click();
		DataBridgePage.dialog.waitForDisplayed();

		assert.ok( DataBridgePage.app.isDisplayed() );
	} );

	describe( 'Errors-Init-Value-Switch', () => {
		// TODO testing the loading behaviour actually fails,
		// because the tests running are to slow to see the loading components

		it( 'shows the occurence of errors', () => {
			const title = DataBridgePage.getDummyTitle();
			const propertyId = browser.call( () => WikibaseApi.getProperty( 'string' ) );
			const nonExistantEntityId = 'Q999999999';
			const editFlow = 'overwrite';
			const content = `{|class="wikitable"
|-
| shows the occurence of errors
| {{#statements:${propertyId}|from=${nonExistantEntityId}}}&nbsp;<span data-bridge-edit-flow="${editFlow}">[https://example.org/wiki/Item:${nonExistantEntityId}?uselang=en#${propertyId} Edit this on Wikidata]</span>
|}`;

			browser.call( () => Api.edit( title, content, browser.config.username, browser.config.password ) );

			DataBridgePage.open( title );
			DataBridgePage.overloadedLink.click();
			DataBridgePage.error.waitForDisplayed();

			assert.ok( DataBridgePage.error.isDisplayed() );
		} );

		it( 'shows the current targetValue', () => {
			const title = DataBridgePage.getDummyTitle();
			const propertyId = browser.call( () => WikibaseApi.getProperty( 'string' ) );
			const stringPropertyExampleValue = 'ExampleString';
			const entityId = browser.call( () => WikibaseApi.createItem( 'data bridge browser test item', {
				'claims': [ {
					'mainsnak': {
						'snaktype': 'value',
						'property': propertyId,
						'datavalue': { 'value': stringPropertyExampleValue, 'type': 'string' },
					},
					'type': 'statement',
					'rank': 'normal',
				} ],
			} ) );
			const editFlow = 'overwrite';
			const content = `{|class="wikitable"
|-
| official website
| {{#statements:${propertyId}|from=${entityId}}}&nbsp;<span data-bridge-edit-flow="${editFlow}">[https://example.org/wiki/Item:${entityId}?uselang=en#${propertyId} Edit this on Wikidata]</span>
|}`;
			browser.call( () => Api.edit( title, content, browser.config.username, browser.config.password ) );

			DataBridgePage.open( title );
			DataBridgePage.overloadedLink.click();
			DataBridgePage.bridge.waitForDisplayed();

			assert.ok( DataBridgePage.bridge.isDisplayed() );
			assert.strictEqual( DataBridgePage.value.getValue(), stringPropertyExampleValue );
		} );
	} );
} );
