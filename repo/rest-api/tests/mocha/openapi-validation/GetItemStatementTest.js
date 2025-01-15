'use strict';

const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const {
	newGetItemStatementRequestBuilder,
	newGetStatementRequestBuilder,
	newAddItemStatementRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );

describe( 'validate GET item statement responses', () => {

	let testItemId;
	let testStatementId;
	let lastRevId;

	before( async () => {
		const statementPropertyId = await entityHelper.getStringPropertyId();
		testItemId = await entityHelper.getItemId();
		testStatementId = ( await newAddItemStatementRequestBuilder(
			testItemId,
			entityHelper.newStatementWithRandomStringValue( statementPropertyId )
		).makeRequest() ).body.id;

		lastRevId = ( await entityHelper.getLatestEditMetadata( testItemId ) ).revid;
	} );

	[
		( statementId ) => newGetItemStatementRequestBuilder( testItemId, statementId ),
		newGetStatementRequestBuilder
	].forEach( ( newRequestBuilder ) => {
		describe( newRequestBuilder().getRouteDescription(), () => {
			it( '200 OK response is valid', async () => {
				const response = await newRequestBuilder( testStatementId )
					.makeRequest();

				expect( response ).to.have.status( 200 );
				expect( response ).to.satisfyApiSchema;
			} );

			it( '304 Not Modified response is valid', async () => {
				const response = await newRequestBuilder( testStatementId )
					.withHeader( 'If-None-Match', `"${lastRevId}"` )
					.makeRequest();

				expect( response ).to.have.status( 304 );
				expect( response ).to.satisfyApiSchema;
			} );

			it( '400 Bad Request for invalid Statement ID', async () => {
				const invalidStatementId = 'X123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
				const response = await newRequestBuilder( invalidStatementId )
					.makeRequest();

				expect( response ).to.have.status( 400 );
				expect( response ).to.satisfyApiSchema;
			} );

			it( '404 Not Found response is valid for a non-existing statement', async () => {
				const nonexistentStatement = `${testItemId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`;

				const response = await newRequestBuilder( nonexistentStatement )
					.makeRequest();

				expect( response ).to.have.status( 404 );
				expect( response ).to.satisfyApiSchema;
			} );
		} );
	} );
} );
