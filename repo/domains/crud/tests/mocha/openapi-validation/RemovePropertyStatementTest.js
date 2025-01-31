'use strict';

const { expect } = require( '../../../../../rest-api/tests/mocha/helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const {
	newRemovePropertyStatementRequestBuilder,
	newRemoveStatementRequestBuilder,
	newAddPropertyStatementRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );

describe( 'validate DELETE endpoints for property statements against OpenAPI definition', () => {
	let testPropertyId;
	let statementPropertyId;

	before( async () => {
		testPropertyId = ( await entityHelper.createUniqueStringProperty() ).body.id;
		statementPropertyId = await entityHelper.getStringPropertyId();
	} );

	[
		( statementId ) => newRemovePropertyStatementRequestBuilder( testPropertyId, statementId ),
		newRemoveStatementRequestBuilder
	].forEach( ( newRemoveRequestBuilder ) => {
		describe( newRemoveRequestBuilder().getRouteDescription(), () => {

			describe( 'DELETE on newly created statements', () => {
				let testStatementId;

				beforeEach( async () => {
					testStatementId = ( await newAddPropertyStatementRequestBuilder(
						testPropertyId,
						entityHelper.newStatementWithRandomStringValue( statementPropertyId )
					).makeRequest() ).body.id;
				} );

				it( '200 OK response is valid', async () => {
					const response = await newRemoveRequestBuilder( testStatementId )
						.makeRequest();

					expect( response ).to.have.status( 200 );
					expect( response ).to.satisfyApiSchema;
				} );

				it( '412 Precondition Failed is valid for outdated revision id', async () => {
					const response = await newRemoveRequestBuilder( testStatementId )
						.withHeader( 'If-Match', '"1"' )
						.makeRequest();

					expect( response ).to.have.status( 412 );
					expect( response ).to.satisfyApiSchema;
				} );
			} );

			it( '400 Bad Request for invalid Statement ID', async () => {
				const invalidStatementId = 'X123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
				const response = await newRemoveRequestBuilder( invalidStatementId )
					.makeRequest();

				expect( response ).to.have.status( 400 );
				expect( response ).to.satisfyApiSchema;
			} );

			it( '404 Not Found response is valid for a non-existing statement', async () => {
				const nonexistentStatementId = `${testPropertyId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`;

				const response = await newRemoveRequestBuilder( nonexistentStatementId )
					.makeRequest();

				expect( response ).to.have.status( 404 );
				expect( response ).to.satisfyApiSchema;
			} );
		} );
	} );

} );
