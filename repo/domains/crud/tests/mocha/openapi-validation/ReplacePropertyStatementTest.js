'use strict';

const { expect } = require( '../../../../../rest-api/tests/mocha/helpers/chaiHelper' );
const {
	createPropertyWithStatements,
	newStatementWithRandomStringValue,
	getStringPropertyId
} = require( '../helpers/entityHelper' );
const {
	newReplacePropertyStatementRequestBuilder,
	newReplaceStatementRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );

describe( 'validate PUT endpoints for property statements against OpenAPI definition', () => {

	let testPropertyId;
	let testStatementId;
	let statementPropertyId;

	before( async () => {
		statementPropertyId = await getStringPropertyId();
		const property = await createPropertyWithStatements( [
			newStatementWithRandomStringValue( statementPropertyId )
		] );
		testPropertyId = property.id;
		testStatementId = property.statements[ statementPropertyId ][ 0 ].id;
	} );

	[
		( statementId, statement ) => newReplacePropertyStatementRequestBuilder( testPropertyId, statementId, statement ),
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
					`${testPropertyId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`,
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
