'use strict';

const { assert } = require( 'api-testing' );
const { RequestBuilder } = require( '../helpers/RequestBuilder' );
const {
	createItemWithStatements,
	createUniqueStringProperty,
	newStatementWithRandomStringValue
} = require( '../helpers/entityHelper' );
const hasJsonDiffLib = require( '../helpers/hasJsonDiffLib' );

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
			newStatementWithRandomStringValue( stringPropertyId )
		] );
		itemId = createEntityResponse.entity.id;
		statementId = createEntityResponse.entity.claims[ stringPropertyId ][ 0 ].id;
	} );

	const editRequests = [
		() => new RequestBuilder()
			.withRoute( 'POST', '/entities/items/{item_id}/statements' )
			.withPathParam( 'item_id', itemId )
			.withJsonBodyParam( 'statement', newStatementWithRandomStringValue( stringPropertyId ) ),
		() => new RequestBuilder()
			.withRoute( 'PUT', '/entities/items/{item_id}/statements/{statement_id}' )
			.withPathParam( 'item_id', itemId )
			.withPathParam( 'statement_id', statementId )
			.withJsonBodyParam( 'statement', newStatementWithRandomStringValue( stringPropertyId ) ),
		() => new RequestBuilder()
			.withRoute( 'PUT', '/statements/{statement_id}' )
			.withPathParam( 'statement_id', statementId )
			.withJsonBodyParam( 'statement', newStatementWithRandomStringValue( stringPropertyId ) ),
		() => new RequestBuilder()
			.withRoute( 'DELETE', '/entities/items/{item_id}/statements/{statement_id}' )
			.withPathParam( 'item_id', itemId )
			.withPathParam( 'statement_id', statementId ),
		() => new RequestBuilder()
			.withRoute( 'DELETE', '/statements/{statement_id}' )
			.withPathParam( 'statement_id', statementId )
	];

	if ( hasJsonDiffLib() ) { // awaiting security review (T316245)
		editRequests.push( () => new RequestBuilder()
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
		);
		editRequests.push( () => new RequestBuilder()
			.withRoute( 'PATCH', '/statements/{statement_id}' )
			.withPathParam( 'statement_id', statementId )
			.withJsonBodyParam( 'patch', [
				{
					op: 'replace',
					path: '/mainsnak',
					value: newStatementWithRandomStringValue( stringPropertyId ).mainsnak
				}
			] )
		);
	}

	[
		() => new RequestBuilder()
			.withRoute( 'GET', '/entities/items/{item_id}/statements' )
			.withPathParam( 'item_id', itemId ),
		() => new RequestBuilder()
			.withRoute( 'GET', '/entities/items/{item_id}/statements/{statement_id}' )
			.withPathParam( 'item_id', itemId )
			.withPathParam( 'statement_id', statementId ),
		() => new RequestBuilder()
			.withRoute( 'GET', '/entities/items/{item_id}' )
			.withPathParam( 'item_id', itemId ),
		() => new RequestBuilder()
			.withRoute( 'GET', '/statements/{statement_id}' )
			.withPathParam( 'statement_id', statementId ),
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
