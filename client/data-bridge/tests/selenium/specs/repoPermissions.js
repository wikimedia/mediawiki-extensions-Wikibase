const assert = require( 'assert' ),
	Api = require( 'wdio-mediawiki/Api' ),
	DataBridgePage = require( '../pageobjects/dataBridge.page' ),
	WikibaseApi = require( 'wdio-wikibase/wikibase.api' );

describe( 'repo permissions', () => {
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
		browser.call( () => Api.edit( title, content, browser.config.username, browser.config.password ) );
	} );

	it( 'shows a permission error when opening bridge if the item is semi-protected on the repo', () => {
		/**
		 * ATM WikibaseApi.protectEntity() does not know how to access the wdio5 user and
		 * we can't just hard-code how to get an entityTitle from an entity id either
		 */
		browser.call( () => Api.bot(
			browser.config.username,
			browser.config.password,
			browser.options.baseUrl
		).then( ( bot ) => {
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
					user: browser.config.username,
					protections: 'edit=autoconfirmed',
				} );
			} );
		} ) );
		// logout
		browser.deleteCookies();

		DataBridgePage.open( title );
		DataBridgePage.overloadedLink.click();
		DataBridgePage.error.waitForDisplayed( 5000 );

		assert.equal( DataBridgePage.permissionErrors.length, 1 );
		assert.ok( !DataBridgePage.bridge.isDisplayed() );
	} );
} );
