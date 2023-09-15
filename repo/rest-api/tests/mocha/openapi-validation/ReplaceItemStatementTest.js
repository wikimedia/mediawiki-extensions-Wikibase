'use strict';

const { expect } = require( '../helpers/chaiHelper' );
const {
	createUniqueStringProperty,
	createItemWithStatements,
	newStatementWithRandomStringValue,
	newLegacyStatementWithRandomStringValue
} = require( '../helpers/entityHelper' );
const {
	newReplaceItemStatementRequestBuilder,
	newReplaceStatementRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );

describe( 'validate PUT endpoints for item statements against OpenAPI definition', () => {

	let testItemId;
	let testStatementId;
	let statementPropertyId;

	before( async () => {
		statementPropertyId = ( await createUniqueStringProperty() ).entity.id;
		const createItemResponse = await createItemWithStatements( [
			newLegacyStatementWithRandomStringValue( statementPropertyId )
		] );
		testItemId = createItemResponse.entity.id;
		testStatementId = createItemResponse.entity.claims[ statementPropertyId ][ 0 ].id;
	} );

	[
		( statementId, statement ) => newReplaceItemStatementRequestBuilder( testItemId, statementId, statement ),
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
					`${testItemId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`,
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
