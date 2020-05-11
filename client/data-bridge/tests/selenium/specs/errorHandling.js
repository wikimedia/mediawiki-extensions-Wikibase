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
			editFlow: 'single-best-value',
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
			editFlow: 'single-best-value',
		} ] );
		browser.call( () => Api.bot().then( ( bot ) => bot.edit( title, content ) ) );

		DataBridgePage.open( title );

		NetworkUtil.disableNetwork();
		DataBridgePage.overloadedLink.click();
		DataBridgePage.app.waitForDisplayed( 10000 );
		DataBridgePage.dismissWarningAnonymousEdit();
		DataBridgePage.error.waitForDisplayed();

		assert.ok( DataBridgePage.showsErrorUnknown() );

		NetworkUtil.enableNetwork();
		DataBridgePage.errorUnknownRelaunch.click();
		DataBridgePage.app.waitForDisplayed( 10000 );
		DataBridgePage.dismissWarningAnonymousEdit();
		DataBridgePage.bridge.waitForDisplayed();
	} );

	it( 'can retry saving bridge from ErrorSaving', () => {
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

		// show ErrorSaving screen
		DataBridgePage.error.waitForDisplayed();

		assert.ok( DataBridgePage.showsErrorSaving() );

		// restore internet connection
		NetworkUtil.enableNetwork();
		DataBridgePage.retrySaveButton.click();
		DataBridgePage.thankYouScreen.waitForDisplayed();
	} );

	it( 'can go back from a save error both on desktop and mobile', () => {
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

		// show ErrorSaving screen
		DataBridgePage.error.waitForDisplayed();

		assert.ok( DataBridgePage.showsErrorSaving() );

		// ensure check that we are on desktop
		if ( browser.getWindowSize().width <= 500 ) {
			browser.setWindowSize( 800, 600 );
			browser.pause( 1000 ); // wait for resize animations to complete
		}

		assert.ok( DataBridgePage.errorSavingBackButton.isDisplayed() );
		assert.ok( !DataBridgePage.headerBackButton.isDisplayed() );
		DataBridgePage.errorSavingBackButton.click();

		DataBridgePage.value.waitForDisplayed();
		assert.equal( DataBridgePage.value.getValue(), newValue );

		// switch to mobile
		browser.setWindowSize( 300, 740 );
		browser.pause( 1000 ); // wait for resize animations to complete

		// show License
		DataBridgePage.saveButton.click();
		DataBridgePage.licensePopup.waitForDisplayed();

		// actually trigger save
		DataBridgePage.saveButton.click();

		// show ErrorSaving screen
		DataBridgePage.error.waitForDisplayed();

		DataBridgePage.errorSavingBackButton.waitForDisplayed( undefined, true );
		DataBridgePage.headerBackButton.waitForDisplayed();
		DataBridgePage.headerBackButton.click();

		DataBridgePage.value.waitForDisplayed();
		assert.equal( DataBridgePage.value.getValue(), newValue );
	} );

} );
