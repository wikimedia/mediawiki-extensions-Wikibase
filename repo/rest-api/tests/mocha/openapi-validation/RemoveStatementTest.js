'use strict';

const chai = require( 'chai' );
const entityHelper = require( '../helpers/entityHelper' );
const { RequestBuilder } = require( '../helpers/RequestBuilder' );
const expect = chai.expect;

function newRemoveStatementRequestBuilder( statementId ) {
	return new RequestBuilder()
		.withRoute( 'DELETE', '/statements/{statement_id}' )
		.withPathParam( 'statement_id', statementId );
}

function newRemoveItemStatementRequestBuilder( itemId, statementId ) {
	return new RequestBuilder()
		.withRoute( 'DELETE', '/entities/items/{item_id}/statements/{statement_id}' )
		.withPathParam( 'item_id', itemId )
		.withPathParam( 'statement_id', statementId );
}

describe( 'validate DELETE endpoints against OpenAPI definition', () => {
	let testItemId;
	let testPropertyId;

	before( async () => {
		testItemId = ( await entityHelper.createEntity( 'item', {} ) ).entity.id;
		testPropertyId = ( await entityHelper.createUniqueStringProperty() ).entity.id;

	} );

	[
		{
			route: '/entities/items/{item_id}/statements/{statement_id}',
			newRemoveRequestBuilder: ( statementId, patch ) =>
				newRemoveItemStatementRequestBuilder( testItemId, statementId, patch )
		},
		{
			route: '/statements/{statement_id}',
			newRemoveRequestBuilder: newRemoveStatementRequestBuilder
		}
	].forEach( ( { route, newRemoveRequestBuilder } ) => {
		describe( route, () => {

			describe( 'DELETE on newly created statements', () => {
				let testStatementId;

				beforeEach( async () => {
					testStatementId = ( await new RequestBuilder()
						.withRoute( 'POST', '/entities/items/{item_id}/statements' )
						.withPathParam( 'item_id', testItemId )
						.withHeader( 'content-type', 'application/json' )
						.withJsonBodyParam(
							'statement',
							entityHelper.newStatementWithRandomStringValue( testPropertyId )
						).makeRequest() ).body.id;
				} );

				it( '200 OK response is valid', async () => {
					const response = await newRemoveRequestBuilder( testStatementId )
						.makeRequest();

					expect( response.status ).to.equal( 200 );
					expect( response ).to.satisfyApiSpec;
				} );

				it( '412 Precondition Failed is valid for outdated revision id', async () => {
					const response = await newRemoveRequestBuilder( testStatementId )
						.withHeader( 'If-Match', '"1"' )
						.makeRequest();

					expect( response.status ).to.equal( 412 );
					expect( response ).to.satisfyApiSpec;
				} );
			} );

			it( '400 Bad Request for invalid Statement ID', async () => {
				const invalidStatementId = 'X123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
				const response = await newRemoveRequestBuilder( invalidStatementId )
					.makeRequest();

				expect( response.status ).to.equal( 400 );
				expect( response ).to.satisfyApiSpec;
			} );

			it( '404 Not Found response is valid for a non-existing statement', async () => {
				const nonexistentStatementId = 'Q1$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';

				const response = await newRemoveRequestBuilder( nonexistentStatementId )
					.makeRequest();

				expect( response.status ).to.equal( 404 );
				expect( response ).to.satisfyApiSpec;
			} );

			it( '415 Unsupported Media Type is valid for unknown Content-Type: headers', async () => {
				const validStatementId = 'Q1$a787449b-4e36-a7fd-1842-0f38ee96cdb0';
				const response = await newRemoveRequestBuilder( validStatementId )
					.withHeader( 'Content-Type', 'text/plain' )
					.makeRequest();

				expect( response.status ).to.equal( 415 );
				expect( response ).to.satisfyApiSpec;
			} );
		} );
	} );

} );
