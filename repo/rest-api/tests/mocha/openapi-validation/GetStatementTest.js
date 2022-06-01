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

	let statementId;
	let lastRevId;

	before( async () => {
		const createSingleItemResponse = await createSingleItem();
		statementId = Object.values( createSingleItemResponse.entity.claims )[ 0 ][ 0 ].id;
		lastRevId = createSingleItemResponse.entity.lastrevid;
	} );

	it( '200 OK response is valid', async () => {
		const response = await newGetStatementRequestBuilder( statementId )
			.makeRequest();

		expect( response.status ).to.equal( 200 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '304 Not Modified response is valid', async () => {
		const response = await newGetStatementRequestBuilder( statementId )
			.withHeader( 'If-None-Match', `"${lastRevId}"` )
			.makeRequest();

		expect( response.status ).to.equal( 304 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 Bad Request response is valid for an invalid UUID part in statement ID', async () => {
		const response = await newGetStatementRequestBuilder( 'Q123$INVALID-UUID-PART' )
			.makeRequest();

		expect( response.status ).to.equal( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 Bad Request response is valid for an invalid item ID in statement ID', async () => {
		const response = await newGetStatementRequestBuilder( 'X123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' )
			.makeRequest();

		expect( response.status ).to.equal( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 Not Found response is valid for a non-existing statement', async () => {
		const createEmptyItemResponse = await createEntity( 'item', {} );
		const itemId = createEmptyItemResponse.entity.id;

		const response = await newGetStatementRequestBuilder( `${itemId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE` )
			.makeRequest();

		expect( response.status ).to.equal( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 Not Found response is valid for a non-existing item', async () => {
		const response = await newGetStatementRequestBuilder( 'Q99999$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' )
			.makeRequest();

		expect( response.status ).to.equal( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

} );
