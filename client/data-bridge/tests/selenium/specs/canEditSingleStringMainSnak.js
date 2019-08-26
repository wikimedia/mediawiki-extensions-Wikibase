const assert = require( 'assert' ),
	Api = require( 'wdio-mediawiki/Api' ),
	DataBridgePage = require( '../pageobjects/dataBridge.page' ),
	WikibaseApi = require( 'wdio-wikibase/wikibase.api' );

describe( 'App', () => {

	it( 'can edit a single string mainsnak value', () => {
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
		browser.call( () => {
			return Api.edit( title, content, browser.config.username, browser.config.password );
		} );

		DataBridgePage.open( title );
		DataBridgePage.overloadedLink.click();
		DataBridgePage.bridge.waitForDisplayed();

		assert.ok( DataBridgePage.bridge.isDisplayed() );
		assert.strictEqual( DataBridgePage.value.getValue(), stringPropertyExampleValue );

		const extraCharacters = ' added characters';
		browser.waitUntil( () => {
			DataBridgePage.value.click();
			return DataBridgePage.value.isFocused();
		} );

		browser.call( () => {
			return browser.keys( extraCharacters );
		} );

		assert.strictEqual( DataBridgePage.value.getValue(), `${stringPropertyExampleValue}${extraCharacters}` );
	} );
} );
