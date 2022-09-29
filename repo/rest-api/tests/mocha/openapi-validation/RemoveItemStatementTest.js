'use strict';

const chai = require( 'chai' );
const entityHelper = require( '../helpers/entityHelper' );
const {
	newRemoveItemStatementRequestBuilder,
	newRemoveStatementRequestBuilder,
	newAddItemStatementRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const expect = chai.expect;

describe( 'validate DELETE endpoints against OpenAPI definition', () => {
	let testItemId;
	let testPropertyId;

	before( async () => {
		testItemId = ( await entityHelper.createEntity( 'item', {} ) ).entity.id;
		testPropertyId = ( await entityHelper.createUniqueStringProperty() ).entity.id;

	} );

	[
		( statementId, patch ) => newRemoveItemStatementRequestBuilder( testItemId, statementId, patch ),
		newRemoveStatementRequestBuilder
	].forEach( ( newRemoveRequestBuilder ) => {
		describe( newRemoveRequestBuilder().getRouteDescription(), () => {

			describe( 'DELETE on newly created statements', () => {
				let testStatementId;

				beforeEach( async () => {
					testStatementId = ( await newAddItemStatementRequestBuilder(
						testItemId,
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
