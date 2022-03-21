const assert = require( 'assert' ),
	Api = require( 'wdio-mediawiki/Api' ),
	LoginPage = require( 'wdio-mediawiki/LoginPage' ),
	DataBridgePage = require( '../pageobjects/dataBridge.page' ),
	ErrorSavingAssertUser = require( '../pageobjects/ErrorSavingAssertUser' ),
	ErrorSavingEditConflict = require( '../pageobjects/ErrorSavingEditConflict' ),
	WarningAnonymousEdit = require( '../pageobjects/WarningAnonymousEdit' ),
	WikibaseApi = require( 'wdio-wikibase/wikibase.api' ),
	DomUtil = require( './../DomUtil' ),
	NetworkUtil = require( './../NetworkUtil' ),
	WindowUtil = require( './../WindowUtil' );

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

		DataBridgePage.openAppOnPage( title );

		DataBridgePage.error.waitForDisplayed();
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
		DataBridgePage.launchApp();
		DataBridgePage.error.waitForDisplayed( { timeout: browser.config.nonApiTimeout } );

		assert.ok( DataBridgePage.showsErrorUnknown() );

		NetworkUtil.enableNetwork();
		DataBridgePage.errorUnknownRelaunch.click();
		DataBridgePage.app.waitForDisplayed( { timeout: browser.config.nonApiTimeout } );
		WarningAnonymousEdit.dismiss();
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

		DataBridgePage.openAppOnPage( title );

		DataBridgePage.bridge.waitForDisplayed();
		assert.ok( DataBridgePage.bridge.isDisplayed() );

		const newValue = 'newValue';
		DomUtil.setValue( DataBridgePage.value, newValue );

		DataBridgePage.editDecision( 'replace' ).click();

		// show License
		DataBridgePage.saveButton.click();
		DataBridgePage.licensePopup.waitForDisplayed( { timeout: browser.config.nonApiTimeout } );

		// lose internet connection
		NetworkUtil.disableNetwork();

		// actually trigger save
		DataBridgePage.saveButton.click();

		// show ErrorSaving screen
		DataBridgePage.error.waitForDisplayed( { timeout: browser.config.nonApiTimeout } );

		assert.ok( DataBridgePage.showsErrorSaving() );

		// restore internet connection
		NetworkUtil.enableNetwork();
		DataBridgePage.retrySaveButton.click();
		DataBridgePage.thankYouScreen.waitForDisplayed();
	} );

	it( 'can go back from a save error on desktop', () => {
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

		DataBridgePage.openAppOnPage( title );

		DataBridgePage.bridge.waitForDisplayed();
		assert.ok( DataBridgePage.bridge.isDisplayed() );

		const newValue = 'newValue';
		DomUtil.setValue( DataBridgePage.value, newValue );

		DataBridgePage.editDecision( 'replace' ).click();

		// show License
		DataBridgePage.saveButton.click();
		DataBridgePage.licensePopup.waitForDisplayed( { timeout: browser.config.nonApiTimeout } );

		// lose internet connection
		NetworkUtil.disableNetwork();

		// actually trigger save
		DataBridgePage.saveButton.click();

		// show ErrorSaving screen
		DataBridgePage.error.waitForDisplayed();

		assert.ok( DataBridgePage.showsErrorSaving() );

		// ensure that we are on desktop
		DataBridgePage.setMobileWindowSize( false );

		assert.ok( DataBridgePage.errorSavingBackButton.isDisplayed() );
		assert.ok( !DataBridgePage.headerBackButton.isDisplayed() );
		DataBridgePage.errorSavingBackButton.click();

		DataBridgePage.value.waitForDisplayed();
		assert.equal( DataBridgePage.value.getValue(), newValue );
	} );

	describe( 'when assertuser fails', () => {
		beforeEach( 'login, run bridge, logout, trigger error', () => {
			// log in
			LoginPage.loginAdmin();

			// prepare Bridge for saving
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

			DataBridgePage.openAppOnPage( title );
			DataBridgePage.bridge.waitForDisplayed();

			const newValue = 'newValue';
			DomUtil.setValue( DataBridgePage.value, newValue );

			DataBridgePage.editDecision( 'replace' ).click();

			DataBridgePage.saveButton.click();
			DataBridgePage.licensePopup.waitForDisplayed( { timeout: browser.config.nonApiTimeout } );

			// log out
			browser.deleteCookies();

			// trigger error
			DataBridgePage.saveButton.click();
			DataBridgePage.error.waitForDisplayed();
		} );

		it( 'can retry saving without assertuser', () => {
			assert.ok( ErrorSavingAssertUser.isDisplayed() );

			// go back, try again
			ErrorSavingAssertUser.clickBackButton();
			DataBridgePage.saveButton.click();
			DataBridgePage.licensePopup.waitForDisplayed( { timeout: browser.config.nonApiTimeout } );
			DataBridgePage.saveButton.click();
			DataBridgePage.error.waitForDisplayed();

			assert.ok( ErrorSavingAssertUser.isDisplayed() );

			// save without logging in
			ErrorSavingAssertUser.proceedButton.click();

			DataBridgePage.thankYouScreen.waitForDisplayed();
		} );

		it( 'can login and retry saving', () => {
			assert.ok( ErrorSavingAssertUser.isDisplayed() );

			// log in
			ErrorSavingAssertUser.loginButton.click();
			WindowUtil.doInOtherWindow( () => {
				LoginPage.username.waitForDisplayed();
				DomUtil.setValue( LoginPage.username, browser.config.mwUser );
				DomUtil.setValue( LoginPage.password, browser.config.mwPwd );
				LoginPage.loginButton.click();
				LoginPage.username.waitForDisplayed( {
					reverse: true,
				} );
			} );

			// app should have returned from error in the meantime
			assert.ok( !DataBridgePage.error.isExisting() );

			// try again
			DataBridgePage.saveButton.click();
			DataBridgePage.licensePopup.waitForDisplayed();
			DataBridgePage.saveButton.click();

			DataBridgePage.thankYouScreen.waitForDisplayed();
		} );

		it( 'still asserts user after logging in', () => {
			assert.ok( ErrorSavingAssertUser.isDisplayed() );

			// click login button, but close tab without logging in
			ErrorSavingAssertUser.loginButton.click();
			WindowUtil.doInOtherWindow( () => {
				LoginPage.username.waitForDisplayed();
			} );

			// app should have returned from error in the meantime
			assert.ok( !DataBridgePage.error.isExisting() );

			// try again
			DataBridgePage.saveButton.click();
			DataBridgePage.licensePopup.waitForDisplayed();
			DataBridgePage.saveButton.click();

			// should show error again
			DataBridgePage.error.waitForDisplayed();
			assert.ok( ErrorSavingAssertUser.isDisplayed() );
		} );

		it( 'shows custom Bridge warning on login page', () => {
			assert.ok( ErrorSavingAssertUser.isDisplayed() );

			// go to login page
			ErrorSavingAssertUser.loginButton.click();
			WindowUtil.doInOtherWindow( () => {
				LoginPage.username.waitForDisplayed();
				/*
				 * The login page could be displayed in any language,
				 * so we can’t assert a particular text, but we can
				 * look for the <strong> part of our message.
				 */
				assert.ok( browser.$( '.mw-message-box-warning strong' ).isDisplayed() );
			} );
		} );
	} );

	describe( 'when there is an edit conflict', () => {
		beforeEach( 'run bridge, clear item, trigger error', () => {
			// prepare Bridge for saving
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

			DataBridgePage.openAppOnPage( title );
			DataBridgePage.bridge.waitForDisplayed();

			const newValue = 'newValue';
			DomUtil.setValue( DataBridgePage.value, newValue );

			DataBridgePage.editDecision( 'replace' ).click();

			DataBridgePage.saveButton.click();
			DataBridgePage.licensePopup.waitForDisplayed( { timeout: browser.config.nonApiTimeout } );

			// clear the item, removing the target statement
			browser.call( () => Api.bot().then( ( bot ) => bot.request( {
				action: 'wbeditentity',
				id: entityId,
				token: bot.editToken,
				data: '{}',
				clear: 1,
			} ) ) );

			// trigger error
			DataBridgePage.saveButton.click();
			DataBridgePage.error.waitForDisplayed();
		} );

		it( 'reloads on reload button click', () => {
			assert.ok( ErrorSavingEditConflict.isDisplayed() );

			ErrorSavingEditConflict.reloadButton.click();

			DataBridgePage.app.waitForDisplayed( {
				reverse: true,
			} );
		} );

		it( 'reloads on close button click', () => {
			assert.ok( ErrorSavingEditConflict.isDisplayed() );

			DataBridgePage.closeButton.click();

			DataBridgePage.app.waitForDisplayed( {
				reverse: true,
			} );
		} );
	} );

} );
