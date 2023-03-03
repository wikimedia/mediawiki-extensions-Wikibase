'use strict';

const { assert, utils } = require( 'api-testing' );
const rbf = require( '../helpers/RequestBuilderFactory' );
const {
	createItemWithStatements,
	createUniqueStringProperty,
	newLegacyStatementWithRandomStringValue,
	newStatementWithRandomStringValue
} = require( '../helpers/entityHelper' );

function assertValid400Response( response ) {
	assert.equal( response.status, 400 );
	assert.strictEqual( response.body.code, 'missing-user-agent' );
	assert.include( response.body.message, 'User-Agent' );
}

describe( 'User-Agent requests', () => {

	let itemId;
	let statementId;
	let stringPropertyId;

	before( async () => {
		stringPropertyId = ( await createUniqueStringProperty() ).entity.id;
		const createEntityResponse = await createItemWithStatements( [
			newLegacyStatementWithRandomStringValue( stringPropertyId )
		] );
		itemId = createEntityResponse.entity.id;
		statementId = createEntityResponse.entity.claims[ stringPropertyId ][ 0 ].id;
	} );

	const editRequests = [
		() => rbf.newAddItemStatementRequestBuilder(
			itemId,
			newStatementWithRandomStringValue( stringPropertyId )
		),
		() => rbf.newReplaceItemStatementRequestBuilder(
			itemId,
			statementId,
			newStatementWithRandomStringValue( stringPropertyId )
		),
		() => rbf.newReplaceStatementRequestBuilder(
			statementId,
			newStatementWithRandomStringValue( stringPropertyId )
		),
		() => rbf.newRemoveItemStatementRequestBuilder( itemId, statementId ),
		() => rbf.newRemoveStatementRequestBuilder( statementId ),
		() => rbf.newPatchItemStatementRequestBuilder(
			itemId,
			statementId,
			[ {
				op: 'replace',
				path: '/mainsnak/datavalue/value',
				value: 'random-string-value-' + utils.uniq()
			} ]
		),
		() => rbf.newPatchStatementRequestBuilder(
			statementId,
			[ {
				op: 'replace',
				path: '/mainsnak/datavalue/value',
				value: 'random-string-value-' + utils.uniq()
			} ]
		)
	];

	[
		() => rbf.newGetItemStatementsRequestBuilder( itemId ),
		() => rbf.newGetItemStatementRequestBuilder( itemId, statementId ),
		() => rbf.newGetItemRequestBuilder( itemId ),
		() => rbf.newGetItemAliasesRequestBuilder( itemId ),
		() => rbf.newGetItemDescriptionsRequestBuilder( itemId ),
		() => rbf.newGetItemLabelsRequestBuilder( itemId ),
		() => rbf.newGetStatementRequestBuilder( statementId ),
		...editRequests
	].forEach( ( newRequestBuilder ) => {
		describe( newRequestBuilder().getRouteDescription(), () => {

			it( 'No User-Agent header provided', async () => {
				const requestBuilder = newRequestBuilder();
				delete requestBuilder.headers[ 'user-agent' ];
				const response = await requestBuilder
					.assertValidRequest()
					.makeRequest();

				assertValid400Response( response );
			} );

			it( 'Empty User-Agent header provided', async () => {
				const response = await newRequestBuilder()
					.withHeader( 'user-agent', '' )
					.assertValidRequest()
					.makeRequest();

				assertValid400Response( response );
			} );

		} );
	} );

} );
