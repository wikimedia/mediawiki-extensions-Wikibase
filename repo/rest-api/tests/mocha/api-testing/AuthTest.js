'use strict';

const { assert, action, utils } = require( 'api-testing' );
const rbf = require( '../helpers/RequestBuilderFactory' );
const {
	createItemWithStatements,
	createUniqueStringProperty,
	newStatementWithRandomStringValue,
	newLegacyStatementWithRandomStringValue,
	changeItemProtectionStatus
} = require( '../helpers/entityHelper' );
const { requireExtensions } = require( '../../../../../tests/api-testing/utils' );

describe( 'Auth', () => {

	let itemId;
	let statementId;
	let stringPropertyId;
	let user;

	before( async () => {
		stringPropertyId = ( await createUniqueStringProperty() ).entity.id;
		const createEntityResponse = await createItemWithStatements( [
			newLegacyStatementWithRandomStringValue( stringPropertyId )
		] );
		itemId = createEntityResponse.entity.id;
		statementId = createEntityResponse.entity.claims[ stringPropertyId ][ 0 ].id;
		user = await action.mindy();
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
		},
		{
			newRequestBuilder: () => rbf.newPatchItemStatementRequestBuilder(
				itemId,
				statementId,
				[ {
					op: 'replace',
					path: '/value/content',
					value: 'random-string-value-' + utils.uniq()
				} ]
			)
		},
		{
			newRequestBuilder: () => rbf.newPatchStatementRequestBuilder(
				statementId,
				[ {
					op: 'replace',
					path: '/value/content',
					value: 'random-string-value-' + utils.uniq()
				} ]
			)
		}
	];

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
			newRequestBuilder: () => rbf.newGetItemAliasesRequestBuilder( itemId )
		},
		{
			newRequestBuilder: () => rbf.newGetItemDescriptionsRequestBuilder( itemId )
		},
		{
			newRequestBuilder: () => rbf.newGetItemLabelsRequestBuilder( itemId )
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
				const response = await newRequestBuilder().withUser( user ).makeRequest();

				assert.strictEqual( response.statusCode, expectedStatusCode );
				assert.header( response, 'X-Authenticated-User', user.username );
			} );

			// eslint-disable-next-line mocha/no-skipped-tests
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
		function assertPermissionDenied( response ) {
			assert.strictEqual( response.status, 403 );
			assert.strictEqual( response.body.httpCode, 403 );
			assert.strictEqual( response.body.httpReason, 'Forbidden' );
			assert.strictEqual( response.body.error, 'rest-write-denied' );
		}

		editRequests.forEach( ( { newRequestBuilder } ) => {
			describe( 'Protected item', () => {
				before( async () => {
					await changeItemProtectionStatus( itemId, 'sysop' ); // protect
				} );

				after( async () => {
					await changeItemProtectionStatus( itemId, 'all' ); // unprotect
				} );

				it( `Permission denied - ${newRequestBuilder().getRouteDescription()}`, async () => {
					assertPermissionDenied( await newRequestBuilder().makeRequest() );
				} );
			} );

			describe( 'Blocked user', () => {
				before( async () => {
					await user.action( 'block', {
						user: user.username,
						reason: 'testing',
						token: await user.token()
					}, 'POST' );
				} );

				after( async () => {
					await user.action( 'unblock', {
						user: user.username,
						token: await user.token()
					}, 'POST' );
				} );

				it( 'can not edit if blocked', async () => {
					const response = await newRequestBuilder().withUser( user ).makeRequest();
					assert.strictEqual( response.statusCode, 403 );
				} );
			} );

			it( `Unauthorized bot edit - ${newRequestBuilder().getRouteDescription()}`, async () => {
				assertPermissionDenied(
					await newRequestBuilder()
						.withJsonBodyParam( 'bot', true )
						.makeRequest()
				);
			} );
		} );
	} );
} );
