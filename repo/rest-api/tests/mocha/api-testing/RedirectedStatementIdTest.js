'use strict';

const { assert, utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const rbf = require( '../helpers/RequestBuilderFactory' );
const {
	newStatementWithRandomStringValue,
	createEntityWithStatements,
	createUniqueStringProperty,
	newLegacyStatementWithRandomStringValue
} = require( '../helpers/entityHelper' );

describe( 'Redirected statementId requests', () => {
	let statementPropertyId;

	let itemId;
	let itemStatementId;
	let lowercaseItemStatementId;
	let propertyId;
	let propertyStatementId;
	let lowercasePropertyStatementId;

	before( async () => {
		statementPropertyId = ( await createUniqueStringProperty() ).entity.id;

		const createItemResponse = await createEntityWithStatements(
			[ newLegacyStatementWithRandomStringValue( statementPropertyId ) ],
			'item'
		);
		itemId = createItemResponse.entity.id;
		itemStatementId = createItemResponse.entity.claims[ statementPropertyId ][ 0 ].id;
		lowercaseItemStatementId = itemStatementId.replace( /^Q/, 'q' );

		const createPropertyResponse = await createEntityWithStatements(
			[ newLegacyStatementWithRandomStringValue( statementPropertyId ) ],
			'property'
		);
		propertyId = createPropertyResponse.entity.id;
		propertyStatementId = createPropertyResponse.entity.claims[ statementPropertyId ][ 0 ].id;
		lowercasePropertyStatementId = propertyStatementId.replace( /^P/, 'p' );
	} );

	[
		{
			newRequestBuilder: () => rbf.newGetStatementRequestBuilder( lowercaseItemStatementId ),
			expectedStatementId: () => itemStatementId
		},
		{
			newRequestBuilder: () => rbf.newGetItemStatementRequestBuilder( itemId, lowercaseItemStatementId ),
			expectedStatementId: () => itemStatementId
		},
		{
			newRequestBuilder: () => rbf.newGetPropertyStatementRequestBuilder(
				propertyId,
				lowercasePropertyStatementId ),
			expectedStatementId: () => propertyStatementId
		},
		{
			newRequestBuilder: () => rbf.newReplaceStatementRequestBuilder(
				lowercaseItemStatementId,
				newStatementWithRandomStringValue( statementPropertyId )
			),
			expectedStatementId: () => itemStatementId
		},
		{
			newRequestBuilder: () => rbf.newReplaceItemStatementRequestBuilder(
				itemId,
				lowercaseItemStatementId,
				newStatementWithRandomStringValue( statementPropertyId )
			),
			expectedStatementId: () => itemStatementId
		},
		{
			newRequestBuilder: () => rbf.newReplacePropertyStatementRequestBuilder(
				propertyId,
				lowercasePropertyStatementId,
				newStatementWithRandomStringValue( statementPropertyId )
			),
			expectedStatementId: () => propertyStatementId
		},
		{
			newRequestBuilder: () => rbf.newPatchStatementRequestBuilder(
				lowercaseItemStatementId,
				[ {
					op: 'replace',
					path: '/value/content',
					value: 'random-string-value-' + utils.uniq()
				} ]
			),
			expectedStatementId: () => itemStatementId
		},
		{
			newRequestBuilder: () => rbf.newPatchItemStatementRequestBuilder(
				itemId,
				lowercaseItemStatementId,
				[ {
					op: 'replace',
					path: '/value/content',
					value: 'random-string-value-' + utils.uniq()
				} ]
			),
			expectedStatementId: () => itemStatementId
		},
		{
			newRequestBuilder: () => rbf.newPatchPropertyStatementRequestBuilder(
				propertyId,
				lowercasePropertyStatementId,
				[ {
					op: 'replace',
					path: '/value/content',
					value: 'random-string-value-' + utils.uniq()
				} ]
			),
			expectedStatementId: () => propertyStatementId
		},
		{
			newRequestBuilder: () => rbf.newRemoveStatementRequestBuilder(
				lowercaseItemStatementId
			),
			expectedStatementId: () => itemStatementId
		},
		{
			newRequestBuilder: () => rbf.newRemoveItemStatementRequestBuilder(
				itemId,
				lowercaseItemStatementId
			),
			expectedStatementId: () => itemStatementId
		},
		{
			newRequestBuilder: () => rbf.newRemovePropertyStatementRequestBuilder(
				propertyId,
				lowercasePropertyStatementId
			),
			expectedStatementId: () => propertyStatementId
		}
	].forEach( ( { newRequestBuilder, expectedStatementId } ) => {
		describe( newRequestBuilder().getRouteDescription(), () => {

			it( 'redirects non-existent lowercase statement ID to existing one', async () => {
				const response = await newRequestBuilder()
					.assertValidRequest()
					.makeRequest();

				expect( response ).to.have.status( 308 );
				assert.isTrue( response.headers.location.endsWith( expectedStatementId() ) );
			} );

		} );
	} );

} );
