const assert = require( 'assert' ),
	Api = require( 'wdio-mediawiki/Api' ),
	DataBridgePage = require( '../pageobjects/dataBridge.page' ),
	WikibaseApi = require( 'wdio-wikibase/wikibase.api' ),
	LoginPage = require( 'wdio-mediawiki/LoginPage' );

/**
 * ATM WikibaseApi.protectEntity() does not know how to access the wdio5 user and
 * we can't just hard-code how to get an entityTitle from an entity id either
 */
function protectEntity( entityId ) {
	browser.call( () => Api.bot().then( ( bot ) => {
		return bot.request( {
			action: 'wbgetentities',
			format: 'json',
			ids: entityId,
			props: 'info',
		} ).then( ( getEntitiesResponse ) => {
			return getEntitiesResponse.entities[ entityId ].title;
		} ).then( ( entityTitle ) => {
			return bot.request( {
				action: 'protect',
				title: entityTitle,
				token: bot.editToken,
				user: browser.config.mwUser,
				protections: 'edit=autoconfirmed',
			} );
		} );
	} ) );
}

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
	let title, propertyId, entityId, content;

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
		content = `{|class="wikitable"
|-
| official website
| {{#statements:${propertyId}|from=${entityId}}}&nbsp;<span data-bridge-edit-flow="overwrite">[https://example.org/wiki/Item:${entityId}?uselang=en#${propertyId} Edit this on Wikidata]</span>
|}`;
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

	describe( 'if the item is semi-protected on the repo', () => {
		it( 'show a permission error when opening bridge', () => {
			protectEntity( entityId );
			// logout
			browser.deleteCookies();

			DataBridgePage.open( title );
			DataBridgePage.overloadedLink.click();
			DataBridgePage.error.waitForDisplayed( 5000 );

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

		it.only( 'show a permission error when opening bridge', () => {
			LoginPage.loginAdmin();
			DataBridgePage.open( title );
			DataBridgePage.overloadedLink.click();
			DataBridgePage.error.waitForDisplayed( 5000 );

			// client and repo on the same installation so we expect a "blocked user" from both
			assert.strictEqual( DataBridgePage.permissionErrors.length, 2 );
			assert.ok( !DataBridgePage.bridge.isDisplayed() );
		} );
	} );
} );
