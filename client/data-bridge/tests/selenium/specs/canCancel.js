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
		const content = DataBridgePage.createInfoboxWikitext( [ {
			label: 'official website',
			entityId,
			propertyId,
			editFlow: 'single-best-value',
		} ] );
		browser.call( () => Api.bot().then( ( bot ) => bot.edit( title, content ) ) );
	} );

	beforeEach( 'open bridge', () => {
		DataBridgePage.openAppOnPage( title );
	} );

	it( 'closes on clicking the close button', () => {
		browser.waitUntil( () => DataBridgePage.closeButton.isClickable() );
		DataBridgePage.closeButton.click();

		DataBridgePage.app.waitForExist( {
			reverse: true,
			timeoutMsg: 'App still exists in the DOM after clicking the close button',
		} );
	} );
} );
