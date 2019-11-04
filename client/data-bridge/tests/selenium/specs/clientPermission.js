const assert = require( 'assert' ),
	Api = require( 'wdio-mediawiki/Api' ),
	DataBridgePage = require( '../pageobjects/dataBridge.page' ),
	WikibaseApi = require( 'wdio-wikibase/wikibase.api' );

describe( 'client permission', () => {
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

	it( 'has an editlink, if the page is editable for the user', () => {
		DataBridgePage.open( title );
		assert.ok( DataBridgePage.overloadedLink.isDisplayed() );
	} );

	it( 'hides the editlink, if the page is not editable for the user', () => {
		// Protect the page
		browser.call(
			() => Api.bot(
				browser.config.username,
				browser.config.password,
				browser.options.baseUrl
			).then( ( bot ) => {
				return bot.request( {
					action: 'protect',
					title,
					token: bot.editToken,
					reason: 'browser test',
					user: browser.config.username,
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
