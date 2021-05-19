const assert = require( 'assert' ),
	Api = require( 'wdio-mediawiki/Api' ),
	DataBridgePage = require( '../pageobjects/dataBridge.page' ),
	WikibaseApi = require( 'wdio-wikibase/wikibase.api' ),
	DomUtil = require( './../DomUtil' );

function createTag( tagname, reason ) {
	return Api.bot().then( ( bot ) => bot.request( {
		action: 'managetags',
		operation: 'create',
		tag: tagname,
		reason,
		token: bot.editToken,
	} ) );
}

function getLatestRevisionTags( title ) {
	return Api.bot().then( ( bot ) => {
		return bot.request( {
			titles: title,
			action: 'query',
			prop: 'revisions',
			rvprop: 'tags',
			formatversion: '2',
		} ).then( ( response ) => {
			return response.query.pages[ 0 ].revisions[ 0 ].tags;
		} );
	} );
}

function ensureTagExists( bridgeEditTag ) {
	browser.call(
		() => createTag( bridgeEditTag, 'created by selenium browser test' )
			.catch( ( reason ) => {
				if ( reason.code === 'tags-create-already-exists' ) {
					return;
				}
				throw new Error( reason );
			} )
	);
}

function assertSaveButtonDisabled( untilMessage = '' ) {
	assert.ok( !DataBridgePage.saveButton.isClickable(), 'disabled button should not be clickable ' + untilMessage );
	assert.ok( !DataBridgePage.saveButton.isFocused(), 'disabled button should not be focusable' );
	assert.ok( !DataBridgePage.saveButton.isSelected(), 'disabled button should not be selectable' );
}

describe( 'App', () => {
	it( 'can save with tag', () => {
		const bridgeEditTag = 'Data Bridge';
		ensureTagExists( bridgeEditTag );

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
		assert.strictEqual( DataBridgePage.value.getValue(), stringPropertyExampleValue );

		assertSaveButtonDisabled( 'until value was changed' );

		const newValue = 'newValue';
		DomUtil.setValue( DataBridgePage.value, newValue );

		assertSaveButtonDisabled( 'until edit decision was made' );

		DataBridgePage.editDecision( 'replace' ).click();

		// show License
		DataBridgePage.saveButton.click();

		DataBridgePage.licensePopup.waitForDisplayed();

		// actually trigger save
		DataBridgePage.saveButton.click();

		// click “edit reference on repo” button to trigger reload
		DataBridgePage.thankYouScreen.waitForDisplayed();
		DataBridgePage.thankYouButton.click();
		browser.switchWindow( title );

		DataBridgePage.app.waitForDisplayed( {
			timeout: browser.config.nonApiTimeout,
			reverse: true,
			timeoutMsg: 'App is still being displayed after clicking the save button',
		} );
		DataBridgePage.app.waitForExist( {
			timeout: browser.config.nonApiTimeout,
			reverse: true,
			timeoutMsg: 'App still exists in the DOM after clicking the save button',
		} );

		const entity = browser.call( () => WikibaseApi.getEntity( entityId ) );
		const actualValueAtServer = entity.claims[ propertyId ][ 0 ].mainsnak.datavalue.value;

		assert.strictEqual( actualValueAtServer, newValue );

		const actualTags = browser.call(
			() => getLatestRevisionTags( entity.title )
				.catch( assert.fail )
		);
		assert.ok( actualTags.includes( bridgeEditTag ), `${JSON.stringify( actualTags )} doesn't include tag "${bridgeEditTag}"!` );

		assert.strictEqual( DataBridgePage.nthInfoboxValue( 1 ).getText(), newValue );
	} );

	it( 'saves an updated value', () => {
		const title = DataBridgePage.getDummyTitle();
		const propertyId = browser.call( () => WikibaseApi.getProperty( 'string' ) );

		const originalStatements = [ {
			'mainsnak': {
				snaktype: 'value',
				property: propertyId,
				datavalue: { value: 'initialValue', type: 'string' },
				datatype: 'string',
			},
			'type': 'statement',
			'rank': 'normal',
		} ];

		const entityId = browser.call( () => WikibaseApi.createItem( 'data bridge browser test item', {
			'claims': originalStatements,
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

		DataBridgePage.editDecision( 'update' ).click();

		// show License
		DataBridgePage.saveButton.click();
		DataBridgePage.licensePopup.waitForDisplayed();

		DataBridgePage.licenseCloseButton.click();
		DataBridgePage.licensePopup.waitForDisplayed( {
			timeout: null,
			reverse: true,
			timeoutMsg: 'License still visible after being dismissed',
		} );

		// show License again
		DataBridgePage.saveButton.click();
		DataBridgePage.licensePopup.waitForDisplayed();

		// actually trigger save
		DataBridgePage.saveButton.click();

		// click “close” button once “thank you” screen is shown to trigger reload
		DataBridgePage.thankYouScreen.waitForDisplayed();
		DataBridgePage.closeButton.click();

		DataBridgePage.app.waitForDisplayed( {
			timeout: browser.config.nonApiTimeout,
			reverse: true,
			timeoutMsg: 'App is still being displayed after clicking the save button',
		} );

		const entity = browser.call( () => WikibaseApi.getEntity( entityId ) );
		const actualValuesAtServer = entity.claims[ propertyId ];

		// remove server generated properties that we don't care about
		delete actualValuesAtServer[ 0 ].id;
		delete actualValuesAtServer[ 0 ].mainsnak.hash;
		delete actualValuesAtServer[ 1 ].id;
		delete actualValuesAtServer[ 1 ].mainsnak.hash;

		const expectedStatements = [
			...originalStatements,
			{
				type: 'statement',
				rank: 'preferred',
				mainsnak: {
					snaktype: 'value',
					property: propertyId,
					datavalue: { value: newValue, type: 'string' },
					datatype: 'string',
				},
			},
		];
		assert.deepStrictEqual( actualValuesAtServer, expectedStatements );

		assert.strictEqual( DataBridgePage.nthInfoboxValue( 1 ).getText(), newValue );
	} );

} );
