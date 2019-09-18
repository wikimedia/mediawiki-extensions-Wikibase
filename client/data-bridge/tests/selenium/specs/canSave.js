const assert = require( 'assert' ),
	Api = require( 'wdio-mediawiki/Api' ),
	DataBridgePage = require( '../pageobjects/dataBridge.page' ),
	WikibaseApi = require( 'wdio-wikibase/wikibase.api' );

describe( 'App', () => {
	it( 'can save', () => {
		const title = DataBridgePage.getDummyTitle();
		const propertyId = browser.call( () => WikibaseApi.getProperty( 'string' ) );
		const stringPropertyExampleValue = 'initialValue';
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

		const newValue = 'newValue';
		browser.waitUntil(
			() => {
				DataBridgePage.value.setValue( newValue );
				return DataBridgePage.value.getValue() === newValue;
			}
		);

		DataBridgePage.saveButton.click();

		browser.waitUntil(
			() => {
				return !DataBridgePage.app.isExisting();
			},
			5000,
			'App didn\'t close after clicking save button'
		);

		const entity = browser.call( () => WikibaseApi.getEntity( entityId ) );
		const actualValueAtServer = entity.claims[ propertyId ][ 0 ].mainsnak.datavalue.value;

		assert.strictEqual( actualValueAtServer, newValue );
	} );
} );
