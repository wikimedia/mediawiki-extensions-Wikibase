'use strict';

const { assert, action } = require( 'api-testing' );
const { RequestBuilder } = require( '../helpers/RequestBuilder' );
const {
	createEntity,
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
		const createEntityResponse = await createEntity( 'item', {
			claims: [ newStatementWithRandomStringValue( stringPropertyId ) ]
		} );
		itemId = createEntityResponse.entity.id;
		statementId = createEntityResponse.entity.claims[ stringPropertyId ][ 0 ].id;
	} );

	const editRequests = [
		{
			route: 'POST /entities/items/{item_id}/statements',
			newRequestBuilder: () => new RequestBuilder()
				.withRoute( 'POST', '/entities/items/{item_id}/statements' )
				.withPathParam( 'item_id', itemId )
				.withJsonBodyParam( 'statement', newStatementWithRandomStringValue( stringPropertyId ) ),
			expectedStatusCode: 201
		},
		{
			route: 'PUT /entities/items/{item_id}/statements/{statement_id}',
			newRequestBuilder: () => new RequestBuilder()
				.withRoute( 'PUT', '/entities/items/{item_id}/statements/{statement_id}' )
				.withPathParam( 'item_id', itemId )
				.withPathParam( 'statement_id', statementId )
				.withJsonBodyParam( 'statement', newStatementWithRandomStringValue( stringPropertyId ) )
		},
		{
			route: 'PUT /statements/{statement_id}',
			newRequestBuilder: () => new RequestBuilder()
				.withRoute( 'PUT', '/statements/{statement_id}' )
				.withPathParam( 'statement_id', statementId )
				.withJsonBodyParam( 'statement', newStatementWithRandomStringValue( stringPropertyId ) )
		},
		{
			route: 'DELETE /entities/items/{item_id}/statements/{statement_id}',
			newRequestBuilder: () => new RequestBuilder()
				.withRoute( 'DELETE', '/entities/items/{item_id}/statements/{statement_id}' )
				.withPathParam( 'item_id', itemId )
				.withPathParam( 'statement_id', statementId ),
			isDestructive: true
		},
		{
			route: 'DELETE /statements/{statement_id}',
			newRequestBuilder: () => new RequestBuilder()
				.withRoute( 'DELETE', '/statements/{statement_id}' )
				.withPathParam( 'statement_id', statementId ),
			isDestructive: true
		}
	];

	const allRoutes = [
		{
			route: 'GET /entities/items/{id}/statements',
			newRequestBuilder: () => new RequestBuilder()
				.withRoute( 'GET', '/entities/items/{item_id}/statements' )
				.withPathParam( 'item_id', itemId )
		},
		{
			route: 'GET /entities/items/{item_id}/statements/{statement_id}',
			newRequestBuilder: () => new RequestBuilder()
				.withRoute( 'GET', '/entities/items/{item_id}/statements/{statement_id}' )
				.withPathParam( 'item_id', itemId )
				.withPathParam( 'statement_id', statementId )
		},
		{
			route: 'GET /entities/items/{id}',
			newRequestBuilder: () => new RequestBuilder()
				.withRoute( 'GET', '/entities/items/{item_id}' )
				.withPathParam( 'item_id', itemId )
		},
		{
			route: 'GET /statements/{statement_id}',
			newRequestBuilder: () => new RequestBuilder()
				.withRoute( 'GET', '/statements/{statement_id}' )
				.withPathParam( 'statement_id', statementId )
		},
		...editRequests
	];

	// eslint-disable-next-line mocha/no-setup-in-describe
	if ( hasJsonDiffLib() ) { // awaiting security review (T316245)
		// eslint-disable-next-line mocha/no-setup-in-describe
		allRoutes.push( { // TODO move this to `editRequests` to also check authorization (T313906)
			route: 'PATCH /entities/items/{item_id}/statements/{statement_id}',
			newRequestBuilder: () => new RequestBuilder()
				.withRoute( 'PATCH', '/entities/items/{item_id}/statements/{statement_id}' )
				.withPathParam( 'item_id', itemId )
				.withPathParam( 'statement_id', statementId )
				.withJsonBodyParam( 'patch', [
					{
						op: 'replace',
						path: '/mainsnak',
						value: newStatementWithRandomStringValue( stringPropertyId ).mainsnak
					}
				] )
		} );
		// eslint-disable-next-line mocha/no-setup-in-describe
		allRoutes.push( { // TODO move this to `editRequests` to also check authorization (T313906)
			route: 'PATCH /statements/{statement_id}',
			newRequestBuilder: () => new RequestBuilder()
				.withRoute( 'PATCH', '/statements/{statement_id}' )
				.withPathParam( 'statement_id', statementId )
				.withJsonBodyParam( 'patch', [
					{
						op: 'replace',
						path: '/mainsnak',
						value: newStatementWithRandomStringValue( stringPropertyId ).mainsnak
					}
				] )
		} );
	}

	// eslint-disable-next-line mocha/no-setup-in-describe
	allRoutes.forEach( ( { route, newRequestBuilder, expectedStatusCode = 200, isDestructive } ) => {
		describe( `Authentication - ${route}`, () => {

			afterEach( async () => {
				if ( isDestructive ) {
					const createStatementResponse = await new RequestBuilder()
						.withRoute( 'POST', '/entities/items/{item_id}/statements' )
						.withPathParam( 'item_id', itemId )
						.withJsonBodyParam( 'statement', newStatementWithRandomStringValue( stringPropertyId ) )
						.makeRequest();
					statementId = createStatementResponse.body.id;
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

		// eslint-disable-next-line mocha/no-setup-in-describe
		editRequests.forEach( ( { route, newRequestBuilder } ) => {
			it( `Permission denied for protected item - ${route}`, async () => {
				const response = await newRequestBuilder().makeRequest();

				assert.strictEqual( response.status, 403 );
				assert.strictEqual( response.body.httpCode, 403 );
				assert.strictEqual( response.body.httpReason, 'Forbidden' );
				assert.strictEqual( response.body.error, 'rest-write-denied' );
			} );
		} );
	} );
} );
