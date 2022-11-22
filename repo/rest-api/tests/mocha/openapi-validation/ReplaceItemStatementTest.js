'use strict';

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
const expect = require( 'chai' ).expect;

describe( 'validate PUT endpoints against OpenAPI definition', () => {

	let testItemId;
	let testStatementId;
	let stringPropertyId;

	before( async () => {
		stringPropertyId = ( await createUniqueStringProperty() ).entity.id;
		const createItemResponse = await createItemWithStatements( [
			newLegacyStatementWithRandomStringValue( stringPropertyId )
		] );
		testItemId = createItemResponse.entity.id;
		testStatementId = createItemResponse.entity.claims[ stringPropertyId ][ 0 ].id;
	} );

	[
		( statementId, patch ) => newReplaceItemStatementRequestBuilder( testItemId, statementId, patch ),
		newReplaceStatementRequestBuilder
	].forEach( ( newReplaceRequestBuilder ) => {
		describe( newReplaceRequestBuilder().getRouteDescription(), () => {

			it( '200', async () => {
				const response = await newReplaceRequestBuilder(
					testStatementId,
					newStatementWithRandomStringValue( stringPropertyId )
				).makeRequest();

				expect( response.status ).to.equal( 200 );
				expect( response ).to.satisfyApiSpec;
			} );

			it( '400 - invalid statement serialization', async () => {
				const response = await newReplaceRequestBuilder(
					testStatementId,
					{ invalid: 'statement' }
				).makeRequest();

				expect( response.status ).to.equal( 400 );
				expect( response ).to.satisfyApiSpec;
			} );

			it( '404 - statement does not exist', async () => {
				const response = await newReplaceRequestBuilder(
					`${testItemId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`,
					newStatementWithRandomStringValue( stringPropertyId )
				).makeRequest();

				expect( response.status ).to.equal( 404 );
				expect( response ).to.satisfyApiSpec;
			} );

			it( '412 - precondition failed', async () => {
				const yesterday = new Date( Date.now() - 24 * 60 * 60 * 1000 ).toUTCString();
				const response = await newReplaceRequestBuilder(
					testStatementId,
					newStatementWithRandomStringValue( stringPropertyId )
				)
					.withHeader( 'If-Unmodified-Since', yesterday )
					.makeRequest();

				expect( response.status ).to.equal( 412 );
				expect( response ).to.satisfyApiSpec;
			} );

			it( '415 - unsupported media type', async () => {
				const response = await newReplaceRequestBuilder(
					testStatementId,
					newStatementWithRandomStringValue( stringPropertyId )
				)
					.withHeader( 'Content-Type', 'text/plain' )
					.makeRequest();

				expect( response.status ).to.equal( 415 );
				expect( response ).to.satisfyApiSpec;
			} );

		} );

	} );

} );
