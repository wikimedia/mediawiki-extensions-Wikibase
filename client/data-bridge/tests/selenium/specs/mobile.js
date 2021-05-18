const assert = require( 'assert' ),
	Api = require( 'wdio-mediawiki/Api' ),
	DataBridgePage = require( '../pageobjects/dataBridge.page' ),
	WikibaseApi = require( 'wdio-wikibase/wikibase.api' ),
	DomUtil = require( './../DomUtil' ),
	NetworkUtil = require( './../NetworkUtil' );

describe( 'On mobile', () => {

	it( 'can go back from a save error', () => {
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
			editFlow: 'single-best-value',
		} ] );
		browser.call( () => Api.bot().then( ( bot ) => bot.edit( title, content ) ) );

		// switch to mobile
		DataBridgePage.setMobileWindowSize();

		DataBridgePage.openAppOnPage( title );

		DataBridgePage.bridge.waitForDisplayed();
		assert.ok( DataBridgePage.bridge.isDisplayed() );

		const newValue = 'newValue';
		DomUtil.setValue( DataBridgePage.value, newValue );

		DataBridgePage.editDecision( 'replace' ).click();

		// show License
		DataBridgePage.saveButton.click();
		DataBridgePage.licensePopup.waitForDisplayed();

		// lose internet connection
		NetworkUtil.disableNetwork();

		// actually trigger save
		DataBridgePage.saveButton.click();

		// show ErrorSaving screen
		DataBridgePage.error.waitForDisplayed();

		assert.ok( DataBridgePage.showsErrorSaving() );

		// show ErrorSaving screen
		DataBridgePage.error.waitForDisplayed();

		DataBridgePage.errorSavingBackButton.waitForDisplayed( {
			reverse: true,
		} );
		DataBridgePage.headerBackButton.waitForDisplayed();
		DataBridgePage.headerBackButton.click();

		DataBridgePage.value.waitForDisplayed();
		assert.equal( DataBridgePage.value.getValue(), newValue );
	} );

} );
