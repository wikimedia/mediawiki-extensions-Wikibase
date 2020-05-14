const assert = require( 'assert' ),
	Api = require( 'wdio-mediawiki/Api' ),
	DataBridgePage = require( '../pageobjects/dataBridge.page' ),
	WikibaseApi = require( 'wdio-wikibase/wikibase.api' );

describe( 'App', () => {

	it.skip( 'can edit a single string mainsnak value', () => {
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
		const content = DataBridgePage.createInfoboxWikitext( [ {
			label: 'official website',
			entityId,
			propertyId,
			editFlow: 'single-best-value',
		} ] );
		browser.call( () => Api.bot().then( ( bot ) => bot.edit( title, content ) ) );

		DataBridgePage.openAppOnPage( title );

		DataBridgePage.bridge.waitForDisplayed( 5000 );
		assert.ok( DataBridgePage.bridge.isDisplayed() );
		assert.strictEqual( DataBridgePage.value.getValue(), stringPropertyExampleValue );

		const extraCharacters = ' added characters';
		browser.waitUntil( () => {
			DataBridgePage.value.click();
			return DataBridgePage.value.isFocused();
		} );

		browser.keys( extraCharacters );

		const expectedContent = `${stringPropertyExampleValue}${extraCharacters}`;
		browser.waitUntil(
			() => {
				return DataBridgePage.value.getValue() === expectedContent;
			},
			5000,
			`${DataBridgePage.value.getValue()} is not equal to ${expectedContent}`
		);
	} );
} );
