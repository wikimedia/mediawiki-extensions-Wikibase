'use strict';

const WikibaseApi = require( 'wdio-wikibase/wikibase.api' );
const Util = require( 'wdio-mediawiki/Util' );
const ItemPage = require( 'wdio-wikibase/pageobjects/item.page' );

describe( 'the Tainted icon', () => {
	let propertyId;
	let itemId;

	before( async () => {
		propertyId = await WikibaseApi.createProperty( 'string', {} );
		itemId = await WikibaseApi.createItem(
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

	// Skipped because of frequent failures, see T266706
	it.skip( 'should appear and disappear correctly', async () => {
		await ItemPage.open( itemId );

		await expect( ItemPage.taintedRefIcon ).not.toExist( {
			message: 'Tainted Icon should not be visible on page load'
		} );

		await ItemPage.editStatementValue( 0, propertyId, Util.getTestString( 'newValue' ) );

		await expect( ItemPage.taintedRefIcon ).toExist( {
			message: 'Tainted Icon should be visible only after changing a statement value'
		} );

		await ItemPage.clickEditOnStatement( 0, propertyId );

		await expect( ItemPage.taintedRefIcon ).not.toExist( {
			wait: 500,
			message: 'Tainted Icon should not be visible on entering edit mode'
		} );

		await ItemPage.clickCancelOnStatement( 0, propertyId );

		await expect( ItemPage.taintedRefIcon ).toExist( {
			message: 'Tainted Icon should be visible after editing canceled on a tainted statement'
		} );
	} );
} );
