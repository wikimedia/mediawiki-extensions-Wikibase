const assert = require( 'assert' ),
	Api = require( 'wdio-mediawiki/Api' ),
	DataBridgePage = require( '../pageobjects/dataBridge.page' ),
	WikibaseApi = require( 'wdio-wikibase/wikibase.api' ),
	NetworkUtil = require( './../NetworkUtil' );

describe( 'App', () => {
	it( 'shows ErrorUnknown when launching bridge for a non-existent entity', () => {
		const title = DataBridgePage.getDummyTitle();
		const propertyId = browser.call( () => WikibaseApi.getProperty( 'string' ) );
		const nonExistentEntityId = 'Q999999999';
		const content = DataBridgePage.createInfoboxWikitext( [ {
			label: 'shows the occurrence of errors',
			entityId: nonExistentEntityId,
			propertyId,
			editFlow: 'overwrite',
		} ] );

		browser.call( () => Api.bot().then( ( bot ) => bot.edit( title, content ) ) );

		DataBridgePage.openBridgeOnPage( title );

		DataBridgePage.error.waitForDisplayed( 5000 );
		assert.ok( DataBridgePage.error.isDisplayed() );
		assert.ok( DataBridgePage.showsErrorUnknown() );

		const errorText = DataBridgePage.error.getText();
		assert.ok( errorText.match( new RegExp( propertyId ) ) );
		assert.ok( errorText.match( new RegExp( nonExistentEntityId ) ) );
	} );

	it( 'can be relaunched from ErrorUnknown', () => {
		const title = DataBridgePage.getDummyTitle();
		const propertyId = browser.call( () => WikibaseApi.getProperty( 'string' ) );
		const entityId = browser.call( () => WikibaseApi.createItem( 'data bridge browser test item', {
			'claims': [ {
				'mainsnak': {
					'snaktype': 'value',
					'property': propertyId,
					'datavalue': { 'value': 'foo bar baz', 'type': 'string' },
				},
				'type': 'statement',
				'rank': 'normal',
			} ],
		} ) );
		const content = DataBridgePage.createInfoboxWikitext( [ {
			label: 'prevail at last',
			entityId,
			propertyId,
			editFlow: 'overwrite',
		} ] );
		browser.call( () => Api.bot().then( ( bot ) => bot.edit( title, content ) ) );

		DataBridgePage.open( title );

		NetworkUtil.disableNetwork();
		DataBridgePage.overloadedLink.click();
		DataBridgePage.error.waitForDisplayed();

		assert.ok( DataBridgePage.showsErrorUnknown() );

		NetworkUtil.enableNetwork();
		DataBridgePage.errorUnknownRelaunch.click();
		DataBridgePage.bridge.waitForDisplayed();
	} );

	it( 'shows ErrorSaving when losing internet connection before saving', () => {
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
		const content = DataBridgePage.createInfoboxWikitext( [ {
			label: 'official website',
			entityId,
			propertyId,
			editFlow: 'overwrite',
		} ] );
		browser.call( () => Api.bot().then( ( bot ) => bot.edit( title, content ) ) );

		DataBridgePage.openBridgeOnPage( title );

		DataBridgePage.bridge.waitForDisplayed( 5000 );
		assert.ok( DataBridgePage.bridge.isDisplayed() );

		const newValue = 'newValue';
		browser.waitUntil(
			() => {
				DataBridgePage.value.setValue( newValue );
				return DataBridgePage.value.getValue() === newValue;
			}
		);

		DataBridgePage.editDecision( 'replace' ).click();

		// show License
		DataBridgePage.saveButton.click();
		DataBridgePage.licensePopup.waitForDisplayed();

		// lose internet connection
		NetworkUtil.disableNetwork();

		// actually trigger save
		DataBridgePage.saveButton.click();

		DataBridgePage.error.waitForDisplayed();
		assert.ok( DataBridgePage.showsErrorSaving() );
	} );
} );
