'use strict';

const chai = require( 'chai' );
const { createSingleItem } = require( '../helpers/entityHelper' );
const { RequestBuilder } = require( '../helpers/RequestBuilder' );
const expect = chai.expect;

function newRemoveStatementRequestBuilder( statementId ) {
	return new RequestBuilder()
		.withRoute( 'DELETE', '/statements/{statement_id}' )
		.withPathParam( 'statement_id', statementId );
}

describe( 'validate DELETE /entities/items/{item_id}/statements/{statement_id} responses', () => {

	describe( 'create statements to delete', () => {
		let newStatementId;

		beforeEach( async () => {
			const createSingleItemResponse = await createSingleItem();
			newStatementId = Object.values( createSingleItemResponse.entity.claims )[ 0 ][ 0 ].id;
		} );

		it( '200 OK response is valid', async () => {
			const response = await newRemoveStatementRequestBuilder( newStatementId )
				.makeRequest();

			expect( response.status ).to.equal( 200 );
			expect( response ).to.satisfyApiSpec;
		} );

		it( '412 Precondition Failed is valid for outdated revision id', async () => {
			const response = await newRemoveStatementRequestBuilder( newStatementId )
				.withHeader( 'If-Match', '"1"' )
				.makeRequest();

			expect( response.status ).to.equal( 412 );
			expect( response ).to.satisfyApiSpec;
		} );
	} );

	it( '400 Bad Request for invalid Statement ID', async () => {
		const invalidStatementId = 'X123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		const response = await newRemoveStatementRequestBuilder( invalidStatementId )
			.makeRequest();

		expect( response.status ).to.equal( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 Not Found response is valid for a non-existing statement', async () => {
		const nonexistentStatement = 'Q1$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';

		const response = await newRemoveStatementRequestBuilder( nonexistentStatement )
			.makeRequest();

		expect( response.status ).to.equal( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '415 Unsupported Media Type is valid for unknown Content-Type: headers', async () => {
		const validStatementId = 'Q1$a787449b-4e36-a7fd-1842-0f38ee96cdb0';
		const response = await newRemoveStatementRequestBuilder( validStatementId )
			.withHeader( 'Content-Type', 'text/plain' )
			.makeRequest();

		expect( response.status ).to.equal( 415 );
		expect( response ).to.satisfyApiSpec;
	} );

} );
