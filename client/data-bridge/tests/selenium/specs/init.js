const assert = require( 'assert' ),
	Api = require( 'wdio-mediawiki/Api' ),
	DataBridgePage = require( '../pageobjects/dataBridge.page' ),
	WikibaseApi = require( 'wdio-wikibase/wikibase.api' );

describe( 'init', () => {
	beforeEach( () => {
		browser.deleteCookie();
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
			const propertyId = browser.call( () => WikibaseApi.getProperty( 'string' ) );
			const nonExistantEntityId = 'Q999999999';
			const editFlow = 'overwrite';
			const content = `{|class="wikitable"
|-
| shows the occurence of errors
| {{#statements:${propertyId}|from=${nonExistantEntityId}}}&nbsp;<span data-bridge-edit-flow="${editFlow}">[https://example.org/wiki/Item:${nonExistantEntityId}?uselang=en#${propertyId} Edit this on Wikidata]</span>
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
			const editFlow = 'overwrite';
			const content = `{|class="wikitable"
|-
| official website
| {{#statements:${propertyId}|from=${entityId}}}&nbsp;<span data-bridge-edit-flow="${editFlow}">[https://example.org/wiki/Item:${entityId}?uselang=en#${propertyId} Edit this on Wikidata]</span>
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
