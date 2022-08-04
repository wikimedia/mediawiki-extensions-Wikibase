'use strict';

const chai = require( 'chai' );
const { createSingleItem } = require( '../helpers/entityHelper' );
const { RequestBuilder } = require( '../helpers/RequestBuilder' );
const expect = chai.expect;

function newRemoveItemStatementRequestBuilder( itemId, statementId ) {
	return new RequestBuilder()
		.withRoute( 'DELETE', '/entities/items/{item_id}/statements/{statement_id}' )
		.withPathParam( 'item_id', itemId )
		.withPathParam( 'statement_id', statementId );
}

describe( 'validate DELETE /entities/items/{item_id}/statements/{statement_id} responses', () => {

	const validItemId = 'Q1';
	const validStatementId = 'Q1$a787449b-4e36-a7fd-1842-0f38ee96cdb0';

	describe( 'create items with a statement to delete', () => {
		let newItemId;
		let newStatementId;
		beforeEach( async () => {
			const createSingleItemResponse = await createSingleItem();
			newItemId = createSingleItemResponse.entity.id;
			newStatementId = Object.values( createSingleItemResponse.entity.claims )[ 0 ][ 0 ].id;
		} );

		it( '200 OK response is valid', async () => {
			const response = await newRemoveItemStatementRequestBuilder( newItemId, newStatementId )
				.makeRequest();

			expect( response.status ).to.equal( 200 );
			expect( response ).to.satisfyApiSpec;
		} );

		it( '404 Not Found response is valid for a non-existing statement', async () => {
			const nonexistentStatement = `${newItemId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`;

			const response = await newRemoveItemStatementRequestBuilder( newItemId, nonexistentStatement )
				.makeRequest();

			expect( response.status ).to.equal( 404 );
			expect( response ).to.satisfyApiSpec;
		} );

		it( '412 Precondition Failed is valid for outdated revision id', async () => {
			const response = await newRemoveItemStatementRequestBuilder( newItemId, newStatementId )
				.withHeader( 'If-Match', '"1"' )
				.makeRequest();

			expect( response.status ).to.equal( 412 );
			expect( response ).to.satisfyApiSpec;
		} );
	} );

	it( '400 Bad Request for invalid Item ID', async () => {
		const response = await newRemoveItemStatementRequestBuilder( 'X123', validStatementId )
			.makeRequest();

		expect( response.status ).to.equal( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 Bad Request for invalid Statement ID', async () => {
		const invalidStatementId = 'X123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		const response = await newRemoveItemStatementRequestBuilder( validItemId, invalidStatementId )
			.makeRequest();

		expect( response.status ).to.equal( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 Not Found response is valid for a non-existing item', async () => {
		const nonexistentItem = 'Q99999';
		const response = await newRemoveItemStatementRequestBuilder(
			nonexistentItem,
			`${nonexistentItem}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`
		).makeRequest();

		expect( response.status ).to.equal( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '415 Unsupported Media Type is valid for unknown Content-Type: headers', async () => {
		const response = await newRemoveItemStatementRequestBuilder( validItemId, validStatementId )
			.withHeader( 'Content-Type', 'text/plain' )
			.makeRequest();

		expect( response.status ).to.equal( 415 );
		expect( response ).to.satisfyApiSpec;
	} );

} );
