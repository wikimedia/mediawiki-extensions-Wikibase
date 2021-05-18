const assert = require( 'assert' ),
	Api = require( 'wdio-mediawiki/Api' ),
	DataBridgePage = require( '../pageobjects/dataBridge.page' ),
	WikibaseApi = require( 'wdio-wikibase/wikibase.api' ),
	LoginPage = require( 'wdio-mediawiki/LoginPage' );

function blockUser( username, expiry ) {
	browser.call( () => Api.bot().then( ( bot ) => {
		return bot.request( {
			action: 'block',
			user: username || browser.config.mwUser,
			reason: 'browser test',
			token: bot.editToken,
			expiry,
		} );
	} ) );
}

function unblockUser( username ) {
	browser.call( () => Api.bot().then( ( bot ) => {
		return bot.request( {
			action: 'unblock',
			user: username || browser.config.mwUser,
			reason: 'browser test done',
			token: bot.editToken,
		} );
	} ) );
}

describe( 'permission checks', () => {
	let title, propertyId, entityId;

	beforeEach( () => {
		browser.deleteCookies();
		title = DataBridgePage.getDummyTitle();
		propertyId = browser.call( () => WikibaseApi.getProperty( 'string' ) );
		entityId = browser.call( () => WikibaseApi.createItem( 'data bridge browser test item', {
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
		const content = DataBridgePage.createInfoboxWikitext( [ {
			label: 'official website',
			entityId,
			propertyId,
			editFlow: 'single-best-value',
		} ] );
		browser.call( () => Api.bot().then( ( bot ) => bot.edit( title, content ) ) );
	} );

	describe( 'if the client page is editable for the user', () => {
		it( 'show the editlink', () => {
			DataBridgePage.open( title );
			assert.ok( DataBridgePage.overloadedLink.isDisplayed() );
		} );
	} );

	describe( 'if the client page is not editable for the user', () => {
		it( 'hide the editlink', () => {
			// Protect the page
			browser.call(
				() => Api.bot().then( ( bot ) => {
					return bot.request( {
						action: 'protect',
						title,
						token: bot.editToken,
						reason: 'browser test',
						user: browser.config.mwUser,
						protections: 'edit=sysop|move=sysop',
					} );
				} )
			);
			// logout
			browser.deleteCookies();

			DataBridgePage.open( title );
			assert.ok( DataBridgePage.overloadedLink.isExisting() );
			assert.ok( !DataBridgePage.overloadedLink.isDisplayed() );
		} );
	} );

	describe( 'if the item is protected on the repo', () => {
		it( 'show a permission error when opening bridge', () => {
			browser.call( () => WikibaseApi.protectEntity( entityId ) );
			// logout
			browser.deleteCookies();

			DataBridgePage.openAppOnPage( title );
			DataBridgePage.error.waitForDisplayed();

			assert.ok( DataBridgePage.showsErrorPermission() );
			assert.equal( DataBridgePage.permissionErrors.length, 1 );
			assert.ok( !DataBridgePage.bridge.isDisplayed() );
		} );
	} );

	describe( 'if the user is blocked on the client', () => {
		beforeEach( () => {
			blockUser();
		} );

		afterEach( () => {
			unblockUser();
		} );

		it( 'show a permission error when opening bridge', () => {
			LoginPage.loginAdmin();
			DataBridgePage.openAppOnPage( title );
			DataBridgePage.error.waitForDisplayed();

			assert.ok( DataBridgePage.showsErrorPermission() );
			// client and repo on the same installation so we expect a "blocked user" from both
			assert.strictEqual( DataBridgePage.permissionErrors.length, 2 );
			assert.ok( !DataBridgePage.bridge.isDisplayed() );
		} );
	} );
} );
