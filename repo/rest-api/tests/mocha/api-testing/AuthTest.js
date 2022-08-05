'use strict';

const { assert, action } = require( 'api-testing' );
const { RequestBuilder } = require( '../helpers/RequestBuilder' );
const {
	createEntity,
	createUniqueStringProperty,
	newStatementWithRandomStringValue
} = require( '../helpers/entityHelper' );
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

	[ // eslint-disable-line mocha/no-setup-in-describe
		{
			route: 'POST /entities/items/{item_id}/statements',
			newRequestBuilder: () => new RequestBuilder()
				.withRoute( 'POST', '/entities/items/{item_id}/statements' )
				.withPathParam( 'item_id', itemId )
				.withJsonBodyParam( 'statement', newStatementWithRandomStringValue( stringPropertyId ) ),
			expectedStatusCode: 201
		},
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
	].forEach( ( { route, newRequestBuilder, expectedStatusCode = 200, isDestructive } ) => {
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
} );
