const Api = require( 'wdio-mediawiki/Api' ),
	DataBridgePage = require( '../pageobjects/dataBridge.page' ),
	WikibaseApi = require( 'wdio-wikibase/wikibase.api' );

describe( 'App', () => {
	let title;

	before( 'set up test item and page', () => {
		title = DataBridgePage.getDummyTitle();
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
		browser.call( () => Api.bot().then( ( bot ) => bot.edit( title, content ) ) );
	} );

	beforeEach( 'open bridge', () => {
		DataBridgePage.openBridgeOnPage( title );
	} );

	it( 'closes on Escape-key event', () => {
		browser.waitUntil(
			() => {
				browser.keys( 'Escape' );
				return !DataBridgePage.app.isExisting();
			},
			5000,
			'App didn\'t close after push ESC key'
		);
	} );

	it( 'closes on clicking the cancel button', () => {
		DataBridgePage.cancelButton.click();

		DataBridgePage.app.waitForExist( 5000, true, 'App still exists in the DOM after clicking the cancel button' );
	} );
} );
