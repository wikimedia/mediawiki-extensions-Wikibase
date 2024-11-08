'use strict';

const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const {
	newRemoveItemStatementRequestBuilder,
	newRemoveStatementRequestBuilder,
	newAddItemStatementRequestBuilder,
	newCreateItemRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );

describe( 'validate DELETE endpoints for item statements against OpenAPI definition', () => {
	let testItemId;
	let statementPropertyId;

	before( async () => {
		testItemId = ( await newCreateItemRequestBuilder( {} ).makeRequest() ).body.id;
		statementPropertyId = ( await entityHelper.createUniqueStringProperty() ).body.id;

	} );

	[
		( statementId ) => newRemoveItemStatementRequestBuilder( testItemId, statementId ),
		newRemoveStatementRequestBuilder
	].forEach( ( newRemoveRequestBuilder ) => {
		describe( newRemoveRequestBuilder().getRouteDescription(), () => {

			describe( 'DELETE on newly created statements', () => {
				let testStatementId;

				beforeEach( async () => {
					testStatementId = ( await newAddItemStatementRequestBuilder(
						testItemId,
						entityHelper.newStatementWithRandomStringValue( statementPropertyId )
					).makeRequest() ).body.id;
				} );

				it( '200 OK response is valid', async () => {
					const response = await newRemoveRequestBuilder( testStatementId )
						.makeRequest();

					expect( response ).to.have.status( 200 );
					expect( response ).to.satisfyApiSpec;
				} );

				it( '412 Precondition Failed is valid for outdated revision id', async () => {
					const response = await newRemoveRequestBuilder( testStatementId )
						.withHeader( 'If-Match', '"1"' )
						.makeRequest();

					expect( response ).to.have.status( 412 );
					expect( response ).to.satisfyApiSpec;
				} );
			} );

			it( '400 Bad Request for invalid Statement ID', async () => {
				const invalidStatementId = 'X123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
				const response = await newRemoveRequestBuilder( invalidStatementId )
					.makeRequest();

				expect( response ).to.have.status( 400 );
				expect( response ).to.satisfyApiSpec;
			} );

			it( '404 Not Found response is valid for a non-existing statement', async () => {
				const nonexistentStatementId = `${testItemId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`;

				const response = await newRemoveRequestBuilder( nonexistentStatementId )
					.makeRequest();

				expect( response ).to.have.status( 404 );
				expect( response ).to.satisfyApiSpec;
			} );
		} );
	} );

} );
