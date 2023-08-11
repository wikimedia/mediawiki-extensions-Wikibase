'use strict';

const { expect } = require( '../helpers/chaiHelper' );
const {
	createUniqueStringProperty,
	createPropertyWithStatements,
	newStatementWithRandomStringValue,
	newLegacyStatementWithRandomStringValue
} = require( '../helpers/entityHelper' );
const {
	newReplacePropertyStatementRequestBuilder,
	newReplaceStatementRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );

describe( 'validate PUT endpoints against OpenAPI definition', () => {

	let testPropertyId;
	let testStatementId;
	let statementPropertyId;

	before( async () => {
		statementPropertyId = ( await createUniqueStringProperty() ).entity.id;
		const createPropertyResponse = await createPropertyWithStatements( [
			newLegacyStatementWithRandomStringValue( statementPropertyId )
		] );
		testPropertyId = createPropertyResponse.entity.id;
		testStatementId = createPropertyResponse.entity.claims[ statementPropertyId ][ 0 ].id;
	} );

	[
		( statementId, patch ) => newReplacePropertyStatementRequestBuilder( testPropertyId, statementId, patch ),
		newReplaceStatementRequestBuilder
	].forEach( ( newReplaceRequestBuilder ) => {

		describe( newReplaceRequestBuilder().getRouteDescription(), () => {

			it( '200', async () => {
				const response = await newReplaceRequestBuilder(
					testStatementId,
					newStatementWithRandomStringValue( statementPropertyId )
				).makeRequest();

				expect( response ).to.have.status( 200 );
				expect( response ).to.satisfyApiSpec;
			} );

			it( '400 - invalid statement serialization', async () => {
				const response = await newReplaceRequestBuilder(
					testStatementId,
					{ invalid: 'statement' }
				).makeRequest();

				expect( response ).to.have.status( 400 );
				expect( response ).to.satisfyApiSpec;
			} );

			it( '404 - statement does not exist', async () => {
				const response = await newReplaceRequestBuilder(
					`${testPropertyId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`,
					newStatementWithRandomStringValue( statementPropertyId )
				).makeRequest();

				expect( response ).to.have.status( 404 );
				expect( response ).to.satisfyApiSpec;
			} );

			it( '412 - precondition failed', async () => {
				const yesterday = new Date( Date.now() - 24 * 60 * 60 * 1000 ).toUTCString();
				const response = await newReplaceRequestBuilder(
					testStatementId,
					newStatementWithRandomStringValue( statementPropertyId )
				)
					.withHeader( 'If-Unmodified-Since', yesterday )
					.makeRequest();

				expect( response ).to.have.status( 412 );
				expect( response ).to.satisfyApiSpec;
			} );

			it( '415 - unsupported media type', async () => {
				const response = await newReplaceRequestBuilder(
					testStatementId,
					newStatementWithRandomStringValue( statementPropertyId )
				)
					.withHeader( 'Content-Type', 'text/plain' )
					.makeRequest();

				expect( response ).to.have.status( 415 );
				expect( response ).to.satisfyApiSpec;
			} );

		} );

	} );

} );
