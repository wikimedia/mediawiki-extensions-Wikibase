import assert from 'assert';
import { mwbot } from 'wdio-mediawiki/Api.js';
import DataBridgePage from '../pageobjects/dataBridge.page.js';
import WikibaseApi from 'wdio-wikibase/wikibase.api.js';
import * as DomUtil from './../DomUtil.js';
import * as NetworkUtil from './../NetworkUtil.js';

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
		browser.call( () => mwbot().then( ( bot ) => bot.edit( title, content ) ) );

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
