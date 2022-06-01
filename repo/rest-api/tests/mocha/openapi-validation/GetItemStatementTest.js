'use strict';

const chai = require( 'chai' );
const { createSingleItem } = require( '../helpers/entityHelper' );
const { RequestBuilder } = require( '../helpers/RequestBuilder' );
const expect = chai.expect;

function newGetItemStatementRequestBuilder( itemId, statementId ) {
	return new RequestBuilder()
		.withRoute( '/entities/items/{entity_id}/statements/{statement_id}' )
		.withPathParam( 'entity_id', itemId )
		.withPathParam( 'statement_id', statementId );
}

describe( 'validate GET /entities/items/{entity_id}/statements/{statement_id} responses', () => {

	let itemId;
	let statementId;
	let lastRevId;

	before( async () => {
		const createSingleItemResponse = await createSingleItem();
		itemId = createSingleItemResponse.entity.id;
		statementId = Object.values( createSingleItemResponse.entity.claims )[ 0 ][ 0 ].id;
		lastRevId = createSingleItemResponse.entity.lastrevid;
	} );

	it( '200 OK response is valid', async () => {
		const response = await newGetItemStatementRequestBuilder( itemId, statementId )
			.makeRequest();

		expect( response.status ).to.equal( 200 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '304 Not Modified response is valid', async () => {
		const response = await newGetItemStatementRequestBuilder( itemId, statementId )
			.withHeader( 'If-None-Match', `"${lastRevId}"` )
			.makeRequest();

		expect( response.status ).to.equal( 304 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 Bad Request for invalid Item ID', async () => {
		const response = await newGetItemStatementRequestBuilder( 'X123', statementId )
			.makeRequest();

		expect( response.status ).to.equal( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 Bad Request for invalid Statement ID', async () => {
		const invalidStatementId = 'X123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		const response = await newGetItemStatementRequestBuilder( itemId, invalidStatementId )
			.makeRequest();

		expect( response.status ).to.equal( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 Not Found response is valid for a non-existing statement', async () => {
		const nonexistentStatement = `${itemId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`;

		const response = await newGetItemStatementRequestBuilder( itemId, nonexistentStatement )
			.makeRequest();

		expect( response.status ).to.equal( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 Not Found response is valid for a non-existing item', async () => {
		const nonexistentItem = 'Q99999';
		const response = await newGetItemStatementRequestBuilder(
			nonexistentItem,
			`${nonexistentItem}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`
		).makeRequest();

		expect( response.status ).to.equal( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

} );
