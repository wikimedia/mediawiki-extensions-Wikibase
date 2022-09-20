'use strict';

const chai = require( 'chai' );
const { createSingleItem } = require( '../helpers/entityHelper' );
const { RequestBuilder } = require( '../helpers/RequestBuilder' );
const expect = chai.expect;

function newGetItemStatementRequestBuilder( itemId, statementId ) {
	return new RequestBuilder()
		.withRoute( 'GET', '/entities/items/{item_id}/statements/{statement_id}' )
		.withPathParam( 'item_id', itemId )
		.withPathParam( 'statement_id', statementId );
}

function newGetStatementRequestBuilder( statementId ) {
	return new RequestBuilder()
		.withRoute( 'GET', '/statements/{statement_id}' )
		.withPathParam( 'statement_id', statementId );
}

describe( 'validate GET statement responses', () => {

	let testItemId;
	let testStatementId;
	let lastRevId;

	before( async () => {
		const createSingleItemResponse = await createSingleItem();
		testItemId = createSingleItemResponse.entity.id;
		testStatementId = Object.values( createSingleItemResponse.entity.claims )[ 0 ][ 0 ].id;
		lastRevId = createSingleItemResponse.entity.lastrevid;
	} );

	[
		( statementId ) => newGetItemStatementRequestBuilder( testItemId, statementId ),
		newGetStatementRequestBuilder
	].forEach( ( newRequestBuilder ) => {
		describe( newRequestBuilder().getRouteDescription(), () => {
			it( '200 OK response is valid', async () => {
				const response = await newRequestBuilder( testStatementId )
					.makeRequest();

				expect( response.status ).to.equal( 200 );
				expect( response ).to.satisfyApiSpec;
			} );

			it( '304 Not Modified response is valid', async () => {
				const response = await newRequestBuilder( testStatementId )
					.withHeader( 'If-None-Match', `"${lastRevId}"` )
					.makeRequest();

				expect( response.status ).to.equal( 304 );
				expect( response ).to.satisfyApiSpec;
			} );

			it( '400 Bad Request for invalid Statement ID', async () => {
				const invalidStatementId = 'X123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
				const response = await newRequestBuilder( invalidStatementId )
					.makeRequest();

				expect( response.status ).to.equal( 400 );
				expect( response ).to.satisfyApiSpec;
			} );

			it( '404 Not Found response is valid for a non-existing statement', async () => {
				const nonexistentStatement = `${testItemId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`;

				const response = await newRequestBuilder( nonexistentStatement )
					.makeRequest();

				expect( response.status ).to.equal( 404 );
				expect( response ).to.satisfyApiSpec;
			} );

			it( '404 Not Found response is valid for a non-existing item', async () => {
				const nonexistentItem = 'Q99999';
				const response = await newRequestBuilder( `${nonexistentItem}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE` )
					.makeRequest();

				expect( response.status ).to.equal( 404 );
				expect( response ).to.satisfyApiSpec;
			} );
		} );
	} );
} );
