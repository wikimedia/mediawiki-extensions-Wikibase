'use strict';

const { assert, action } = require( 'api-testing' );
const rbf = require( '../helpers/RequestBuilderFactory' );
const {
	createItemWithStatements,
	createUniqueStringProperty,
	newStatementWithRandomStringValue,
	protectItem
} = require( '../helpers/entityHelper' );
const hasJsonDiffLib = require( '../helpers/hasJsonDiffLib' );
const { requireExtensions } = require( '../../../../../tests/api-testing/utils' );

describe( 'Auth', () => {

	let itemId;
	let statementId;
	let stringPropertyId;

	before( async () => {
		stringPropertyId = ( await createUniqueStringProperty() ).entity.id;
		const createEntityResponse = await createItemWithStatements( [
			newStatementWithRandomStringValue( stringPropertyId )
		] );
		itemId = createEntityResponse.entity.id;
		statementId = createEntityResponse.entity.claims[ stringPropertyId ][ 0 ].id;
	} );

	const editRequests = [
		{
			newRequestBuilder: () => rbf.newAddItemStatementRequestBuilder(
				itemId,
				newStatementWithRandomStringValue( stringPropertyId )
			),
			expectedStatusCode: 201
		},
		{
			newRequestBuilder: () => rbf.newReplaceItemStatementRequestBuilder(
				itemId,
				statementId,
				newStatementWithRandomStringValue( stringPropertyId )
			)
		},
		{
			newRequestBuilder: () => rbf.newReplaceStatementRequestBuilder(
				statementId,
				newStatementWithRandomStringValue( stringPropertyId )
			)
		},
		{
			newRequestBuilder: () => rbf.newRemoveItemStatementRequestBuilder( itemId, statementId ),
			isDestructive: true
		},
		{
			newRequestBuilder: () => rbf.newRemoveStatementRequestBuilder( statementId ),
			isDestructive: true
		}
	];

	if ( hasJsonDiffLib() ) { // awaiting security review (T316245)
		editRequests.push( {
			newRequestBuilder: () => rbf.newPatchItemStatementRequestBuilder(
				itemId,
				statementId,
				[ {
					op: 'replace',
					path: '/mainsnak',
					value: newStatementWithRandomStringValue( stringPropertyId ).mainsnak
				} ]
			)
		} );
		editRequests.push( {
			newRequestBuilder: () => rbf.newPatchStatementRequestBuilder(
				statementId,
				[ {
					op: 'replace',
					path: '/mainsnak',
					value: newStatementWithRandomStringValue( stringPropertyId ).mainsnak
				} ]
			)
		} );
	}

	[
		{
			newRequestBuilder: () => rbf.newGetItemStatementsRequestBuilder( itemId )
		},
		{
			newRequestBuilder: () => rbf.newGetItemStatementRequestBuilder( itemId, statementId )
		},
		{
			newRequestBuilder: () => rbf.newGetItemRequestBuilder( itemId )
		},
		{
			newRequestBuilder: () => rbf.newGetStatementRequestBuilder( statementId )
		},
		...editRequests
	].forEach( ( { newRequestBuilder, expectedStatusCode = 200, isDestructive } ) => {
		describe( `Authentication - ${newRequestBuilder().getRouteDescription()}`, () => {

			afterEach( async () => {
				if ( isDestructive ) {
					statementId = ( await rbf.newAddItemStatementRequestBuilder(
						itemId,
						newStatementWithRandomStringValue( stringPropertyId )
					).makeRequest() ).body.id;
				}
			} );

			it( 'has an X-Authenticated-User header with the logged in user', async () => {
				const mindy = await action.mindy();

				const response = await newRequestBuilder().withUser( mindy ).makeRequest();

				assert.strictEqual( response.statusCode, expectedStatusCode );
				assert.header( response, 'X-Authenticated-User', mindy.username );
			} );

			describe.skip( 'OAuth', () => { // Skipping due to apache auth header issues. See T305709
				before( requireExtensions( [ 'OAuth' ] ) );

				it( 'responds with an error given an invalid bearer token', async () => {
					const response = newRequestBuilder()
						.withHeader( 'Authorization', 'Bearer this-is-an-invalid-token' )
						.makeRequest();

					assert.strictEqual( response.status, 403 );
				} );
			} );
		} );
	} );

	describe( 'Authorization', () => {
		before( async () => {
			await protectItem( itemId );
		} );

		editRequests.forEach( ( { newRequestBuilder } ) => {
			it( `Permission denied for protected item - ${newRequestBuilder().getRouteDescription()}`, async () => {
				const response = await newRequestBuilder().makeRequest();

				assert.strictEqual( response.status, 403 );
				assert.strictEqual( response.body.httpCode, 403 );
				assert.strictEqual( response.body.httpReason, 'Forbidden' );
				assert.strictEqual( response.body.error, 'rest-write-denied' );
			} );
		} );
	} );
} );
