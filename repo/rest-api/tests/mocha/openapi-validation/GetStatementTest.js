'use strict';

const chai = require( 'chai' );
const { createEntity, createSingleItem } = require( '../helpers/entityHelper' );
const { RequestBuilder } = require( '../helpers/RequestBuilder' );
const expect = chai.expect;

function newGetStatementRequestBuilder( statementId ) {
	return new RequestBuilder()
		.withRoute( '/statements/{statement_id}' )
		.withPathParam( 'statement_id', statementId );
}

describe( 'validate GET /statements/${statement_id} responses against OpenAPI spec', () => {

	it( '200 OK response is valid', async () => {
		const createSingleItemResponse = await createSingleItem();
		const claims = createSingleItemResponse.entity.claims;
		const statementId = Object.values( claims )[ 0 ][ 0 ].id;

		const response = await newGetStatementRequestBuilder( statementId )
			.makeRequest();

		expect( response.status ).to.equal( 200 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 Bad Request response is valid for an invalid UUID part in statement ID', async () => {
		const statementId = 'Q123$INVALID-UUID-PART';
		const response = await newGetStatementRequestBuilder( statementId )
			.makeRequest();

		expect( response.status ).to.equal( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 Bad Request response is valid for an invalid item ID in statement ID', async () => {
		const statementId = 'X123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		const response = await newGetStatementRequestBuilder( statementId )
			.makeRequest();

		expect( response.status ).to.equal( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 Not Found response is valid for a non-existing statement', async () => {
		const createEmptyItemResponse = await createEntity( 'item', {} );
		const itemId = createEmptyItemResponse.entity.id;
		const statementId = `${itemId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`;

		const response = await newGetStatementRequestBuilder( statementId )
			.makeRequest();

		expect( response.status ).to.equal( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 Not Found response is valid for a non-existing item', async () => {
		const statementId = 'Q99999$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		const response = await newGetStatementRequestBuilder( statementId )
			.makeRequest();

		expect( response.status ).to.equal( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

} );
