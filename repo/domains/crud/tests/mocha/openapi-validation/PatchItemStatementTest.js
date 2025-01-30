'use strict';

const { expect } = require( '../../../../../rest-api/tests/mocha/helpers/chaiHelper' );
const {
	newPatchItemStatementRequestBuilder,
	newPatchStatementRequestBuilder,
	newAddItemStatementRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const entityHelper = require( '../helpers/entityHelper' );

describe( 'validate PATCH endpoints for item statements against OpenAPI definition', () => {
	let testItemId;
	let testStatementId;
	let statementPropertyId;

	before( async function () {
		statementPropertyId = await entityHelper.getStringPropertyId();
		testItemId = await entityHelper.getItemId();
		testStatementId = ( await newAddItemStatementRequestBuilder(
			testItemId,
			entityHelper.newStatementWithRandomStringValue( statementPropertyId )
		).makeRequest() ).body.id;
	} );

	[
		( statementId, patch ) => newPatchItemStatementRequestBuilder( testItemId, statementId, patch ),
		newPatchStatementRequestBuilder
	].forEach( ( newPatchRequestBuilder ) => {
		describe( newPatchRequestBuilder().getRouteDescription(), () => {

			it( '200', async () => {
				const response = await newPatchRequestBuilder(
					testStatementId,
					[
						{ op: 'replace', path: '/value/content', value: 'I be patched!' }
					]
				).makeRequest();

				expect( response ).to.have.status( 200 );
				expect( response ).to.satisfyApiSchema;
			} );

			it( '400 - invalid patch provided', async () => {
				const response = await newPatchRequestBuilder(
					testStatementId,
					{ invalid: 'patch document' }
				).makeRequest();

				expect( response ).to.have.status( 400 );
				expect( response ).to.satisfyApiSchema;
			} );

			it( '404 - statement does not exist', async () => {
				const response = await newPatchRequestBuilder(
					`${testItemId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`,
					[
						{ op: 'replace', path: '/value/content', value: 'no patchy :(' }
					]
				).makeRequest();

				expect( response ).to.have.status( 404 );
				expect( response ).to.satisfyApiSchema;
			} );

			it( '409 - cannot apply patch', async () => {
				const response = await newPatchRequestBuilder(
					testStatementId,
					[
						{ op: 'replace', path: '/incorrect/path', value: 'no patchy :(' }
					]
				).makeRequest();

				expect( response ).to.have.status( 409 );
				expect( response ).to.satisfyApiSchema;
			} );

			it( '412 - precondition failed', async () => {
				const yesterday = new Date( Date.now() - 24 * 60 * 60 * 1000 ).toUTCString();
				const response = await newPatchRequestBuilder(
					testStatementId,
					[
						{ op: 'replace', path: '/value/content', value: 'no patchy :(' }
					]
				)
					.withHeader( 'If-Unmodified-Since', yesterday )
					.makeRequest();

				expect( response ).to.have.status( 412 );
				expect( response ).to.satisfyApiSchema;
			} );

			it( '422 - patch results in an invalid statement', async () => {
				const response = await newPatchRequestBuilder(
					testStatementId,
					[
						{
							op: 'replace',
							path: '/value/content',
							value: { invalid: [ 'datavalue', 'value', 'for a string datavalue' ] }
						}
					]
				).makeRequest();

				expect( response ).to.have.status( 422 );
				expect( response ).to.satisfyApiSchema;
			} );

		} );

	} );

} );
