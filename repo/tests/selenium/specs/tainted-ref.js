'use strict';

const WikibaseApi = require( 'wdio-wikibase/wikibase.api' );
const Util = require( 'wdio-mediawiki/Util' );
const EntityPage = require( 'wdio-wikibase/pageobjects/entity.page' );
const ItemPage = require( 'wdio-wikibase/pageobjects/item.page' );
const assert = require( 'assert' );

describe( 'the Tainted icon', () => {
	let propertyId;
	let itemId;
	before( () => {
		propertyId = browser.call( () => WikibaseApi.createProperty( 'string', {} ) );
		itemId = browser.call( () => {
			return WikibaseApi.createItem(
				Util.getTestString( 'TaintedRefSelenium-' ),
				{
					claims: [ {
						mainsnak: {
							snaktype: 'value',
							property: propertyId,
							datavalue: { value: 'ExampleValue', type: 'string' }
						},
						type: 'statement',
						rank: 'normal',
						references: [ {
							snaks: {
								[ propertyId ]: [ {
									snaktype: 'value',
									property: propertyId,
									datavalue: { value: 'refString', type: 'string' }
								} ]
							}
						} ]
					} ]
				}
			);
		} );
	} );

	it( 'should appear and disappear correctly', () => {
		EntityPage.open( itemId );

		assert(
			!ItemPage.taintedRefIcon.isExisting(),
			'Tainted Icon should not be visible on page load'
		);

		ItemPage.editStatementValue( 0, propertyId, Util.getTestString( 'newValue' ) );

		assert(
			ItemPage.taintedRefIcon.waitForExist(),
			'Tainted Icon should be visible after only changing a statement value'
		);

		ItemPage.clickEditOnStatement( 0, propertyId );

		assert(
			ItemPage.taintedRefIcon.waitForExist( { timeout: 500, reverse: true } ),
			'Tainted Icon should not be visible on entering edit mode'
		);

		ItemPage.clickCancelOnStatement( 0, propertyId );

		assert(
			ItemPage.taintedRefIcon.waitForExist(),
			'Tainted Icon is visible after canceling edit on a tainted statement '
		);
	} );

} );
