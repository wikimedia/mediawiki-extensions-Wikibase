'use strict';

const { expect } = require( '../../../../../rest-api/tests/mocha/helpers/chaiHelper' );
const {
	newStatementWithRandomStringValue,
	getStringPropertyId,
	getItemId
} = require( '../helpers/entityHelper' );
const {
	newReplaceItemStatementRequestBuilder,
	newReplaceStatementRequestBuilder,
	newAddItemStatementRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );

describe( 'validate PUT endpoints for item statements against OpenAPI definition', () => {

	let testItemId;
	let testStatementId;
	let statementPropertyId;

	before( async () => {
		statementPropertyId = await getStringPropertyId();
		testItemId = await getItemId();
		testStatementId = ( await newAddItemStatementRequestBuilder(
			testItemId,
			newStatementWithRandomStringValue( statementPropertyId )
		).makeRequest() ).body.id;
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
				expect( response ).to.satisfyApiSchema;
			} );

			it( '400 - invalid statement serialization', async () => {
				const response = await newReplaceRequestBuilder(
					testStatementId,
					{ invalid: 'statement' }
				).makeRequest();

				expect( response ).to.have.status( 400 );
				expect( response ).to.satisfyApiSchema;
			} );

			it( '404 - statement does not exist', async () => {
				const response = await newReplaceRequestBuilder(
					`${testItemId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`,
					newStatementWithRandomStringValue( statementPropertyId )
				).makeRequest();

				expect( response ).to.have.status( 404 );
				expect( response ).to.satisfyApiSchema;
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
				expect( response ).to.satisfyApiSchema;
			} );

		} );

	} );

} );
